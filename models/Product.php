<?php
/**
 * WoodCon - Model Sản phẩm
 * Bao gồm tìm kiếm/bộ lọc theo PROMPT_MASTER (tên, giá, danh mục, biến thể, khuyến mãi).
 */

declare(strict_types=1);

namespace WoodCon;

class Product extends Base
{
    protected static string $table = 'products';

    /** Giá hiệu lực của sản phẩm (ưu tiên giá khuyến mãi) */
    public static function effectivePrice(array $product): float
    {
        if (!empty($product['sale_price']) && (float)$product['sale_price'] > 0 && (float)$product['sale_price'] < (float)$product['price']) {
            return (float)$product['sale_price'];
        }
        return (float)$product['price'];
    }

    public static function bySlug(string $slug): ?array
    {
        return static::first('SELECT * FROM products WHERE slug = ? AND status = 1 LIMIT 1', [$slug]);
    }

    /** Id danh mục của sản phẩm (phục vụ kiểm tra scope voucher) */
    public static function categoryIdOf(int $productId): ?int
    {
        $row = static::first('SELECT category_id FROM products WHERE id = ? LIMIT 1', [$productId]);
        return $row ? (int)$row['category_id'] : null;
    }

    public static function images(int $productId): array
    {
        return static::query('SELECT id, image, sort_order FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC', [$productId]);
    }

    /** Thêm 1 ảnh phụ vào gallery (nối vào cuối danh sách) */
    public static function addImage(int $productId, string $image): void
    {
        $next = (int)static::first('SELECT COALESCE(MAX(sort_order),0)+1 AS n FROM product_images WHERE product_id = ?', [$productId])['n'];
        static::db()->prepare('INSERT INTO product_images (product_id, image, sort_order) VALUES (?,?,?)')->execute([$productId, $image, $next]);
    }

    /** Xóa 1 ảnh phụ khỏi gallery theo id */
    public static function removeImage(int $id): bool
    {
        return (bool)static::db()->prepare('DELETE FROM product_images WHERE id = ?')->execute([$id]);
    }

    public static function variants(int $productId): array
    {
        return static::query('SELECT * FROM product_variants WHERE product_id = ? ORDER BY id ASC', [$productId]);
    }

    /** Thêm 1 biến thể */
    public static function addVariant(array $d): int
    {
        $stmt = static::db()->prepare(
            'INSERT INTO product_variants (product_id, name, value, image_url, price_adjust, stock, sku)
             VALUES (?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $d['product_id'],
            $d['name'] ?? 'Màu sắc',
            $d['value'] ?? '',
            $d['image_url'] ?? null,
            $d['price_adjust'] ?? 0,
            $d['stock'] ?? 0,
            $d['sku'] ?? null,
        ]);
        return (int)static::db()->lastInsertId();
    }

    /** Cập nhật 1 biến thể (image_url null = giữ nguyên ảnh cũ) */
    public static function updateVariant(int $id, array $d): void
    {
        $set = ['name = ?, value = ?, price_adjust = ?, stock = ?, sku = ?'];
        $args = [
            $d['name'] ?? 'Màu sắc',
            $d['value'] ?? '',
            $d['price_adjust'] ?? 0,
            $d['stock'] ?? 0,
            $d['sku'] ?? null,
        ];
        if (!empty($d['image_url'])) {
            $set[] = 'image_url = ?';
            $args[] = $d['image_url'];
        }
        static::db()->prepare('UPDATE product_variants SET ' . implode(', ', $set) . ' WHERE id = ?')->execute(array_merge($args, [$id]));
    }

    /** Xóa toàn bộ biến thể của 1 sản phẩm (dùng khi ghép danh sách mới) */
    public static function deleteVariants(int $productId, array $keepIds = []): void
    {
        if ($keepIds) {
            $ph = implode(',', array_fill(0, count($keepIds), '?'));
            static::db()->prepare("DELETE FROM product_variants WHERE product_id = ? AND id NOT IN ($ph)")->execute(array_merge([$productId], $keepIds));
        } else {
            static::db()->prepare('DELETE FROM product_variants WHERE product_id = ?')->execute([$productId]);
        }
    }

    public static function videos(int $productId): array
    {
        return static::query('SELECT video_url FROM product_videos WHERE product_id = ?', [$productId]);
    }

    /** Tăng lượt xem */
    public static function bumpView(int $id): void
    {
        static::db()->prepare('UPDATE products SET view_count = view_count + 1 WHERE id = ?')->execute([$id]);
    }

    /** Sản phẩm nổi bật */
    public static function featured(int $limit = 8): array
    {
        return static::where('status = 1 AND is_featured = 1', [], '*', 'sold_count DESC, id DESC', $limit);
    }

    /** Sản phẩm bán chạy */
    public static function bestSellers(int $limit = 8): array
    {
        return static::where('status = 1 AND is_best_seller = 1', [], '*', 'sold_count DESC, id DESC', $limit);
    }

    /** Sản phẩm mới */
    public static function newest(int $limit = 8): array
    {
        return static::where('status = 1 AND is_new = 1', [], '*', 'created_at DESC, id DESC', $limit);
    }

    /** Flash sale: sản phẩm đang giảm giá */
    public static function flashSale(int $limit = 8): array
    {
        return static::where('status = 1 AND sale_price IS NOT NULL AND sale_price > 0 AND sale_price < price', [], '*', 'sold_count DESC', $limit);
    }

    /** Sản phẩm liên quan cùng danh mục */
    public static function related(int $categoryId, int $excludeId, int $limit = 4): array
    {
        return static::query(
            'SELECT * FROM products WHERE status = 1 AND category_id = ? AND id <> ? ORDER BY is_featured DESC, sold_count DESC LIMIT ?',
            [$categoryId, $excludeId, $limit]
        );
    }

/** Khoảng giá [min, max] của sản phẩm đang hoạt động (theo giá hiệu lực). */
    public static function priceRange(): array
    {
        $row = static::first(
            "SELECT
                COALESCE(MIN(CASE WHEN sale_price IS NOT NULL AND sale_price > 0 AND sale_price < price THEN sale_price ELSE price END), 0) AS pmin,
                COALESCE(MAX(CASE WHEN sale_price IS NOT NULL AND sale_price > 0 AND sale_price < price THEN sale_price ELSE price END), 0) AS pmax
             FROM products
             WHERE status = 1"
        );
        $min = (int)($row['pmin'] ?? 0);
        $max = (int)($row['pmax'] ?? 0);
        // Làm tròn max lên bội số đẹp (5/10/20/50/100 triệu)
        $max = self::roundUpPrice($max);
        return ['min' => 0, 'max' => $max];
    }

    /** Làm tròn lên bội số đẹp: 1.2tr → 5tr, 13tr → 15tr, 47tr → 50tr, 73tr → 100tr, 130tr → 200tr, ... */
    public static function roundUpPrice(int $max): int
    {
        if ($max <= 0) return 10000000; // mặc định 10tr nếu chưa có SP
        // Thêm ~15% buffer để slider có khoảng trống phía trên
        $max = (int)ceil($max * 1.15);
        // Chọn step làm tròn theo độ lớn
        if ($max <= 5_000_000) {
            $step = 1_000_000;
        } elseif ($max <= 20_000_000) {
            $step = 5_000_000;
        } elseif ($max <= 50_000_000) {
            $step = 10_000_000;
        } elseif ($max <= 100_000_000) {
            $step = 20_000_000;
        } else {
            $step = 50_000_000;
        }
        return (int)ceil($max / $step) * $step;
    }

    /**
     * Tìm kiếm + bộ lọc nâng cao.
     * @param array $f q=tên, cat=danh mục, brand=thương hiệu, min_price, max_price,
     *                sort=new|price_asc|price_desc|best|sale, variants=[], page, per_page
     * @return array{total:int, rows:array, pager:array}
     */
    public static function search(array $f = []): array
    {
        $where = ['p.status = 1'];
        $params = [];

        if (!empty($f['q'])) {
            // Khớp cả tiếng Việt có dấu lẫn không dấu (qua cột chuẩn hoá tu_khoa_tim_kiem).
            $where[] = '(p.name LIKE ? OR p.summary LIKE ? OR p.sku LIKE ? OR p.tu_khoa_tim_kiem LIKE ?)';
            $q = (string)$f['q'];
            // Giới hạn 100 ký tự + escape các ký tự đặc biệt của LIKE
            $q = mb_substr($q, 0, 100, 'UTF-8');
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $likeNoAccent = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], str_lower_no_accent($q)) . '%';
            array_push($params, $like, $like, $like, $likeNoAccent);
        }
        // Gộp tất cả cat được chọn (cha + con) vào 1 mảng, sau đó mở rộng
        // mỗi cha thành tất cả con của nó để không bỏ sót sản phẩm.
        $__catRaw = [];
        if (!empty($f['cat'])) {
            $__catRaw = array_merge($__catRaw, (array)$f['cat']);
        }
        if (!empty($f['cat_ids']) && is_array($f['cat_ids'])) {
            $__catRaw = array_merge($__catRaw, $f['cat_ids']);
        }
        $__catRaw = array_values(array_unique(array_map('intval', $__catRaw)));
        $__catRaw = array_values(array_filter($__catRaw, fn($x) => $x > 0));
        if (!empty($__catRaw)) {
            $__place = implode(',', array_fill(0, count($__catRaw), '?'));
            $__expand = static::db()->prepare(
                "SELECT id FROM categories WHERE id IN ($__place) OR parent_id IN ($__place)"
            );
            $__expand->execute(array_merge($__catRaw, $__catRaw));
            $__allIds = array_map(fn($r) => (int)$r['id'], $__expand->fetchAll());
            $__allIds = array_values(array_unique($__allIds));
            if (!empty($__allIds)) {
                $__place2 = implode(',', array_fill(0, count($__allIds), '?'));
                $where[] = 'p.category_id IN (' . $__place2 . ')';
                foreach ($__allIds as $__iid) $params[] = $__iid;
            }
        }
        if (!empty($f['brand_ids']) && is_array($f['brand_ids'])) {
            $__brands = array_values(array_unique(array_map('intval', array_filter($f['brand_ids'], fn($x) => (int)$x > 0))));
            if (!empty($__brands)) {
                $__place = implode(',', array_fill(0, count($__brands), '?'));
                $where[] = 'p.brand_id IN (' . $__place . ')';
                foreach ($__brands as $__bid) $params[] = $__bid;
            }
        } elseif (!empty($f['brand'])) {
            $where[] = 'p.brand_id = ?';
            $params[] = (int)$f['brand'];
        }
        if (isset($f['min_price']) && $f['min_price'] !== null && $f['min_price'] !== '' && (float)$f['min_price'] > 0) {
            $where[] = 'COALESCE(p.sale_price, p.price) >= ?';
            $params[] = (float)$f['min_price'];
        }
        if (isset($f['max_price']) && $f['max_price'] !== null && $f['max_price'] !== '' && (float)$f['max_price'] > 0) {
            $where[] = 'COALESCE(p.sale_price, p.price) <= ?';
            $params[] = (float)$f['max_price'];
        }
        if (!empty($f['sale_only'])) {
            $where[] = 'p.sale_price IS NOT NULL AND p.sale_price > 0 AND p.sale_price < p.price';
        }
        if (!empty($f['is_new'])) {
            $where[] = 'p.is_new = 1';
        }
        if (!empty($f['is_best_seller'])) {
            $where[] = 'p.is_best_seller = 1';
        }

        $sortMap = [
            'new'        => 'p.created_at DESC, p.id DESC',
            'price_asc'  => 'COALESCE(p.sale_price, p.price) ASC',
            'price_desc' => 'COALESCE(p.sale_price, p.price) DESC',
            'best'       => 'p.sold_count DESC',
            'sale'       => 'p.sale_price IS NOT NULL DESC, p.sold_count DESC',
        ];
        $orderBy = $sortMap[$f['sort'] ?? 'new'] ?? $sortMap['new'];

        $whereSql = implode(' AND ', $where);

        // COUNT tổng số phù hợp (độc lập phân trang)
        $stmt = static::db()->prepare("SELECT COUNT(*) AS c FROM products p WHERE {$whereSql}");
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['c'];

        // Phân trang
        $perPage = max(1, min(48, (int)($f['per_page'] ?? 12)));
        $pager = paginate($total, $perPage, (int)($f['page'] ?? 1));

        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN brands b ON b.id = p.brand_id
                WHERE {$whereSql} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$pager['offset']}";

        $rows = static::query($sql, $params);

        return ['total' => $total, 'rows' => $rows, 'pager' => $pager];
    }

    /** Đánh giá đã duyệt của sản phẩm */
    public static function reviewsOf(int $productId): array
    {
        return static::query(
            'SELECT r.id, r.rating, r.title, r.content, r.created_at, u.name AS user_name
             FROM reviews r
             JOIN users u ON u.id = r.user_id
             WHERE r.product_id = ? AND r.status = "approved"
             ORDER BY r.created_at DESC',
            [$productId]
        );
    }

    /** Thống kê điểm trung bình + số lượng đánh giá đã duyệt */
    public static function reviewStats(int $productId): array
    {
        $row = static::first(
            'SELECT COUNT(*) AS total, COALESCE(AVG(rating),0) AS avg
             FROM reviews WHERE product_id = ? AND status = "approved"',
            [$productId]
        );
        return ['total' => (int)$row['total'], 'avg' => round((float)$row['avg'], 1)];
    }

    /**
     * Gợi ý tìm kiếm nhanh (dropdown): ưu tiên sản phẩm hot/bán chạy.
     * @return array[] mỗi phần tử gồm id, name, slug, price, sale_price, image
     */
    public static function suggest(string $q = '', int $limit = 8): array
    {
        $where = ['status = 1'];
        $params = [];

        $q = mb_substr(trim((string)$q), 0, 100, 'UTF-8');
        if ($q !== '') {
            $where[] = '(name LIKE ? OR summary LIKE ? OR sku LIKE ?)';
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            array_push($params, $like, $like, $like);
        }

        $limit = max(1, min(20, $limit));
        $whereSql = implode(' AND ', $where);

        // 1. Ưu tiên bán chạy/hot → các sản phẩm trùng từ khoá sau đó
        $orderBy = 'is_best_seller DESC, sold_count DESC, view_count DESC, id DESC';

        return static::query(
            "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.cover_image AS image
             FROM products p
             WHERE {$whereSql}
             ORDER BY {$orderBy} LIMIT {$limit}",
            $params
        );
    }
}