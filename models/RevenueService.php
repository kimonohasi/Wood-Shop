<?php
/**
 * WoodCon - Dịch vụ tính Doanh thu (nguồn duy nhất cho MỌI chỉ số doanh thu).
 *
 * Quy ước thống nhất toàn hệ thống:
 *   - "Doanh thu" luôn = SUM(orders.total_amount) của một BỘ TRẠNG THÁI đơn.
 *   - Cột mốc thời gian (dateCol):
 *       delivered_at  → doanh thu "thực" (đơn GIAO THÀNH CÔNG), NHẬN DIỆN theo ngày giao.
 *       created_at    → doanh thu "tiềm năng" (đơn chưa hủy/trả) theo ngày đặt.
 *   - Mọi nơi hiển thị doanh thu (Dashboard, Báo cáo, biểu đồ, export) PHẢI gọi
 *     qua service này, KHÔNG tự viết công thức SUM riêng.
 *
 * Scope trạng thái (định nghĩa duy nhất, dùng chung):
 *   - 'delivered' : chỉ đơn đã giao thành công  → doanh thu thực.
 *   - 'active'    : mọi đơn chưa hủy / chưa trả hàng (kể cả đang xử lý) → doanh thu tiềm năng.
 *   - 'all'       : mọi đơn, không loại trừ gì (chỉ để thống kê tổng giá trị).
 */

declare(strict_types=1);

namespace WoodCon;

class RevenueService extends Base
{
    /** Cột mốc thời gian dùng để lọc/group */
    public const COL_CREATED  = 'created_at';
    public const COL_DELIVERED = 'delivered_at';

    /** Số bucket mặc định theo kỳ của biểu đồ (cửa sổ xu hướng, KHÔNG phải con số kỳ hiện tại). */
    public const BUCKET_COUNTS = ['day' => 7, 'week' => 8, 'month' => 12, 'year' => 5];

    /** Bộ trạng thái đơn hàng theo scope — NGUỒN DUY NHẤT. */
    public static function scopeStatuses(string $scope): array
    {
        return match ($scope) {
            'active' => [
                'pending', 'manual_verifying', 'confirmed', 'preparing',
                'shipping', 'delivery_failed', 'delivered',
            ],
            'all'    => [
                'pending', 'manual_verifying', 'confirmed', 'preparing',
                'shipping', 'delivery_failed', 'delivered', 'returned', 'cancelled',
            ],
            default  => ['delivered'],
        };
    }

    /** Cột mốc hợp lệ (chống injection khi xếp vào SQL). */
    public static function dateCol(?string $col): string
    {
        return $col === self::COL_DELIVERED ? 'delivered_at' : 'created_at';
    }

    /**
     * Mệnh đề WHERE chuẩn: lọc theo bộ trạng thái + khoảng ngày theo cột mốc.
     * @return array{0:string,1:array} [whereSql, params]
     */
    public static function buildWhere(string $scope, ?string $col = null, ?string $from = null, ?string $to = null): array
    {
        $statuses = self::scopeStatuses($scope);
        $conds = ['order_status IN (' . implode(',', array_fill(0, count($statuses), '?')) . ')'];
        $params = $statuses;

        $c = self::dateCol($col);
        if ($from !== null && $from !== '') {
            $conds[] = "{$c} >= ?";
            $params[] = $from . ' 00:00:00';
        }
        if ($to !== null && $to !== '') {
            $conds[] = "{$c} <= ?";
            $params[] = $to . ' 23:59:59';
        }
        return [implode(' AND ', $conds), $params];
    }

    /** Biểu thức SQL group theo kỳ (day/week/month/quarter/year) trên cột mốc đã chọn. */
    public static function groupExpr(string $group, ?string $col = null): string
    {
        $c = self::dateCol($col);
        return match ($group) {
            'day'     => "DATE({$c})",
            'week'    => "DATE(DATE_SUB({$c}, INTERVAL WEEKDAY({$c}) DAY))",
            'month'   => "DATE_FORMAT({$c}, '%Y-%m')",
            'quarter' => "CONCAT(YEAR({$c}), '-Q', QUARTER({$c}))",
            'year'    => "DATE_FORMAT({$c}, '%Y')",
            default   => "DATE({$c})",
        };
    }

    /**
     * Tổng doanh thu (1 số) của bộ trạng thái trong khoảng thời gian.
     * @return float
     */
    public static function total(string $scope, ?string $col = null, ?string $from = null, ?string $to = null): float
    {
        [$where, $params] = self::buildWhere($scope, $col, $from, $to);
        $row = static::query(
            "SELECT COALESCE(SUM(total_amount),0) AS t FROM orders WHERE {$where}",
            $params
        )[0];
        return (float)$row['t'];
    }

    /**
     * Doanh thu theo kỳ: [{period, orders, total}] theo group (day/week/month/quarter/year).
     * period trả về dạng chuẩn của groupExpr (vd Y-m-d / Y-m / Y).
     * @return array<int, array{period:string, orders:int, total:float}>
     */
    public static function series(string $scope, string $group, ?string $col = null, ?string $from = null, ?string $to = null): array
    {
        [$where, $params] = self::buildWhere($scope, $col, $from, $to);
        $gex = self::groupExpr($group, $col);
        $rows = static::query(
            "SELECT {$gex} AS period, COUNT(*) AS orders, COALESCE(SUM(total_amount),0) AS total
             FROM orders WHERE {$where} GROUP BY period ORDER BY period",
            $params
        );
        foreach ($rows as &$r) {
            $r['orders'] = (int)$r['orders'];
            $r['total'] = (float)$r['total'];
        }
        unset($r);
        return $rows;
    }

    /**
     * Chuỗi bucket LIÊN TỤC từ hôm nay lùi về trước (gồm cả khoảng trống chưa có đơn) —
     * dùng để vẽ biểu đồ, mốc `from` luôn khớp bucket đầu tiên.
     * @return array{from:string, labels:array<int,string>, values:array<int,float>}
     */
    public static function buckets(string $scope, string $period, ?string $col = null, int $count = 7): array
    {
        $count = max(1, $count);
        $now = new \DateTime('today');
        $labels = [];
        $keys = [];
        for ($i = $count - 1; $i >= 0; $i--) {
            switch ($period) {
                case 'week':
                    $ws = (clone $now)->modify('-' . $i . ' weeks')->modify('monday this week');
                    $we = (clone $ws)->modify('+6 days');
                    $labels[] = $ws->format('d/m') . '-' . $we->format('d/m');
                    $keys[] = $ws->format('Y-m-d');
                    break;
                case 'month':
                    $m = (clone $now)->modify('-' . $i . ' months');
                    $labels[] = 'T' . (int)$m->format('n');
                    $keys[] = $m->format('Y-m');
                    break;
                case 'year':
                    $labels[] = (string)((int)$now->format('Y') - $i);
                    $keys[] = (string)((int)$now->format('Y') - $i);
                    break;
                default: // day
                    $d = (clone $now)->modify('-' . $i . ' days');
                    $labels[] = $d->format('d/m');
                    $keys[] = $d->format('Y-m-d');
            }
        }

        $from = match ($period) {
            'day', 'week' => $keys[0] . ' 00:00:00',
            'month'       => $keys[0] . '-01 00:00:00',
            'year'        => $keys[0] . '-01-01 00:00:00',
            default       => $keys[0] . ' 00:00:00',
        };

        $rows = self::series($scope, $period, $col, $from);
        $buckets = array_fill_keys($keys, 0.0);
        foreach ($rows as $r) {
            $k = match ($period) {
                'day'   => $r['period'],
                'week'  => (new \DateTime($r['period']))->format('Y-m-d'),
                'month' => $r['period'],
                'year'  => (string)(int)$r['period'],
                default => $r['period'],
            };
            if (array_key_exists($k, $buckets)) {
                $buckets[$k] = (float)$r['total'];
            }
        }

        return [
            'from'   => $from,
            'labels' => $labels,
            'values' => array_values($buckets),
        ];
    }

    /**
     * Tổng doanh thu ĐÚNG MỘT kỳ hiện tại (đơn vị thời gian đang chọn) —
     * KHÁC với tổng cửa sổ biểu đồ:
     *   day → hôm nay, week → thứ 2 tuần này→hôm nay, month → đầu tháng→hôm nay, year → đầu năm→hôm nay.
     * @return array{label:string, from:string, to:string, total:float}
     */
    public static function currentPeriodTotal(string $scope, string $period, ?string $col = null): array
    {
        $today = new \DateTime('today');
        $to = $today->format('Y-m-d');
        [$from, $label] = match ($period) {
            'week'  => [(clone $today)->modify('monday this week')->format('Y-m-d'), 'Tuần này'],
            'month' => [$today->format('Y-m-01'), 'Tháng này'],
            'year'  => [$today->format('Y-01-01'), 'Năm nay'],
            default => [$today->format('Y-m-d'), 'Hôm nay'],
        };
        return [
            'label' => $label,
            'from'  => $from,
            'to'    => $to,
            'total' => self::total($scope, $col, $from, $to),
        ];
    }
}