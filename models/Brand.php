<?php
/**
 * WoodCon - Model Thương hiệu
 */

declare(strict_types=1);

namespace WoodCon;

class Brand extends Base
{
    protected static string $table = 'brands';

    public static function active(): array
    {
        return static::where('status = 1', [], '*', 'name ASC');
    }

    public static function bySlug(string $slug): ?array
    {
        return static::first('SELECT * FROM brands WHERE slug = ? AND status = 1 LIMIT 1', [$slug]);
    }
}