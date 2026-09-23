<?php
/**
 * WoodCon - TrustEngine (chống bom hàng COD)
 * Triển khai đúng logic_chong_bung_hang_COD.txt:
 *   - 3 tier: green (Xanh) / yellow (Vàng) / red (Đỏ)
 *   - Tín hiệu rủi ro bổ sung (>= 2 tín hiệu => cờ nghi ngờ)
 *   - Feedback loop tự cập nhật trust-level theo kết quả giao hàng
 */

declare(strict_types=1);

namespace WoodCon;

use PDO;

class TrustEngine
{
    public const GREEN = 'green';
    public const YELLOW = 'yellow';
    public const RED = 'red';

    /** Lấy tier của một số điện thoại (khách vãng lai mặc định = vàng) */
    public static function tierForPhone(string $phone): array
    {
        $user = static::first('SELECT * FROM users WHERE phone = ? LIMIT 1', [$phone]);
        if ($user) {
            return ['user_id' => (int)$user['id'], 'tier' => $user['trust_level'], 'success_orders' => (int)$user['success_orders']];
        }
        return ['user_id' => null, 'tier' => self::YELLOW, 'success_orders' => 0];
    }

    /**
     * Đánh giá tín hiệu rủi ro cho một đơn mới.
     * Trả risk=true nếu đạt >= 2 tín hiệu nghi ngờ.
     * (orders không lưu cột IP nên tín hiệu spam dựa trên SĐT.)
     * @return array{risk:bool,flags:array}
     */
    public static function evaluateSignals(string $phone, string $address, string $name): array
    {
        $flags = [];

        // Tín hiệu 1: nhiều đơn đặt liên tục trong thời gian ngắn từ cùng SĐT
        $maxPerHour = (int)get_setting('cod_max_orders_per_hour', 3);
        $recentByPhone = static::first(
            'SELECT COUNT(*) AS c FROM orders WHERE customer_phone = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)',
            [$phone]
        );
        if ((int)$recentByPhone['c'] >= $maxPerHour) {
            $flags[] = 'Nhieu_don_trong_gio';
        }

        // Tín hiệu 2: SĐT không hợp lệ
        if (!preg_match('/^(0|\+84)[0-9]{9,10}$/', str_replace(['.', '-', ' '], '', $phone))) {
            $flags[] = 'SDT_khong_hop_le';
        }

        // Tín hiệu 3: Địa chỉ mơ hồ / thiếu số nhà
        $addr = trim($address);
        if ($addr === '' || mb_strlen($addr, 'UTF-8') < 10 || !preg_match('/[0-9]/', $addr)) {
            $flags[] = 'Dia_chi_mo_ho';
        }

        // Tín hiệu 4: Tên người nhận ngẫu nhiên/giả (chuỗi ký tự đơn lẻ không hợp lệ)
        $name = trim($name);
        if ($name === '' || (bool)preg_match('/^[a-zA-Z0-9]{12,}$/u', $name)) {
            $flags[] = 'Ten_nguoi_nhan_fake';
        }

        return ['risk' => count($flags) >= 2, 'flags' => $flags];
    }

    /**
     * Feedback loop sau khi có kết quả giao hàng.
     * @param array $order đơn hàng hiện tại (có customer_phone)
     * @param string $event delivered | refused | cancelled
     */
    public static function onDeliveryResult(array $order, string $event, string $note = ''): void
    {
        $phone = $order['customer_phone'];
        $user = static::first('SELECT * FROM users WHERE phone = ? LIMIT 1', [$phone]);

        $tierBefore = $user['trust_level'] ?? self::YELLOW;
        $tierAfter = $tierBefore;

        if ($event === 'delivered') {
            if ($user) {
                $success = $user['success_orders'] + 1;
                // Vàng giao thành công 2 đơn liên tiếp => lên Xanh
                if ($tierBefore === self::YELLOW && $success >= 2) {
                    $tierAfter = self::GREEN;
                }
                if ($tierAfter === self::GREEN) {
                    $success = max($success, 2);
                }
                static::db()->prepare(
                    'UPDATE users SET success_orders = ?, trust_level = ?, trust_score = LEAST(trust_score + 20, 150) WHERE id = ?'
                )->execute([$success, $tierAfter, $user['id']]);
            }
        } elseif ($event === 'refused') {
            // Khách từ chối nhận không lý do hợp lệ => tự động xuống Tier Đỏ
            $tierAfter = self::RED;
            if ($user) {
                static::db()->prepare(
                    'UPDATE users SET failed_orders = failed_orders + 1, trust_level = ?, trust_score = GREATEST(trust_score - 50, -100) WHERE id = ?'
                )->execute([self::RED, $user['id']]);
            }
        }

        TrustLog::write($phone, $user['id'] ?? null, $order['id'], $event, $tierBefore, $tierAfter, $note);
    }

    /** Truy vấn nhanh */
    protected static function first(string $sql, array $params = []): ?array
    {
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    protected static function db(): PDO
    {
        return Database::connect();
    }
}

/** Model nhỏ ghi nhật ký tín nhiệm */
class TrustLog extends Base
{
    protected static string $table = 'trust_logs';

    public static function write(?string $phone, ?int $userId, ?int $orderId, string $event, ?string $before, ?string $after, string $note = ''): void
    {
        static::insert([
            'phone'       => $phone ?: '#unknown',
            'user_id'     => $userId,
            'order_id'    => $orderId,
            'event'       => $event,
            'tier_before' => $before,
            'tier_after'  => $after,
            'note'        => $note,
        ]);
    }
}