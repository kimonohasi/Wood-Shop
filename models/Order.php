<?php
/**
 * WoodCon - Model Đơn hàng
 * Vòng đời 8 trạng thái theo PROMPT_LOGIC mục 1 (mô hình Shopee):
 * pending -> manual_verifying/cancelled; pending -> confirmed -> preparing
 * -> shipping -> (delivery_failed *) -> delivered | returned | cancelled
 * Side-effect chuẩn:
 *  - Trừ kho khi đặt, hoàn kho khi huỷ/hoàn.
 *  - Chỉ tính doanh thu + tích điểm + sinh bảo hành khi "delivered".
 *  - Hoàn tiền riêng khi đã thanh toán trước mà đơn huỷ/hoàn.
 */

declare(strict_types=1);

namespace WoodCon;

use PDO;

class Order extends Base
{
    protected static string $table = 'orders';

    public const STATUS_LABEL = [
        'pending'          => 'Chờ xác nhận',
        'manual_verifying' => 'Chờ xác minh thủ công',
        'confirmed'        => 'Đã xác nhận',
        'preparing'        => 'Đang chuẩn bị hàng',
        'shipping'         => 'Đang giao hàng',
        'delivery_failed'  => 'Giao hàng thất bại',
        'delivered'        => 'Đã giao thành công',
        'returned'         => 'Hoàn hàng về shop',
        'cancelled'        => 'Đã hủy',
    ];

    public const STATUS_BADGE = [
        'pending'          => 'warning',
        'manual_verifying' => 'danger',
        'confirmed'        => 'info',
        'preparing'        => 'secondary',
        'shipping'         => 'primary',
        'delivery_failed'  => 'dark',
        'delivered'        => 'success',
        'returned'         => 'danger',
        'cancelled'        => 'secondary',
    ];

    /** Sinh mã đơn hàng duy nhất */
    public static function generateCode(): string
    {
        do {
            $code = 'WC' . date('ymd') . strtoupper(bin2hex(random_bytes(2)));
            $exists = static::first('SELECT id FROM orders WHERE order_code = ? LIMIT 1', [$code]);
        } while ($exists);
        return $code;
    }

    public static function byCode(string $code): ?array
    {
        return static::first('SELECT * FROM orders WHERE order_code = ? LIMIT 1', [$code]);
    }

    public static function itemsOf(int $orderId): array
    {
        return static::query('SELECT * FROM order_items WHERE order_id = ?', [$orderId]);
    }

    /** Danh sách đơn của khách (trang lịch sử mua hàng) */
    public static function forUser(int $userId): array
    {
        return static::query(
            'SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC',
            [$userId]
        );
    }

    /** Danh sách đơn của khách có phân trang (trang lịch sử mua hàng) */
    public static function paginatedForUser(int $userId, int $page = 1, int $perPage = 10): array
    {
        $total = (int)static::count('user_id = ?', [$userId]);
        $pager = paginate($total, $perPage, $page);
        $rows = static::query(
            'SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $pager['offset'],
            [$userId]
        );
        return ['total' => $total, 'rows' => $rows, 'pager' => $pager];
    }

    /** Kiểm tra đơn thuộc quyền khách trước khi truy cập */
    public static function belongTo(array $order, int $userId): bool
    {
        return (int)$order['user_id'] === $userId;
    }

    /**
     * Chuyển trạng thái đơn hàng theo luồng hợp lệ.
     * Có side-effect tùy trạng thái đích.
     * @param array $order    đơn hiện hành
     * @param string $newStatus trạng thái đích
     * @param string $note    ghi chú (admin)
     * @return array{ok:bool,message:string}
     */
    public static function transition(array $order, string $newStatus, string $note = ''): array
    {
        $db = static::db();
        $cur = $order['order_status'];

        // Ma trận chuyển trạng thái cho phép
        $allowed = [
            'pending'          => ['manual_verifying', 'confirmed', 'cancelled'],
            'manual_verifying' => ['confirmed', 'cancelled'],
            'confirmed'        => ['preparing', 'cancelled'],
            'preparing'        => ['shipping', 'cancelled'],
            'shipping'         => ['delivery_failed', 'delivered', 'returned'],
            'delivery_failed'  => ['shipping', 'returned', 'delivered'],
            'delivered'        => [],
            'returned'         => [],
            'cancelled'        => [],
        ];

        if (!in_array($newStatus, $allowed[$cur] ?? [], true)) {
            return ['ok' => false, 'message' => 'Không thể chuyển từ "' . self::STATUS_LABEL[$cur] . '" sang "' . self::STATUS_LABEL[$newStatus] . '".'];
        }

        $updates = ['order_status' => $newStatus];

        // Giao thất bại: đếm lần, tự lên lịch giao lại, sau 3 lần -> hoàn hàng
        if ($newStatus === 'delivery_failed') {
            $failCount = (int)$order['delivery_fail_count'] + 1;
            $maxFail = (int)get_setting('max_delivery_fail', 3);
            if ($failCount >= $maxFail) {
                $newStatus = 'returned';
                $updates['order_status'] = 'returned';
                $updates['cancel_reason'] = 'Giao thất bại ' . $failCount . ' lần liên tiếp.';
            }
            $updates['delivery_fail_count'] = $failCount;
        }

        // Giao lại sau thất bại: reset nguyên nhân
        if ($newStatus === 'shipping' && $cur === 'delivery_failed') {
            $updates['cancel_reason'] = null;
        }

        if ($newStatus === 'shipping') {
            $updates['tracking_code'] = $note !== '' && str_starts_with($note, 'VD:') ? trim(substr($note, 3)) : $order['tracking_code'];
            $updates['delivery_company'] = $note !== '' ? $note : $order['delivery_company'];
        }

        // Đã giao: doanh thu chính thức + điểm + bảo hành + trust green
        if ($newStatus === 'delivered') {
            if (empty($order['delivered_at'])) {
                $updates['delivered_at'] = date('Y-m-d H:i:s');
            }
            $db->beginTransaction();
            try {
                static::update((int)$order['id'], $updates);
                self::grantDeliveredBenefits((int)$order['id']);
                $db->commit();
            } catch (\Throwable $e) {
                $db->rollBack();
                return ['ok' => false, 'message' => 'Lỗi xử lý đơn giao thành công: ' . $e->getMessage()];
            }
            $order = array_merge($order, $updates);
            TrustEngine::onDeliveryResult($order, 'delivered');
            write_log('order', 'Đơn ' . $order['order_code'] . ' đã giao thành công.', $_SESSION['admin_id'] ?? null);
            // Tự động phát hành HÓA ĐƠN khi đơn HOÀN THÀNH (idempotent)
            Invoice::generateForOrder($order, static::itemsOf((int)$order['id']), $_SESSION['admin_id'] ?? null);
            return ['ok' => true, 'message' => 'Đã xác nhận đơn giao thành công.'];
        }

        // Hoàn hàng / Hủy: hoàn kho + hoàn tiền nếu đã thanh toán trước + hoàn điểm đã dùng
        if (in_array($newStatus, ['returned', 'cancelled'], true)) {
            $db->beginTransaction();
            try {
                static::update((int)$order['id'], $updates);
                self::restoreStock((int)$order['id']);
                if ((int)$order['points_used'] > 0 && !empty($order['user_id'])) {
                    self::refundUsedPoints((int)$order['id']);
                }
                if ($order['payment_status'] === 'paid') {
                    self::refund((int)$order['id'], (float)$order['total_amount'], 'Hoàn tiền đơn hàng bị ' . self::STATUS_LABEL[$newStatus], 'bank');
                }
                $db->commit();
            } catch (\Throwable $e) {
                $db->rollBack();
                return ['ok' => false, 'message' => $e->getMessage()];
            }
            $order = array_merge($order, $updates);
            if ($newStatus === 'returned') {
                TrustEngine::onDeliveryResult($order, 'refused', 'Hoàn hàng về shop.');
            }
            write_log('order', 'Đơn ' . $order['order_code'] . ' chuyển ' . self::STATUS_LABEL[$newStatus] . '.', $_SESSION['admin_id'] ?? null);
            return ['ok' => true, 'message' => 'Đã cập nhật đơn hàng.'];
        }

        static::update((int)$order['id'], $updates);
        write_log('order', 'Đơn ' . $order['order_code'] . ' chuyển sang ' . self::STATUS_LABEL[$newStatus] . '.', $_SESSION['admin_id'] ?? null);
        return ['ok' => true, 'message' => 'Đã cập nhật trạng thái đơn hàng.'];
    }

    /** Khách tự hủy trong thời gian chờ xác nhận (miễn phí) */
    public static function cancelWithinWait(array $order): array
    {
        if ($order['order_status'] !== 'pending') {
            return ['ok' => false, 'message' => 'Đơn không còn trong thời gian chờ xác nhận.'];
        }
        $waitMinutes = (int)get_setting('order_wait_confirm_minutes', 20);
        $deadline = strtotime($order['created_at']) + $waitMinutes * 60;
        if (time() > $deadline) {
            return ['ok' => false, 'message' => 'Đã hết thời gian tự hủy, vui lòng liên hệ CSKH.'];
        }
        return static::transition($order, 'cancelled', 'Khách tự hủy trong thời gian chờ xác nhận.');
    }

    /** Khách gửi yêu cầu hủy sau khi đã xác nhận */
    public static function requestCancel(array $order, string $reason): array
    {
        if ($order['cancel_request_status'] !== 'none') {
            return ['ok' => false, 'message' => 'Yêu cầu hủy đã tồn tại cho đơn này.'];
        }
        if (!in_array($order['order_status'], ['confirmed', 'preparing'], true)) {
            return ['ok' => false, 'message' => 'Đơn không trong trạng thái có thể yêu cầu hủy.'];
        }
        OrderCancelRequest::insert([
            'order_id' => $order['id'],
            'reason'   => $reason,
            'status'   => 'pending',
        ]);
        static::update((int)$order['id'], ['cancel_request_status' => 'requested', 'cancel_reason' => $reason]);
        write_log('order', 'Khách yêu cầu hủy đơn ' . $order['order_code'] . ': ' . $reason);
        return ['ok' => true, 'message' => 'Đã gửi yêu cầu hủy, chờ shop xác nhận.'];
    }

    /** Admin duyệt/từ chối yêu cầu hủy */
    public static function handleCancelRequest(int $cancelRequestId, string $decision, string $adminNote = ''): array
    {
        $req = OrderCancelRequest::find($cancelRequestId);
        if (!$req || $req['status'] !== 'pending') {
            return ['ok' => false, 'message' => 'Yêu cầu không hợp lệ hoặc đã xử lý.'];
        }
        $order = Order::find((int)$req['order_id']);
        if (!$order) {
            return ['ok' => false, 'message' => 'Không tìm thấy đơn hàng.'];
        }

        if ($decision === 'approve') {
            $res = static::transition($order, 'cancelled', 'Đồng ý hủy theo yêu cầu khách.');
            if ($res['ok']) {
                OrderCancelRequest::update($cancelRequestId, ['status' => 'approved', 'admin_note' => $adminNote]);
                static::update((int)$order['id'], ['cancel_request_status' => 'approved']);
            }
            return $res;
        }

        // Từ chối: đơn quay lại trạng thái trước đó, ghi lý do
        OrderCancelRequest::update($cancelRequestId, ['status' => 'rejected', 'admin_note' => $adminNote]);
        static::update((int)$order['id'], ['cancel_request_status' => 'rejected', 'cancel_reject_reason' => $adminNote]);
        return ['ok' => true, 'message' => 'Đã từ chối yêu cầu hủy đơn.'];
    }

    /** Hoàn tiền (khi đơn thanh toán trước bị hủy/hoàn) - ghi log đối soát */
    public static function refund(int $orderId, float $amount, string $reason, string $method = 'bank'): bool
    {
        Refund::insert([
            'order_id'   => $orderId,
            'amount'     => $amount,
            'method'     => $method,
            'reason'     => $reason,
            'status'     => 'pending',
            'created_by' => $_SESSION['admin_id'] ?? null,
        ]);
        write_log('refund', 'Tạo yêu cầu hoàn tiền đơn #' . $orderId . ' số tiền ' . format_money((int)$amount) . ' - ' . $reason, $_SESSION['admin_id'] ?? null);
        return true;
    }

    /** Hoàn tất hoàn tiền - cập nhật trạng thái thanh toán + log */
    public static function completeRefund(int $refundId, int $adminId): bool
    {
        $refund = Refund::find($refundId);
        if (!$refund) {
            return false;
        }
        $orderId = (int)$refund['order_id'];
        Refund::update($refundId, ['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')]);
        static::update($orderId, ['payment_status' => 'refunded']);
        write_log('refund', 'Hoàn tất hoàn tiền #' . $refundId . ' cho đơn #' . $orderId, $adminId);

        // Đơn đã từng xuất HÓA ĐƠN BÁN (SALE_INVOICE) → phát hành Credit Note điều chỉnh giảm,
        // trỏ về hóa đơn gốc qua original_invoice_id (createCreditNote tự chống phát hành trùng).
        // Lưu ý: điều kiện cũ `order_status === 'delivered'` luôn sai vì lúc hoàn tất hoàn tiền
        // đơn đã là cancelled/returned; kiểm tra theo HÓA ĐƠN GỐC — đơn hủy trước khi giao chưa
        // từng có SALE_INVOICE nên không sinh credit note (giữ nguyên hành vi cũ).
        $order = static::find($orderId);
        $sale = $order ? Invoice::first(
            "SELECT * FROM invoices WHERE order_id = ? AND type = ? LIMIT 1",
            [$orderId, Invoice::TYPE_SALE]
        ) : null;
        if ($sale) {
            Invoice::createCreditNote(
                $sale,
                $order,
                static::itemsOf($orderId),
                'Hoàn tiền đầy đủ đơn hàng đã giao.',
                $adminId
            );
        }
        return true;
    }

    /** Cộng điểm + tạo bảo hành khi đơn đã giao thành công */
    protected static function grantDeliveredBenefits(int $orderId): void
    {
        $order = static::find($orderId);
        if (!$order || (int)$order['user_id'] === 0 || $order['user_id'] === null) {
            // Khách vãng lai vẫn cần bảo hành theo SĐT
            self::createWarranties($orderId, static::itemsOf($orderId));
            return;
        }

        $rate = max(1, (int)get_setting('point_rate', 10000));
        // Điểm gốc = floor(total_amount / point_rate), rồi nhân hệ số của HẠNG THÀNH VIÊN
        // TẠI THỜI ĐIỂM GIAO HÀNG (trước syncMembershipTier thăng hạng); floor lần cuối.
        // computePoints (tiêu điểm) giữ nguyên 1 điểm = 1.000đ — KHÔNG đổi.
        $multiplier = 1.0;
        if ((int)$order['user_id'] > 0) {
            $tierRow = static::first(
                'SELECT mt.points_multiplier FROM users u JOIN membership_tiers mt ON mt.id = u.membership_tier_id WHERE u.id = ? LIMIT 1',
                [(int)$order['user_id']]
            );
            $multiplier = $tierRow ? max(1.0, (float)($tierRow['points_multiplier'] ?? 1.0)) : 1.0;
        }
        $points = (int)floor(((float)$order['total_amount'] / $rate) * $multiplier);
        static::update($orderId, ['points_earned' => $points]);

        $db = static::db();
        $db->prepare('UPDATE users SET points = points + ? WHERE id = ?')
            ->execute([$points, $order['user_id']]);
        PointsTransaction::insert([
            'user_id'      => $order['user_id'],
            'order_id'     => $orderId,
            'points_change' => $points,
            'type'         => 'earn',
            'note'         => 'Tích điểm đơn ' . $order['order_code'],
        ]);
        self::syncMembershipTier((int)$order['user_id']);
        self::createWarranties($orderId, static::itemsOf($orderId));
    }

    /** Đồng bộ hạng thành viên theo tổng chi tiêu đơn ĐÃ GIAO */
    public static function syncMembershipTier(int $userId): void
    {
        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $spent = (float)static::query(
            'SELECT COALESCE(SUM(total_amount),0) AS s FROM orders WHERE user_id = ? AND order_status = "delivered"',
            [$userId]
        )[0]['s'];

        $tiers = static::query('SELECT * FROM membership_tiers WHERE status = 1 ORDER BY min_total_spent DESC');
        $tierId = (int)$user['membership_tier_id'];
        foreach ($tiers as $tier) {
            if ($spent >= (float)$tier['min_total_spent']) {
                $tierId = (int)$tier['id'];
                break;
            }
        }

        static::db()->prepare('UPDATE users SET total_spent = ?, membership_tier_id = ?, membership_expired = DATE_ADD(CURDATE(), INTERVAL 12 MONTH) WHERE id = ?')
            ->execute([$spent, $tierId, $userId]);
    }

    /** Tạo phiếu bảo hành cho từng sản phẩm trong đơn (nhánh C) */
    protected static function createWarranties(int $orderId, array $items): void
    {
        $db = static::db();
        $monthsByItem = [];
        $warrantyRows = static::query(
            'SELECT oi.id, p.warranty_months FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?',
            [$orderId]
        );
        foreach ($warrantyRows as $wr) {
            $monthsByItem[(int)$wr['id']] = (int)$wr['warranty_months'];
        }
        foreach ($items as $item) {
            $months = $monthsByItem[(int)$item['id']] ?? 12;
            $serial = 'WC-' . str_pad((string)$item['id'], 5, '0', STR_PAD_LEFT) . '-' . strtoupper(bin2hex(random_bytes(2)));
            $db->prepare(
                'INSERT INTO warranties (order_id, order_item_id, serial_no, purchase_date, warranty_months, warranty_end, status)
                 VALUES (?, ?, ?, CURDATE(), ?, DATE_ADD(CURDATE(), INTERVAL ? MONTH), "active")'
            )->execute([$orderId, $item['id'], $serial, $months, $months]);
        }
    }

    /** Hoàn lại kho khi đơn huỷ/hoàn */
    protected static function restoreStock(int $orderId): void
    {
        $db = static::db();
        $items = static::itemsOf($orderId);
        $db->prepare('UPDATE products p JOIN order_items oi ON oi.product_id = p.id AND oi.order_id = ? SET p.quantity = p.quantity + oi.quantity')
            ->execute([$orderId]);
    }

    /** Tra cứu phiếu bảo hành theo tem/serial (không cần đăng nhập) */
    public static function warrantyBySerial(string $serial): ?array
    {
        return static::first(
            'SELECT w.*, oi.product_name, oi.price, o.customer_name as customer_name,
                    o.customer_phone, o.order_code, p.slug AS product_slug, p.cover_image
             FROM warranties w
             JOIN order_items oi ON oi.id = w.order_item_id
             JOIN orders o ON o.id = w.order_id
             JOIN products p ON p.id = oi.product_id
             WHERE w.serial_no = ? LIMIT 1',
            [$serial]
        );
    }

    /** Lịch sử bảo hành của một phiếu */
    public static function warrantyHistory(int $warrantyId): array
    {
        return static::query(
            'SELECT * FROM warranty_history WHERE warranty_id = ? ORDER BY id DESC',
            [$warrantyId]
        );
    }

    /** Ghi lịch sử bảo hành */
    public static function logWarranty(int $warrantyId, string $description): void
    {
        static::db()->prepare('INSERT INTO warranty_history (warranty_id, description) VALUES (?, ?)')
            ->execute([$warrantyId, trim($description)]);
    }

    /** Kiểm tra phiếu còn hạn hay hết hạn, cập nhật status */
    public static function syncWarrantyStatus(int $warrantyId): void
    {
        static::db()->prepare(
            'UPDATE warranties SET status = IF(status = "active" AND warranty_end < CURDATE(), "expired", status) WHERE id = ?'
        )->execute([$warrantyId]);
    }

    /** Hoàn lại số điểm khách đã dùng khi đơn bị hủy/hoàn hàng */
    protected static function refundUsedPoints(int $orderId): void
    {
        $order = static::find($orderId);
        if (!$order || (int)$order['points_used'] <= 0 || empty($order['user_id'])) {
            return;
        }
        $used = (int)$order['points_used'];
        static::db()->prepare('UPDATE users SET points = points + ? WHERE id = ?')
            ->execute([$used, $order['user_id']]);
        PointsTransaction::insert([
            'user_id'       => $order['user_id'],
            'order_id'      => $orderId,
            'points_change' => $used,
            'type'          => 'admin_adjust',
            'note'          => 'Hoàn điểm đơn ' . $order['order_code'] . ' bị hủy/hoàn hàng',
        ]);
    }
}

/** Model phụ trợ */
class OrderCancelRequest extends Base
{
    protected static string $table = 'order_cancel_requests';
}

class Refund extends Base
{
    protected static string $table = 'refunds';
}

class PointsTransaction extends Base
{
    protected static string $table = 'points_transactions';
}