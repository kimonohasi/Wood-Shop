<?php
/**
 * WoodCon - Dịch vụ tính phí vận chuyển (Module 2 loại - Phần 2)
 *
 * LOẠI 1 - "Giao hàng & lắp đặt tại nhà (nhân viên cửa hàng)":
 *   - Kho / trụ sở cấu hình 1 lần; khoảng cách 63 Tỉnh/Thành nhập tay (bảng province_distances).
 *   - Phí theo BẬC khoảng cách (bảng ship_fee_brackets, VD: 0-30km, 31-100km, 101-300km, >300km).
 *   - Phí lắp đặt riêng theo sản phẩm/danh mục (install_fee), chỉ khi khách chọn lắp đặt.
 *
* LOẠI 2 - "Vận chuyển qua Viettel Post (tự động tính cước qua API)":
 *   - Không còn bảng giá vùng nhập tay (carrier_rates chỉ giữ để tương thích dữ liệu cũ).
 *   - Cước = Viettel Post /v2/order/getPrice theo địa chỉ khách + khối lượng (xem ViettelPost::calculateForCity).
 *   - Ngưỡng miễn phí riêng: vtp_freeship_threshold (trống -> ngưỡng chung).
 *
 * Mỗi sản phẩm/danh mục bật/tắt 1 hoặc cả 2 loại (ship_supports_type1/type2).
 */

declare(strict_types=1);

namespace WoodCon;

class Shipping
{
    public const TYPE_SHOP    = 'shop';    // Loại 1: Giao hàng & lắp đặt tại nhà
    public const TYPE_CARRIER = 'carrier'; // Loại 2: Vận chuyển qua đơn vị vận chuyển

    /** Các vùng giao hàng (đơn giá cước Loại 2) hiển thị khi thanh toán */
    public static function zones(): array
    {
        return [
            'noithanh'  => 'Nội thành (TP. HCM)',
            'lientinh'  => 'Liên tỉnh gần',
            'lienmien'  => 'Liên miền / xa',
        ];
    }

    /** Nhãn hiển thị 2 loại vận chuyển (admin chỉnh được) */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_SHOP    => (string)get_setting('ship_label_type1', 'Giao hàng & lắp đặt tại nhà (nhân viên cửa hàng)'),
            self::TYPE_CARRIER => (string)get_setting('ship_label_type2', 'Vận chuyển qua Viettel Post'),
        ];
    }

    /** Tự dò vùng loại 2 theo thành phố (shop đặt tại TP. HCM) */
    public static function detectZone(?string $city): string
    {
        $city = mb_strtolower(trim((string)$city), 'UTF-8');
        $nearCities = ['hà nội', 'hải phòng', 'đà nẵng', 'cần thơ', 'biên hòa', 'vũng tàu', 'bình dương', 'long an', 'đồng nai', 'bến tre', 'tiền giang'];
        if ($city === '' || str_contains($city, 'hồ chí minh') || str_contains($city, 'hcm') || str_contains($city, 'sg')) {
            return 'noithanh';
        }
        foreach ($nearCities as $c) {
            if (str_contains($city, $c)) {
                return 'lientinh';
            }
        }
        return 'lienmien';
    }

    // ================================================================
    // PHÂN GIẢI HỖ TRỢ VẬN CHUYỂN (per sản phẩm / per giỏ)
    // ================================================================

    /**
     * Trả về tùy chọn vận chuyển + phí lắp đặt cho 1 sản phẩm.
     * @param array $product hàng products (phải có category_id)
     * @return array{support_type1:bool,support_type2:bool,install_fee:float}
     */
    public static function itemShippingOptions(array $product): array
    {
        $cat = null;
        if (!empty($product['category_id'])) {
            $cat = Category::find((int)$product['category_id']);
        }
        $t1 = $product['ship_supports_type1'] ?? null;
        $supportT1 = $t1 === null ? ((int)($cat['ship_supports_type1'] ?? 1) === 1) : ((int)$t1 === 1);
        $t2 = $product['ship_supports_type2'] ?? null;
        $supportT2 = $t2 === null ? ((int)($cat['ship_supports_type2'] ?? 1) === 1) : ((int)$t2 === 1);

$installFee = (float)($product['install_fee'] ?? 0);
        if ($installFee <= 0) {
            $installFee = (float)($cat['install_fee'] ?? 0);
        }
        // Bậc 3: phí lắp đặt mặc định toàn cửa hàng (chỉ áp dụng cho Loại 1).
        if ($installFee <= 0) {
            $installFee = (float)get_setting('ship_default_install_fee', 0);
        }

        return [
            'support_type1' => $supportT1,
            'support_type2' => $supportT2,
            'install_fee'   => max(0, $installFee),
        ];
    }

    /**
     * Phân giải tùy chọn vận chuyển cho TOÀN GIỎ HÀNG.
     * Loại chỉ khả dụng khi MỌI sản phẩm hỗ trợ loại đó.
     * @param array $items [['product_id','quantity','product'? ...], ...]
     * @return array{type1:bool,type2:bool,install_available:bool,install_fee:float,install_detail:array}
     */
    public static function cartShippingOptions(array $items): array
    {
        $t1 = true;
        $t2 = true;
        $installAvailable = false;
        $installFeeTotal = 0.0;
        $installDetail = [];

        foreach ($items as $item) {
            $p = $item['product'] ?? Product::find((int)($item['product_id'] ?? 0));
            if (!$p) {
                continue;
            }
            $opt = static::itemShippingOptions($p);
            $qty = max(1, (int)($item['quantity'] ?? 1));
            $t1 = $t1 && $opt['support_type1'];
            $t2 = $t2 && $opt['support_type2'];
            if ($opt['support_type1'] && $opt['install_fee'] > 0) {
                $installAvailable = true;
                $fee = $opt['install_fee'] * $qty;
                $installFeeTotal += $fee;
                $installDetail[] = [
                    'product_name' => (string)($p['name'] ?? ''),
                    'quantity'     => $qty,
                    'fee'          => $fee,
                ];
            }
        }

        // Loại 2 hiện tại do Viettel Post tự động tính cước tại checkout;
        // nếu tích hợp chưa bật/thiếu token -> ẩn hẳn Loại 2 (không để khách chọn rồi tính sai).
        if ($t2 && !\WoodCon\ViettelPost::configured()) {
            $t2 = false;
        }

        return [
            'type1'            => $t1,
            'type2'            => $t2,
            'install_available'=> $installAvailable,
            'install_fee'      => (float)$installFeeTotal,
            'install_detail'   => $installDetail,
        ];
    }

    // ================================================================
    // LOẠI 1 - GIAO HÀNG & LẮP ĐẶT TẠI NHÀ
    // ================================================================

    /** Danh sách 63 tỉnh/thành kèm khoảng cách (km) từ kho */
    public static function provinceDistances(): array
    {
        return Database::connect()->query('SELECT province, distance_km FROM province_distances ORDER BY sort_order, province')->fetchAll();
    }

    /** Tìm khoảng cách tỉnh theo tên thành phố khách nhập (không dấu, chứa - chứa) */
    public static function provinceDistance(?string $city): ?float
    {
        $city = str_lower_no_accent(trim((string)$city));
        if ($city === '') {
            return null;
        }
        $rows = static::provinceDistances();
        foreach ($rows as $row) {
            $p = str_lower_no_accent($row['province']);
            // Ưu tiên: tên tỉnh (chuẩn hóa) nằm trong chuỗi nhập, hoặc ngược lại
            if ($p !== '' && (str_contains($city, $p) || str_contains($p, $city))) {
                return (float)$row['distance_km'];
            }
        }
        return null;
    }

/** Bậc mức phí theo khoảng cách (đã sắp theo sort_order) */
    public static function feeBrackets(): array
    {
        return Database::connect()->query('SELECT id, min_km, max_km, fee, free_ship_threshold FROM ship_fee_brackets ORDER BY sort_order, min_km')->fetchAll();
    }

    /** Bậc phí Loại 1 khớp theo thành phố; không xác định được -> bậc xa nhất (an toàn) */
    public static function type1Bracket(?string $city): ?array
    {
        $dist = static::provinceDistance($city);
        $brackets = static::feeBrackets();
        if (!$brackets) {
            return null;
        }
        if ($dist !== null) {
            foreach ($brackets as $b) {
                $max = $b['max_km'] === null ? PHP_INT_MAX : (float)$b['max_km'];
                if ($dist >= (float)$b['min_km'] && $dist <= $max) {
                    return $b;
                }
            }
        }
        return $brackets[count($brackets) - 1];
    }

    /**
     * Phí Loại 1 (đồng): theo bậc khoảng cách từ kho tới tỉnh.
     * Không tìm được tỉnh -> lấy bậc xa nhất (an toàn).
     */
    public static function type1Fee(?string $city): float
    {
        $bracket = static::type1Bracket($city);
        return $bracket ? round((float)$bracket['fee']) : 0;
    }

    // ================================================================
    // LOẠI 2 - VẬN CHUYỂN QUA ĐƠN VỊ VẬN CHUYỂN
    // ================================================================

    /** Đơn giá cước theo vùng (Loại 2) */
    public static function carrierRates(): array
    {
        return Database::connect()->query('SELECT zone, label, base_fee, price_per_kg FROM carrier_rates ORDER BY id')->fetchAll();
    }

    public static function carrierRate(string $zone): ?array
    {
        $row = Database::connect()->prepare('SELECT * FROM carrier_rates WHERE zone = ? LIMIT 1');
        $row->execute([$zone]);
        $rate = $row->fetch() ?: null;
        return $rate ?: null;
    }

    /**
     * Phí Loại 2 (đồng): KL tính cước = max(KL thực, KL quy đổi); chia per sản phẩm theo SL.
     * Phí = phí cơ bản + KL tính cước x đơn giá/kg (làm tròn kg, tối thiểu 1kg).
     * @param array $items [['weight_kg','dim_l','dim_w','dim_h','quantity'], ...]
     */
    public static function carrierFee(array $items, string $zone = 'noithanh'): float
    {
        $rate = static::carrierRate($zone) ?: ['base_fee' => (float)get_setting('ship_min_fee', 20000), 'price_per_kg' => 0];
        $divisor = max(1, (float)get_setting('ship_volumetric_divisor', 6000));

        $weightVol = 0.0;
        $weightReal = 0.0;
        foreach ($items as $item) {
            $qty = max(1, (int)($item['quantity'] ?? 1));
            $real = (float)($item['weight_kg'] ?? 0) * $qty;
            $l = (float)($item['dim_l'] ?? 0);
            $w = (float)($item['dim_w'] ?? 0);
            $h = (float)($item['dim_h'] ?? 0);
            $vol = 0.0;
            if ($l > 0 && $w > 0 && $h > 0) {
                $vol = ($l * $w * $h) / $divisor * $qty;
            }
            $weightReal += $real;
            $weightVol += $vol;
        }

        $chargeable = max($weightReal, $weightVol, 1);
        $fee = (float)$rate['base_fee'] + $chargeable * (float)$rate['price_per_kg'];
        return round(max(0, $fee));
    }

    // ================================================================
    // CÔNG THỨC CŨ (giữ lại để tương thích; không còn dùng trong checkout mới)
    // ================================================================

    public static function calculate(array $items, string $zone = 'noithanh'): float
    {
        $divisor = max(1, (float)get_setting('ship_divisor', 5000));
        $unitPrice = match ($zone) {
            'lientinh' => (float)get_setting('ship_fee_lientinh', 45000),
            'lienmien' => (float)get_setting('ship_fee_lienmien', 65000),
            default    => (float)get_setting('ship_fee_noithanh', 25000),
        };
        $minFee = (float)get_setting('ship_min_fee', 20000);
        $minKm  = max(1, (float)get_setting('ship_min_km', 1));

        $weightVol = 0.0;
        $weightReal = 0.0;
        foreach ($items as $item) {
            $qty = max(1, (int)($item['quantity'] ?? 1));
            $real = (float)($item['weight_kg'] ?? 1) * $qty;
            $vol = 0.0;
            $l = (float)($item['dim_l'] ?? 0);
            $w = (float)($item['dim_w'] ?? 0);
            $h = (float)($item['dim_h'] ?? 0);
            if ($l > 0 && $w > 0 && $h > 0) {
                $vol = ($l * $w * $h) / $divisor * $qty;
            }
            $weightReal += $real;
            $weightVol += $vol;
        }
        $chargeable = max($weightReal, $weightVol);
        $metric = $chargeable * $minKm;
        $fee = $metric * $unitPrice;
        return round(max($minFee * $minKm, $fee), 0);
    }

/**
     * Ngưỡng miễn phí vận chuyển theo bậc (Loại 1) / riêng Loại 2 của đơn.
     * Ngưỡng riêng để trống hoặc = 0 -> dùng ngưỡng chung (setting free_ship_threshold) làm dự phòng.
     * @param string      $shippingType TYPE_SHOP | TYPE_CARRIER
     * @param string|null $city         Tỉnh/thành phố giao (Loại 1, dò bậc theo khoảng cách)
     * @param string      $zone         Vùng giao (chỉ còn dùng để tương thích; Loại 2 dùng ngưỡng riêng vtp_freeship_threshold)
     */
    public static function freeShipThreshold(string $shippingType, ?string $city, string $zone): float
    {
        $global = (float)get_setting('free_ship_threshold', 2000000);
        $threshold = 0.0;
        if ($shippingType === self::TYPE_SHOP) {
            $bracket = static::type1Bracket($city);
            $threshold = $bracket ? (float)($bracket['free_ship_threshold'] ?? 0) : 0.0;
        } else {
            $threshold = (float)get_setting('vtp_freeship_threshold', 0);
        }
        return $threshold > 0 ? $threshold : $global;
    }

    /** Kiểm tra đơn đạt ngưỡng miễn phí vận chuyển theo đúng bậc/loại đã chọn */
    public static function isFreeShipping(float $orderValue, string $shippingType, ?string $city, string $zone): bool
    {
        $threshold = static::freeShipThreshold($shippingType, $city, $zone);
        return $threshold > 0 && $orderValue >= $threshold;
    }
}
