<?php
/**
 * WoodCon - Model Voucher / Mã giảm giá
 * Tách 2 loại: discount (giảm giá) và freeship (miễn phí vận chuyển).
 * Tuân thủ ràng buộc kỹ thuật theo PROMPT_LOGIC mục 4.
 */

declare(strict_types=1);

namespace WoodCon;

class Voucher extends Base
{
    protected static string $table = 'vouchers';

    /**
     * Validate va tính số tiền giảm cho một mã.
     * @param string   $code       Mã voucher
     * @param string   $type       discount | freeship
     * @param float    $orderValue Giá trị đơn hàng (trước chiết khấu, dùng tính min_order)
     * @param float    $shippingFee Phí ship gốc (dùng cho mã freeship)
     * @param ?int     $userId     Khách hàng (để kiểm tra lượt/người)
     * @param array    $items      Danh sách sản phẩm trong đơn [{product_id, category_id}]
     * @return array{valid:bool,error:string,discount:float,name:string,id:int}
     */
    public static function validate(
        string $code,
        string $type,
        float $orderValue,
        float $shippingFee = 0,
        ?int $userId = null,
        array $items = []
    ): array {
        $code = strtoupper(trim($code));
        $fail = fn(string $err): array => ['valid' => false, 'error' => $err, 'discount' => 0, 'name' => '', 'id' => 0];

        if ($code === '') {
            return $fail('Vui lòng nhập mã giảm giá.');
        }

        $v = static::first(
            'SELECT * FROM vouchers WHERE code = ? AND type = ? AND status = 1 LIMIT 1',
            [$code, $type]
        );
        if (!$v) {
            return $fail('Mã giảm giá không tồn tại hoặc không đúng loại.');
        }

        $now = date('Y-m-d H:i:s');
        if ($v['start_date'] > $now || $v['end_date'] < $now) {
            return $fail('Mã giảm giá đã hết hạn sử dụng.');
        }

        if ((float)$orderValue < (float)$v['min_order_value']) {
            return $fail('Đơn hàng chưa đạt giá trị tối thiểu ' . format_money((int)$v['min_order_value']) . ' để dùng mã này.');
        }

        if ((int)$v['total_quantity'] > 0 && (int)$v['used_quantity'] >= (int)$v['total_quantity']) {
            return $fail('Mã giảm giá đã hết lượt sử dụng.');
        }

        // Kiểm tra cấp bậc hội viên (đúng cấp trở lên)
        if (isset($v['required_tier_id']) && (int)$v['required_tier_id'] > 0) {
            if (!$userId) {
                return $fail('Vui lòng đăng nhập để dùng mã này.');
            }
            $reqStmt = static::db()->prepare(
                'SELECT min_total_spent FROM membership_tiers WHERE id = ? AND status = 1 LIMIT 1'
            );
            $reqStmt->execute([(int)$v['required_tier_id']]);
            $reqTier = $reqStmt->fetch();
            if (!$reqTier) {
                return $fail('Mã giảm giá này hiện không khả dụng.');
            }
            $reqMin = (float)$reqTier['min_total_spent'];

            $userStmt = static::db()->prepare(
                'SELECT mt.min_total_spent
                   FROM users u JOIN membership_tiers mt ON mt.id = u.membership_tier_id
                  WHERE u.id = ? LIMIT 1'
            );
            $userStmt->execute([$userId]);
            $userMin = (float)($userStmt->fetch()['min_total_spent'] ?? 0);

            if ($userMin < $reqMin) {
                return $fail('Mã này chỉ dành cho hội viên đủ cấp bậc.');
            }
        }

        // Kiểm tra giới hạn lượt dùng trên mỗi tài khoản
        if ($userId) {
            $col = $type === 'freeship' ? 'freeship_code' : 'voucher_code';
            $stmt = static::db()->prepare("SELECT COUNT(*) AS c FROM orders WHERE user_id = ? AND {$col} = ? AND order_status <> 'cancelled'");
            $stmt->execute([$userId, $code]);
            $usedByUser = (int)$stmt->fetch()['c'];
            if ($usedByUser >= (int)$v['per_user_limit']) {
                return $fail('Bạn đã dùng hết lượt cho mã này.');
            }
        }

        // Phạm vi áp dụng (toàn shop / danh mục / sản phẩm)
        if ($v['scope_type'] !== 'all' && !empty($items)) {
            $decoded = $v['scope_ids'] ? json_decode($v['scope_ids'], true) : [];
            $scopes = is_array($decoded) ? $decoded : [$decoded];
            $match = false;
            foreach ($items as $item) {
                if ($v['scope_type'] === 'category' && in_array((int)$item['category_id'], $scopes, true)) {
                    $match = true;
                    break;
                }
                if ($v['scope_type'] === 'product' && in_array((int)$item['product_id'], $scopes, true)) {
                    $match = true;
                    break;
                }
            }
            if (!$match) {
                return $fail('Mã giảm giá chỉ áp dụng cho danh mục/sản phẩm chỉ định.');
            }
        }

        // Tính số tiền giảm
        $discount = 0.0;
        if ($type === 'discount') {
            if ($v['discount_type'] === 'percent') {
                $discount = ($orderValue * (float)$v['discount_value']) / 100;
                if ((float)$v['max_discount'] > 0 && $discount > (float)$v['max_discount']) {
                    $discount = (float)$v['max_discount'];
                }
            } else {
                $discount = (float)$v['discount_value'];
            }
            // Không hoàn chênh lệch nếu voucher > đơn hàng
            $discount = min($discount, $orderValue);
        } else {
            // freeship: trừ vào phí ship, tối đa = max_discount (nếu cấu hình)
            $discount = $shippingFee;
            if ((float)$v['max_discount'] > 0 && $discount > (float)$v['max_discount']) {
                $discount = (float)$v['max_discount'];
            }
        }

        return ['valid' => true, 'error' => '', 'discount' => (int)round($discount), 'name' => $v['name'], 'id' => (int)$v['id']];
    }

    /** Tăng lượt đã dùng sau khi mua thành công */
    public static function incrementUsed(int $voucherId): void
    {
        static::db()->prepare('UPDATE vouchers SET used_quantity = used_quantity + 1 WHERE id = ?')
            ->execute([$voucherId]);
    }

    /** Danh sách voucher đang hiệu lực cho trang khuyến mãi */
    public static function active(string $type = 'discount', int $limit = 12): array
    {
        $now = date('Y-m-d H:i:s');
        return static::query(
            'SELECT * FROM vouchers WHERE type = ? AND status = 1 AND start_date <= ? AND end_date >= ?
             AND (total_quantity = 0 OR used_quantity < total_quantity) ORDER BY discount_value DESC LIMIT ?',
            [$type, $now, $now, $limit]
        );
    }
}