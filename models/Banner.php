<?php
/**
 * WoodCon - Model Banner / Slider
 */

declare(strict_types=1);

namespace WoodCon;

class Banner extends Base
{
    protected static string $table = 'banners';

    public static function byPosition(string $position, int $limit = 8): array
    {
        return static::where(
            'position = ? AND status = 1',
            [$position],
            '*',
            'sort_order ASC, id ASC',
            $limit
        );
    }
}