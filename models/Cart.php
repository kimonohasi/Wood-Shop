<?php
/**
 * WoodCon - Model Giỏ hàng
 * Hỗ trợ khách vãng lai (session_id) và khách đã đăng nhập (user_id).
 */

declare(strict_types=1);

namespace WoodCon;

class Cart extends Base
{
    protected static string $table = 'carts';

    /** Lấy key định danh giỏ hàng hiện tại */
    public static function identity(): array
    {
        $ident = ['user_id' => null, 'session_id' => null];
        if (isset($_SESSION['user_id'])) {
            $ident['user_id'] = $_SESSION['user_id'];
        } else {
            if (empty($_SESSION['cart_token'])) {
                $_SESSION['cart_token'] = bin2hex(random_bytes(16));
            }
            $ident['session_id'] = $_SESSION['cart_token'];
        }
        return $ident;
    }

    /** Thêm sản phẩm vào giỏ (nếu đã có biến thể giống nhau thì cộng dồn) */
    public static function add(int $productId, int $quantity = 1, ?int $variantId = null): bool
    {
        $product = Product::find($productId);
        if (!$product || !$product['status']) {
            return false;
        }
        $price = Product::effectivePrice($product);
        $ident = static::identity();
        $userId = $ident['user_id'];
        $sessionId = $ident['session_id'];

        $existing = static::first(
            'SELECT * FROM carts WHERE ' . ($userId ? 'user_id = ?' : 'session_id = ?') .
            ' AND product_id = ? AND variant_id <=> ? LIMIT 1',
            $userId ? [$userId, $productId, $variantId] : [$sessionId, $productId, $variantId]
        );

        if ($existing) {
            static::update((int)$existing['id'], [
                'quantity'       => $existing['quantity'] + $quantity,
                'price_snapshot' => $price,
            ]);
        } else {
            static::insert([
                'user_id'        => $userId,
                'session_id'     => $sessionId,
                'product_id'     => $productId,
                'variant_id'     => $variantId,
                'quantity'       => $quantity,
                'price_snapshot' => $price,
            ]);
        }
        return true;
    }

    /** Cập nhật số lượng (giới hạn tồn kho) */
    public static function updateQty(int $cartId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return static::delete($cartId);
        }
        $row = static::find($cartId);
        if (!$row) {
            return false;
        }
        $maxStock = static::stockAvailable($row);
        $quantity = min($quantity, max(1, $maxStock));
        return static::update($cartId, ['quantity' => $quantity]);
    }

    /** Kiểm tra số lượng tồn kho còn lại của dòng giỏ */
    public static function stockAvailable(array $row): int
    {
        $left = (int)$row['quantity'];
        $product = Product::find((int)$row['product_id']);
        if ($product) {
            $stock = (int)$product['quantity'];
            if (!empty($row['variant_id'])) {
                $variant = static::first('SELECT stock FROM product_variants WHERE id = ?', [$row['variant_id']]);
                if ($variant) {
                    $stock = min($stock, (int)$variant['stock']);
                }
            }
            $left = $stock;
        }
        return max(0, $left);
    }

    public static function remove(int $cartId): bool
    {
        $ident = static::identity();
        $row = static::find($cartId);
        if (!$row) {
            return false;
        }
        if ($row['user_id'] !== $ident['user_id'] || ($row['user_id'] === null && $row['session_id'] !== $ident['session_id'])) {
            return false;
        }
        return static::delete($cartId);
    }

    /** Danh sách dòng giỏ kèm thông tin sản phẩm */
    public static function items(): array
    {
        $ident = static::identity();
        $userId = $ident['user_id'];
        $sessionId = $ident['session_id'];
        $sql = "SELECT c.*, p.name AS product_name, p.slug, p.cover_image, p.price, p.sale_price,
                       COALESCE(c.price_snapshot, p.sale_price, p.price) AS unit_price,
                       p.quantity AS product_stock, v.name AS variant_name, v.value AS variant_value, v.stock AS variant_stock
                FROM carts c
                JOIN products p ON p.id = c.product_id
                LEFT JOIN product_variants v ON v.id = c.variant_id
                WHERE " . ($userId ? 'c.user_id = ?' : 'c.session_id = ?') . "
                ORDER BY c.id DESC";
        return static::query($sql, $userId ? [$userId] : [$sessionId]);
    }

    /** Tổng số loại sản phẩm trong giỏ (cho badge) */
    public static function countItems(): int
    {
        $ident = static::identity();
        if ($ident['user_id']) {
            return (int)static::count('user_id = ?', [$ident['user_id']]);
        }
        return (int)static::count('session_id = ?', [$ident['session_id']]);
    }

    /** Tổng tiền hàng hóa (tạm tính) của giỏ hiện tại */
    public static function gross(): float
    {
        $sum = 0.0;
        foreach (static::items() as $it) {
            $sum += (float)$it['unit_price'] * (int)$it['quantity'];
        }
        return $sum;
    }

    /** Gộp giỏ khách vãng lai vào tài khoản sau khi đăng nhập */
    public static function mergeGuestToUser(int $userId, ?string $guestToken = null): void
    {
        if (!$guestToken) {
            return;
        }
        $guests = static::where('session_id = ?', [$guestToken]);
        foreach ($guests as $guest) {
            $existing = static::first(
                'SELECT * FROM carts WHERE user_id = ? AND product_id = ? AND variant_id <=> ? LIMIT 1',
                [$userId, $guest['product_id'], $guest['variant_id']]
            );
            if ($existing) {
                static::update((int)$existing['id'], ['quantity' => $existing['quantity'] + (int)$guest['quantity']]);
                static::delete((int)$guest['id']);
            } else {
                static::update((int)$guest['id'], ['user_id' => $userId, 'session_id' => null]);
            }
        }
    }

    /** Xoá toàn bộ giỏ sau khi đặt hàng */
    public static function clear(): void
    {
        $ident = static::identity();
        if ($ident['user_id']) {
            static::deleteWhere('user_id = ?', [$ident['user_id']]);
        } else {
            static::deleteWhere('session_id = ?', [$ident['session_id']]);
        }
    }
}