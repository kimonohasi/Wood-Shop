<?php
/**
 * WoodCon - Model Tin tức
 */

declare(strict_types=1);

namespace WoodCon;

class News extends Base
{
    protected static string $table = 'news';

    public static function latest(int $limit = 3): array
    {
        return static::where('status = 1', [], '*', 'created_at DESC', $limit);
    }

    public static function bySlug(string $slug): ?array
    {
        return static::first('SELECT * FROM news WHERE slug = ? AND status = 1 LIMIT 1', [$slug]);
    }

    public static function paginated(int $page = 1, int $perPage = 9): array
    {
        $total = (int)static::count('status = 1');
        $pager = paginate($total, $perPage, $page);
        $rows = static::where('status = 1', [], '*', 'created_at DESC', $perPage, $pager['offset']);
        return ['total' => $total, 'rows' => $rows, 'pager' => $pager];
    }
}