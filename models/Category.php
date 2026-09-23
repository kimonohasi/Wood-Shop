<?php
/**
 * WoodCon - Model Danh mục sản phẩm
 */

declare(strict_types=1);

namespace WoodCon;

class Category extends Base
{
    protected static string $table = 'categories';

    /** Danh mục cấp cha đang hoạt động */
    public static function getParents(): array
    {
        return static::where('parent_id IS NULL AND status = 1', [], '*', 'sort_order ASC, id ASC');
    }

    /** Danh mục con của một cha */
    public static function getChildren(int $parentId): array
    {
        return static::where('parent_id = ? AND status = 1', [$parentId], '*', 'sort_order ASC, id ASC');
    }

    /** Cây danh mục 2 cấp cho menu/giao diện */
    public static function getTree(): array
    {
        $tree = [];
        foreach (static::getParents() as $parent) {
            $parent['children'] = static::getChildren((int)$parent['id']);
            $tree[] = $parent;
        }
        return $tree;
    }

    public static function bySlug(string $slug): ?array
    {
        return static::first('SELECT * FROM categories WHERE slug = ? AND status = 1 LIMIT 1', [$slug]);
    }

    /** Đếm sản phẩm đang hoạt động trong danh mục (gồm cả danh mục con) */
    public static function countProducts(int $categoryId): int
    {
        $children = static::getChildren($categoryId);
        $ids = [$categoryId];
        foreach ($children as $c) {
            $ids[] = (int)$c['id'];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = static::db()->prepare("SELECT COUNT(*) AS c FROM products WHERE category_id IN ({$in}) AND status = 1");
        $stmt->execute($ids);
        return (int)$stmt->fetch()['c'];
    }
}