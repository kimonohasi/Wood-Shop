<?php
/**
 * WoodCon - Model Người dùng (khách hàng)
 */

declare(strict_types=1);

namespace WoodCon;

class User extends Base
{
    protected static string $table = 'users';

    /** Đăng ký tài khoản mới */
    public static function register(array $data): array
    {
        $name = trim($data['name'] ?? '');
        $username = trim(strtolower($data['username'] ?? ''));
        $email = trim(strtolower($data['email'] ?? ''));
        $phone = trim($data['phone'] ?? '');
        $password = $data['password'] ?? '';

        if ($username !== '' && static::findBy('username', $username)) {
            return ['ok' => false, 'message' => 'Tên đăng nhập này đã được sử dụng.'];
        }
        if (static::findBy('email', $email)) {
            return ['ok' => false, 'message' => 'Email này đã được đăng ký.'];
        }
        if (static::findBy('phone', $phone)) {
            return ['ok' => false, 'message' => 'Số điện thoại này đã được đăng ký.'];
        }

        $id = static::insert([
            'name'     => $name,
            'username' => $username !== '' ? $username : null,
            'email'    => $email,
            'phone'    => $phone,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'gender'   => $data['gender'] ?? 'other',
            'trust_level' => TrustEngine::YELLOW,
            'membership_tier_id' => 1,
            'membership_expired' => date('Y-m-d', strtotime('+12 months')),
        ]);
        return ['ok' => true, 'id' => $id];
    }

    /** Đăng nhập bằng tên đăng nhập, email hoặc SĐT */
    public static function login(string $account, string $password): array
    {
        $user = static::first('SELECT * FROM users WHERE (username = ? OR email = ? OR phone = ?) AND status = 1 LIMIT 1', [$account, $account, $account]);
        // Tài khoản đăng nhập bằng Google không có mật khẩu -> không cho đăng nhập mật khẩu
        if (!$user || $user['password'] === null || !password_verify($password, $user['password'])) {
            return ['ok' => false, 'message' => 'Sai thông tin đăng nhập.'];
        }
        return ['ok' => true, 'user' => $user];
    }

    /** Tạo tài khoản từ Google (không có mật khẩu, không bắt buộc SĐT) */
    public static function createWithGoogle(string $email, string $name, string $googleId, ?string $avatar = null): int
    {
        return static::insert([
            'name'               => $name,
            'username'           => null,
            'email'              => $email,
            'phone'              => null,
            'password'           => null,
            'avatar'             => $avatar,
            'gender'             => 'other',
            'trust_level'        => TrustEngine::YELLOW,
            'membership_tier_id' => 1,
            'membership_expired' => date('Y-m-d', strtotime('+12 months')),
            'google_id'          => $googleId,
            'email_verify'       => 1,
        ]);
    }

    public static function changePassword(int $userId, string $old, string $new): array
    {
        $user = static::find($userId);
        if (!$user || !password_verify($old, $user['password'])) {
            return ['ok' => false, 'message' => 'Mật khẩu cũ không đúng.'];
        }
        static::update($userId, ['password' => password_hash($new, PASSWORD_BCRYPT)]);
        write_log('user', 'Đổi mật khẩu thành công.', null, $userId);
        return ['ok' => true, 'message' => 'Đã đổi mật khẩu thành công.'];
    }

    /** Đặt lại mật khẩu. $method = 'phone' hoặc 'email' */
    public static function resetPassword(string $account, string $otp, string $new, string $method = 'phone'): array
    {
        if ($method === 'email') {
            $user = static::first('SELECT * FROM users WHERE email = ? LIMIT 1', [$account]);
        } else {
            $user = static::first('SELECT * FROM users WHERE phone = ? LIMIT 1', [$account]);
        }
        if (!$user) {
            $label = $method === 'email' ? 'email' : 'số điện thoại';
            return ['ok' => false, 'message' => "Không tìm thấy tài khoản với {$label} này."];
        }
        if ($user['otp_code'] === null || $user['otp_expired'] < date('Y-m-d H:i:s') || !hash_equals((string)$user['otp_code'], trim($otp))) {
            return ['ok' => false, 'message' => 'Mã OTP không hợp lệ hoặc đã hết hạn.'];
        }
        static::update((int)$user['id'], ['password' => password_hash($new, PASSWORD_BCRYPT), 'otp_code' => null, 'otp_expired' => null]);
        return ['ok' => true, 'message' => 'Đã đặt lại mật khẩu.'];
    }

    /** Cập nhật thông tin hồ sơ */
    public static function updateProfile(int $userId, array $data): bool
    {
        return static::update($userId, [
            'name'    => $data['name'],
            'gender'  => $data['gender'] ?? 'other',
            'birthday'=> $data['birthday'] ?: null,
            'address' => $data['address'] ?: null,
            'ward'    => $data['ward'] ?: null,
            'district'=> $data['district'] ?: null,
            'city'    => $data['city'] ?: null,
        ]);
    }

    // ---------------- Yêu thích sản phẩm ----------------
    public static function addWishlist(int $userId, int $productId): bool
    {
        if (static::first('SELECT id FROM wishlists WHERE user_id = ? AND product_id = ? LIMIT 1', [$userId, $productId])) {
            return false;
        }
        return static::db()->prepare('INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)')
            ->execute([$userId, $productId]);
    }

    public static function removeWishlist(int $userId, int $productId): bool
    {
        return static::db()->prepare('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?')
            ->execute([$userId, $productId]);
    }

    public static function wishlist(int $userId, int $page = 1, int $perPage = 12): array
    {
        $count = (int)static::first(
            'SELECT COUNT(*) AS c FROM wishlists w JOIN products p ON p.id = w.product_id
             WHERE w.user_id = ? AND p.status = 1',
            [$userId]
        )['c'];
        $pager = paginate($count, $perPage, $page);
        $rows = static::query(
            'SELECT p.*, w.created_at AS liked_at FROM wishlists w JOIN products p ON p.id = w.product_id
             WHERE w.user_id = ? AND p.status = 1 ORDER BY w.id DESC
             LIMIT ' . $perPage . ' OFFSET ' . $pager['offset'],
            [$userId]
        );
        return ['total' => $count, 'rows' => $rows, 'pager' => $pager];
    }

    public static function inWishlist(int $userId, ?int $productId): bool
    {
        if (!$userId || !$productId) {
            return false;
        }
        return (bool)static::first('SELECT id FROM wishlists WHERE user_id = ? AND product_id = ? LIMIT 1', [$userId, $productId]);
    }

    /** Đánh giá sản phẩm (chỉ khi đã mua và giao thành công) */
    public static function canReview(int $userId, int $productId): bool
    {
        $row = static::first(
            'SELECT o.id FROM orders o
             JOIN order_items oi ON oi.order_id = o.id AND oi.product_id = ?
             WHERE o.user_id = ? AND o.order_status = "delivered" LIMIT 1',
            [$productId, $userId]
        );
        return (bool)$row;
    }

    /** Kiểm tra người dùng đã đánh giá sản phẩm này chưa */
    public static function hasReviewed(int $userId, int $productId): bool
    {
        return (bool)static::first(
            'SELECT id FROM reviews WHERE user_id = ? AND product_id = ? LIMIT 1',
            [$userId, $productId]
        );
    }

    // ---------------- Điểm thưởng & hạng thành viên ----------------
    public static function pointsHistory(int $userId, int $page = 1, int $perPage = 15): array
    {
        $count = (int)static::first(
            'SELECT COUNT(*) AS c FROM points_transactions WHERE user_id = ?',
            [$userId]
        )['c'];
        $pager = paginate($count, $perPage, $page);
        $rows = static::query(
            'SELECT pt.*, o.order_code, o.total_amount AS order_total
             FROM points_transactions pt
             LEFT JOIN orders o ON o.id = pt.order_id
             WHERE pt.user_id = ?
             ORDER BY pt.id DESC LIMIT ' . $perPage . ' OFFSET ' . $pager['offset'],
            [$userId]
        );
        return ['total' => $count, 'rows' => $rows, 'pager' => $pager];
    }

    public static function membershipTier(int $userId): ?array
    {
        $user = static::find($userId);
        if (!$user) {
            return null;
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

        return static::first(
            'SELECT mt.*, u.membership_expired
             FROM users u
             JOIN membership_tiers mt ON mt.id = ?
             WHERE u.id = ? LIMIT 1',
            [$tierId, $userId]
        );
    }

    /** Hạng kế tiếp (nếu có) theo tổng chi tiêu hiện tại */
    public static function nextMembershipTier(int $userId): ?array
    {
        $user = static::find($userId);
        if (!$user) {
            return null;
        }
        $spent = (float)static::query(
            'SELECT COALESCE(SUM(total_amount),0) AS s FROM orders WHERE user_id = ? AND order_status = "delivered"',
            [$userId]
        )[0]['s'];
        return static::first(
            'SELECT * FROM membership_tiers
             WHERE status = 1 AND min_total_spent > ? AND min_total_spent > 0
             ORDER BY min_total_spent ASC LIMIT 1',
            [$spent]
        );
    }

    // ---------------- Đánh giá sản phẩm ----------------
    public static function addReview(int $userId, array $data): array
    {
        $product = Product::find((int)$data['product_id']);
        if (!$product) {
            return ['ok' => false, 'message' => 'Sản phẩm không tồn tại.'];
        }
        if (static::first('SELECT id FROM reviews WHERE user_id = ? AND product_id = ? LIMIT 1', [$userId, $product['id']])) {
            return ['ok' => false, 'message' => 'Bạn đã đánh giá sản phẩm này.'];
        }
        if (!static::canReview($userId, (int)$product['id'])) {
            return ['ok' => false, 'message' => 'Chỉ đánh giá được sau khi nhận hàng thành công.'];
        }
        $stmt = static::db()->prepare(
            'INSERT INTO reviews (product_id, user_id, rating, title, content, status) VALUES (?, ?, ?, ?, ?, "pending")'
        );
        if (!$stmt->execute([$product['id'], $userId, (int)$data['rating'], trim($data['title'] ?? ''), trim($data['content'] ?? '')])) {
            return ['ok' => false, 'message' => 'Không thể lưu đánh giá.'];
        }
        return ['ok' => true, 'id' => (int)static::db()->lastInsertId()];
    }
}