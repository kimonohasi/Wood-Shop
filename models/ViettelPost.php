<?php
/**
 * WoodCon - Tích hợp Viettel Post cho LOẠI 2 vận chuyển (Module 2 loại - Phần 3)
 *
 * Thay thế bảng giá nhập tay theo vùng (carrier_rates) bằng gọi API Viettel Post
 * tự động tính cước thực tế khi khách chọn "Vận chuyển qua Viettel Post":
 *   - Bật/tắt tích hợp (vtp_enabled)
 *   - Token xác thực (vtp_token, mã hóa AES nếu có WOODCON_APP_KEY, không bao giờ trả về client)
 *   - Đăng nhập lấy Token tự động (vtp_username / vtp_password)
 *   - Kho gửi: Tỉnh/Quận lấy từ API danh mục (vtp_sender_province_id|name, vtp_sender_district_id|name)
 *   - Dịch vụ (vtp_service), Loại hàng (vtp_product_type), COD (vtp_cod_enabled)
 *   - Phụ thu nội bộ (vtp_surcharge), ngưỡng miễn phí riêng (vtp_freeship_threshold)
 *   - Log trạng thái gọi API gần nhất (vtp_last_call / vtp_last_status / vtp_last_message)
 *
 * Tài liệu API: https://partner.viettelpost.vn/v2
 *   GET  /v2/categories/listProvince            -> data[] {PROVINCE_ID, PROVINCE_NAME}
 *   GET  /v2/categories/listDistrict?provinceID -> data[] {DISTRICT_ID, DISTRICT_NAME}
 *   POST /v2/order/getPrice                     -> data {MONEY_TOTAL, ...}
 *   POST /v2/user/login (tuỳ chọn, lấy token tự động)
 */

declare(strict_types=1);

namespace WoodCon;

class ViettelPost
{
    public const API_BASE = 'https://partner.viettelpost.vn/v2';
    public const CACHE_TTL_HOURS = 24;

    /** Mã dịch vụ Viettel Post có thể chọn được ('' = tự động chọn rẻ nhất) */
    public const SERVICES = [
        ''    => 'Tự động (rẻ nhất)',
        'VCN' => 'Chuyển phát nhanh (VCN)',
        'VTK' => 'Chuyển phát tiết kiệm (VTK)',
        'SCN' => 'Chuyển phát nhanh (SCN)',
        'STK' => 'Chuyển phát tiêu chuẩn (STK)',
        'SHT' => 'Chuyển phát hỏa tốc (SHT)',
        'VHT' => 'Hỏa tốc (VHT)',
    ];

    /** Loại hàng */
    public const PRODUCT_TYPES = [
        'HH' => 'Hàng hóa (HH)',
        'HD' => 'Hóa đơn - Tài liệu (HD)',
    ];

    // ================================================================
    // CẤU HÌNH / TRẠNG THÁI
    // ================================================================

    public static function enabled(): bool
    {
        return (int)get_setting('vtp_enabled', 0) === 1;
    }

    /** Token xác thực đã giải mã (backend only — không bao giờ trả về client) */
    public static function token(): string
    {
        $raw = trim((string)get_setting('vtp_token', ''));
        return static::dec($raw);
    }

    public static function hasToken(): bool
    {
        return static::token() !== '';
    }

    /** Loại 2 sẵn sàng hiển thị ở trang thanh toán (bật + có token) */
    public static function configured(): bool
    {
        return static::enabled() && static::hasToken();
    }

    public static function sender(): array
    {
        return [
            'province_id'   => (int)get_setting('vtp_sender_province_id', 0),
            'province_name' => trim((string)get_setting('vtp_sender_province_name', '')),
            'district_id'   => (int)get_setting('vtp_sender_district_id', 0),
            'district_name' => trim((string)get_setting('vtp_sender_district_name', '')),
        ];
    }

    /** Trả về cấu hình mặc định an toàn để dựng form admin */
    public static function config(): array
    {
        $last = static::lastCallInfo();
        return [
            'enabled'             => static::enabled(),
            'has_token'           => static::hasToken(),
            'username'            => trim((string)get_setting('vtp_username', '')),
            'has_password'        => trim((string)get_setting('vtp_password', '')) !== '',
            'sender'              => static::sender(),
            'service'             => (string)get_setting('vtp_service', ''),
            'product_type'        => (string)get_setting('vtp_product_type', 'HH'),
            'cod_enabled'         => (int)get_setting('vtp_cod_enabled', 1) === 1,
            'surcharge'           => (float)get_setting('vtp_surcharge', 0),
            'freeship_threshold'  => (float)get_setting('vtp_freeship_threshold', 0),
            'volumetric_divisor'  => (float)get_setting('ship_volumetric_divisor', 6000),
            'label'               => (string)get_setting('ship_label_type2', 'Vận chuyển qua Viettel Post'),
            'last'                => $last,
        ];
    }

    /** Trạng thái log gọi API gần nhất (hiển thị admin để debug) */
    public static function lastCallInfo(): array
    {
        return [
            'time'    => (string)get_setting('vtp_last_call', ''),
            'status'  => (string)get_setting('vtp_last_status', ''),
            'message' => (string)get_setting('vtp_last_message', ''),
        ];
    }

    public static function setLastCall(string $status, string $message): void
    {
        set_setting('vtp_last_call', date('Y-m-d H:i:s'), 'shipping');
        set_setting('vtp_last_status', $status, 'shipping');
        set_setting('vtp_last_message', mb_substr($message, 0, 500), 'shipping');
    }

    // ================================================================
    // MÃ HÓA TOKEN (backend): AES-256-CBC nếu có WOODCON_APP_KEY
    // ================================================================

    private static function tokenKey(): ?string
    {
        $k = getenv('WOODCON_APP_KEY');
        if ($k === false || $k === null || trim($k) === '') {
            $k = $_SERVER['WOODCON_APP_KEY'] ?? '';
        }
        return trim((string)$k) !== '' ? trim((string)$k) : null;
    }

    private static function enc(string $value): string
    {
        $key = static::tokenKey();
        if ($key === null || !function_exists('openssl_encrypt')) {
            return $value; // chưa cấu hình khóa mã hóa -> giữ chuẩn hiện tại của hệ thống
        }
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($value, 'aes-256-cbc', hash('sha256', $key, true), 0, $iv);
        return 'ENC:' . base64_encode(json_encode(['i' => base64_encode($iv), 'c' => $cipher], JSON_UNESCAPED_UNICODE));
    }

    private static function dec(string $value): string
    {
        if (str_starts_with($value, 'ENC:')) {
            $key = static::tokenKey();
            if ($key !== null && function_exists('openssl_decrypt')) {
                $d = json_decode((string)base64_decode(substr($value, 4)), true);
                if (is_array($d) && isset($d['i'], $d['c'])) {
                    $plain = openssl_decrypt((string)$d['c'], 'aes-256-cbc', hash('sha256', $key, true), 0, (string)base64_decode($d['i']));
                    if ($plain !== false) {
                        return $plain;
                    }
                }
            }
        }
        return $value;
    }

    private static function setToken(string $token): void
    {
        set_setting('vtp_token', static::enc(trim($token)), 'shipping');
    }

    /** Lưu Token mới (backend only, có mã hóa nếu cấu hình WOODCON_APP_KEY) */
    public static function saveToken(string $token): void
    {
        static::setToken($token);
    }

    // ================================================================
    // HTTP CLIENT (theo mẫu ai-provider.php)
    // ================================================================

    /** @return array{status:int, body:array, curl_errno:int, curl_error:string} */
    private static function httpPost(string $path, ?array $body = null, ?string $token = null, int $timeout = 8, string $method = 'POST'): array
    {
        $method = strtoupper($method);
        $url = static::API_BASE . '/' . ltrim($path, '/');
        $headers = ['Content-Type: application/json; charset=utf-8'];
        if ($token !== null && $token !== '') {
            $headers[] = 'Token: ' . $token;
        }

        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => $headers,
        ];
        if ($method === 'GET') {
            $opts[CURLOPT_HTTPGET] = true;
            if (!empty($body) && !str_contains($url, '?')) {
                $url .= '?' . http_build_query($body);
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        } else {
            $opts[CURLOPT_POST]       = true;
            $opts[CURLOPT_POSTFIELDS] = json_encode($body ?? new \stdClass(), JSON_UNESCAPED_UNICODE);
        }
        $opts[CURLOPT_CUSTOMREQUEST] = $method;
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $errMsg   = curl_error($ch);
        $status   = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode((string)$response, true);
        return [
            'status'     => $status,
            'body'       => is_array($data) ? $data : [],
            'curl_errno' => $errno,
            'curl_error' => $errMsg,
        ];
    }

    /** Lấy message lỗi thân thiện bất kể Viettel trả cấu trúc nào */
    private static function msg(array $res): string
    {
        $b = $res['body'];
        $m = $b['message'] ?? $b['error'] ?? $b['ErrorMessage'] ?? '';
        if (is_string($m) && trim($m) !== '') {
            return trim($m);
        }
        if (isset($b['data']) && is_string($b['data'])) {
            return trim($b['data']);
        }
        if ($res['curl_errno'] !== 0) {
            return 'Không kết nối được Viettel Post (' . $res['curl_error'] . ')';
        }
        return 'Viettel Post trả lỗi ' . $res['status'];
    }

    // ================================================================
    // DANH MỤC TỈNH / QUẬN (có cache DB, TTL 24h)
    // ================================================================

    /** Cache danh mục đã quá 24h chưa (để admin tự động làm mới khi vào trang) */
    public static function cacheStale(): bool
    {
        $t = (string)get_setting('vtp_cat_synced_at', '');
        if ($t === '') {
            return true;
        }
        $ts = strtotime($t);
        return $ts !== false && (time() - $ts) >= self::CACHE_TTL_HOURS * 3600;
    }

    /** Lấy danh sách Tỉnh/Thành [['id'=>..,'name'=>..]] từ cache/API */
    public static function getProvinces(bool $force = false): array
    {
        $db = Database::connect();
        if (!$force) {
            $rows = $db->query('SELECT code, name FROM vtp_categories WHERE parent_id = 0 ORDER BY name')->fetchAll();
            if (count($rows) > 0) {
                return array_map(fn($r) => ['id' => (int)$r['code'], 'name' => (string)$r['name']], $rows);
            }
        }

        $res = static::httpPost('categories/listProvince', ['type' => 1], static::token(), 8, 'GET');
        if ($res['status'] === 200 && isset($res['body']['data']) && is_array($res['body']['data'])) {
            $stored = 0;
            foreach ($res['body']['data'] as $p) {
                $id = (int)static::firstVal($p, ['PROVINCE_ID', 'PROVINCE_CODE', 'code', 'Id']);
                $name = (string)static::firstVal($p, ['PROVINCE_NAME', 'name', 'Name']);
                if ($id > 0 && $name !== '') {
                    static::storeCategory($id, $name, 0);
                    $stored++;
                }
            }
            if ($stored > 0) {
                set_setting('vtp_cat_synced_at', date('Y-m-d H:i:s'), 'shipping');
                return static::getProvinces();
            }
        }
        static::setLastCall('error', 'Đồng bộ danh mục Tỉnh thất bại: ' . static::msg($res));
        return [];
    }

    /** Lấy danh sách Quận/Huyện của tỉnh (cache/API) */
    public static function getDistricts(int $provinceId, bool $force = false): array
    {
        if ($provinceId <= 0) {
            return [];
        }
        $db = Database::connect();
        if (!$force) {
            $stmt = $db->prepare('SELECT code, name FROM vtp_categories WHERE parent_id = ? ORDER BY name');
            $stmt->execute([$provinceId]);
            $rows = $stmt->fetchAll();
            if (count($rows) > 0) {
                return array_map(fn($r) => ['id' => (int)$r['code'], 'name' => (string)$r['name']], $rows);
            }
        }

        $res = static::httpPost('categories/listDistrict?' . http_build_query(['provinceId' => $provinceId]), [], static::token(), 8, 'GET');
        if ($res['status'] === 200 && isset($res['body']['data']) && is_array($res['body']['data'])) {
            $stored = 0;
            foreach ($res['body']['data'] as $p) {
                $id = (int)static::firstVal($p, ['DISTRICT_ID', 'DISTRICT_CODE', 'code', 'Id']);
                $name = (string)static::firstVal($p, ['DISTRICT_NAME', 'name', 'Name']);
                if ($id > 0 && $name !== '') {
                    static::storeCategory($id, $name, $provinceId);
                    $stored++;
                }
            }
            if ($stored > 0) {
                return static::getDistricts($provinceId);
            }
        }
        static::setLastCall('error', 'Đồng bộ danh mục Quận/Huyện (' . $provinceId . ') thất bại: ' . static::msg($res));
        return [];
    }

    /** Đồng bộ toàn bộ Tỉnh + Quận/Huyện của 1 tỉnh (thường là tỉnh kho gửi) */
    public static function syncCategories(?int $provinceId = null): array
    {
        $provinces = static::getProvinces(true);
        $countP = count($provinces);
        $countD = 0;
        if ($provinceId === null && (int)get_setting('vtp_sender_province_id', 0) > 0) {
            $provinceId = (int)get_setting('vtp_sender_province_id', 0);
        }
        if ($provinceId !== null && $provinceId > 0) {
            $countD = count(static::getDistricts($provinceId, true));
        }
        set_setting('vtp_cat_synced_at', date('Y-m-d H:i:s'), 'shipping');
        $message = "Đồng bộ xong: {$countP} tỉnh/thành, {$countD} quận/huyện.";
        static::setLastCall('ok', $message);
        return ['ok' => $countP > 0, 'provinces' => $countP, 'districts' => $countD, 'message' => $message];
    }

    /** Ghi cache 1 dòng danh mục (parent_id = 0 với Tỉnh; mã Tỉnh với Quận/Huyện) */
    private static function storeCategory(int $code, string $name, int $parentId): void
    {
        $db = Database::connect();
        $stmt = $db->prepare('INSERT INTO vtp_categories (code, name, parent_id, updated_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE name = VALUES(name), updated_at = NOW()');
        $stmt->execute([$code, $name, $parentId]);
    }

    /** Đọc giá trị khớp 1 trong các khóa (API các phiên bản khác tên khác) */
    private static function firstVal(array $row, array $keys)
    {
        foreach ($keys as $k) {
            if (isset($row[$k])) return $row[$k];
        }
        return null;
    }

    // ================================================================
    // MAP ĐỊA CHỈ KHÁCH (tên -> mã ID Viettel Post)
    // ================================================================

    /**
     * Tìm mã Tỉnh & Quận theo tên (bộ đệm; không tìm thấy tỉnh thì thử đồng bộ lại 1 lần).
     * @return array{province_id:int,district_id:int}|null
     */
    public static function mapAddress(?string $city, ?string $district): ?array
    {
        $provinces = static::getProvinces();
        if (!$provinces) {
            return null;
        }
        $cityNorm = str_lower_no_accent(trim((string)$city));
        $pid = 0;
        foreach ($provinces as $p) {
            $n = str_lower_no_accent($p['name']);
            if ($n !== '' && ($cityNorm === '' || str_contains($cityNorm, $n) || str_contains($n, $cityNorm))) {
                $pid = $p['id'];
                break;
            }
        }
        if ($pid <= 0) {
            // thử đồng bộ lại 1 lần
            $provinces = static::getProvinces(true);
            foreach ($provinces as $p) {
                $n = str_lower_no_accent($p['name']);
                if ($n !== '' && ($cityNorm === '' || str_contains($cityNorm, $n) || str_contains($n, $cityNorm))) {
                    $pid = $p['id'];
                    break;
                }
            }
        }
        if ($pid <= 0) {
            return null;
        }

        $districts = static::getDistricts($pid);
        $did = 0;
        $dNorm = str_lower_no_accent(trim((string)$district));
        foreach ($districts as $d) {
            $n = str_lower_no_accent($d['name']);
            if ($n !== '' && ($dNorm === '' || str_contains($dNorm, $n) || str_contains($n, $dNorm))) {
                $did = $d['id'];
                break;
            }
        }
        if ($did <= 0) {
            return null;
        }
        return ['province_id' => $pid, 'district_id' => $did];
    }

    // ================================================================
    // TÍNH CƯỚC
    // ================================================================

    /** Khối lượng tính cước (kg) của giỏ hàng: max(KL thực, KL quy đổi thể tích) */
    public static function chargeableWeightKg(array $items): float
    {
        $divisor = max(1.0, (float)get_setting('ship_volumetric_divisor', 6000));
        $real = 0.0;
        $vol  = 0.0;
        foreach ($items as $item) {
            $qty = max(1, (int)($item['quantity'] ?? 1));
            $real += (float)($item['weight_kg'] ?? 0) * $qty;
            $l = (float)($item['dim_l'] ?? 0);
            $w = (float)($item['dim_w'] ?? 0);
            $h = (float)($item['dim_h'] ?? 0);
            if ($l > 0 && $w > 0 && $h > 0) {
                $vol += ($l * $w * $h) / $divisor * $qty;
            }
        }
        return max($real, $vol, 0);
    }

    /**
     * Tính cước Loại 2 (Viettel Post) cho 1 giỏ hàng + địa chỉ khách.
     * @param array  $items     [['quantity','weight_kg','dim_l','dim_w','dim_h','unit_price'], ...]
     * @param string|null $city Tỉnh/Thành khách (tên)
     * @param string|null $district Quận/Huyện khách (tên)
     * @param float   $codAmount Tiền thu hộ (chỉ gửi MONEY_COLLECTION khi bật COD + phương thức COD)
     * @return array{available:bool, ok:bool, fee:float, message:string}
     */
    public static function calculateForCity(array $items, ?string $city, ?string $district, float $codAmount = 0): array
    {
        $fail = static::lastCallInfo();
        if (!static::configured()) {
            $status = static::enabled() ? 'error' : 'off';
            static::setLastCall($status, static::enabled() ? 'Chưa nhập Token Viettel Post.' : 'Tích hợp Viettel Post đang tắt.');
            return ['available' => false, 'ok' => false, 'fee' => 0.0, 'message' => 'Vận chuyển Viettel Post chưa sẵn sàng.'];
        }

        $addr = static::mapAddress($city, $district);
        if ($addr === null) {
            static::setLastCall('error', 'Không map được địa chỉ khách (' . ($city ?: '?') . ' / ' . ($district ?: '?') . ') sang mã Tỉnh/Quận Viettel Post.');
            return ['available' => false, 'ok' => false, 'fee' => 0.0, 'message' => 'Không thể tính phí vận chuyển cho địa chỉ này.'];
        }

        $sender = static::sender();
        if ($sender['province_id'] <= 0) {
            // Gợi ý kho gửi theo "Thành phố kho" đã cấu hình ở Cài đặt chung
            $map = static::mapAddress((string)get_setting('ship_warehouse_city', 'Hồ Chí Minh'), null);
            if ($map === null) {
                static::setLastCall('error', 'Chưa cấu hình kho gửi (Tỉnh/Quận) cho Viettel Post.');
                return ['available' => false, 'ok' => false, 'fee' => 0.0, 'message' => 'Chưa cấu hình kho gửi Viettel Post.'];
            }
            $sender['province_id'] = $map['province_id'];
            $sender['district_id'] = $map['district_id'];
        }
        if ($sender['district_id'] <= 0) {
            $ds = static::getDistricts($sender['province_id']);
            $sender['district_id'] = $ds ? (int)$ds[0]['id'] : 0;
        }
        if ($sender['district_id'] <= 0) {
            static::setLastCall('error', 'Không xác định được Quận/Huyện kho gửi Viettel Post.');
            return ['available' => false, 'ok' => false, 'fee' => 0.0, 'message' => 'Kho gửi chưa đủ thông tin.'];
        }

        $weightKg = static::chargeableWeightKg($items);
        $grams = (int)ceil($weightKg * 1000);
        $productPrice = 0.0;
        foreach ($items as $item) {
            $productPrice += (float)($item['unit_price'] ?? 0) * max(1, (int)($item['quantity'] ?? 1));
        }
        $cod = (static::isCodEnabled() && $codAmount > 0) ? (float)$codAmount : 0.0;

        $payload = [
            'PRODUCT_TYPE'        => (string)get_setting('vtp_product_type', 'HH'),
            'PRODUCT_WEIGHT'      => max(1, $grams),
            'PRODUCT_PRICE'       => round($productPrice),
            'MONEY_COLLECTION'    => round($cod),
            'ORDER_SERVICE'       => (string)get_setting('vtp_service', ''),
            'SENDER_PROVINCE'     => (int)$sender['province_id'],
            'SENDER_DISTRICT'     => (int)$sender['district_id'],
            'RECEIVER_PROVINCE'   => (int)$addr['province_id'],
            'RECEIVER_DISTRICT'   => (int)$addr['district_id'],
        ];

        $fee = static::calculateFee($payload);
        if (!$fee['ok']) {
            static::setLastCall('error', $fee['message']);
            return ['available' => false, 'ok' => false, 'fee' => 0.0, 'message' => $fee['message']];
        }
        $message = 'Cước Viettel Post: ' . number_format($fee['fee'], 0, ',', '.') . ' đ';
        static::setLastCall('ok', $message);
        return ['available' => true, 'ok' => true, 'fee' => round($fee['fee']), 'message' => $message];
    }

    /**
     * Gọi getPriceAll: lấy giá mọi dịch vụ khả dụng của hành trình, chọn dịch vụ
     * cấu hình (vtp_service) hoặc rẻ nhất, sau đó cộng phụ thu nội bộ.
     */
    public static function calculateFee(array $payload): array
    {
        $res = static::httpPost('order/getPriceAll', $payload, static::token(), 15);
        if ($res['status'] !== 200) {
            return ['ok' => false, 'fee' => 0.0, 'message' => 'Viettel Post lỗi ' . $res['status'] . ': ' . static::msg($res)];
        }
        $offers = $res['body']['data'] ?? $res['body'];
        if (!is_array($offers) || $offers === []) {
            return ['ok' => false, 'fee' => 0.0, 'message' => static::msg($res)];
        }
        if (array_values($offers) !== $offers) {
            $offers = [$offers];
        }
        $wanted = strtoupper(trim((string)get_setting('vtp_service', '')));
        $pick = null;
        foreach ($offers as $o) {
            if (!is_array($o)) {
                continue;
            }
            $code = strtoupper((string)static::firstVal($o, ['MA_DV_CHINH', 'TYPE', 'ORDER_SERVICE', 'service', 'code']));
            if ($wanted !== '' && $code === $wanted) {
                $pick = $o;
                break;
            }
        }
        if ($pick === null) {
            foreach ($offers as $o) {
                if (!is_array($o)) {
                    continue;
                }
                $fee = (float)static::firstVal($o, ['GIA_CUOC', 'MONEY_TOTAL', 'MONEY_FEE', 'fee', 'price']);
                $cur = $pick === null ? PHP_FLOAT_MAX : (float)static::firstVal($pick, ['GIA_CUOC', 'MONEY_TOTAL', 'MONEY_FEE', 'fee', 'price']);
                if ($fee < $cur) {
                    $pick = $o;
                }
            }
        }
        if ($pick === null) {
            return ['ok' => false, 'fee' => 0.0, 'message' => 'Viettel Post trả cước không hợp lệ.'];
        }
        $total     = (float)static::firstVal($pick, ['GIA_CUOC', 'MONEY_TOTAL', 'MONEY_FEE', 'fee', 'price', 'MONEY_TOTALFEE']);
        $svc       = (string)static::firstVal($pick, ['MA_DV_CHINH', 'TYPE', 'ORDER_SERVICE', 'service', 'code']);
        $svcName   = (string)static::firstVal($pick, ['TEN_DICHVU', 'name', 'Name']);
        $surcharge = max(0.0, (float)get_setting('vtp_surcharge', 0));
        $label     = $svcName !== '' ? $svcName . ' (' . $svc . ')' : ($svc !== '' ? $svc : 'Viettel Post');
        return ['ok' => true, 'fee' => max(0.0, $total + $surcharge), 'message' => $label, 'service' => $svc, 'service_name' => $svcName];
    }

    public static function isCodEnabled(): bool
    {
        return (int)get_setting('vtp_cod_enabled', 1) === 1;
    }

    // ================================================================
    // TEST KẾT NỐI / LẤY TOKEN TỰ ĐỘNG
    // ================================================================

    /** Test kết nối: gọi getPrice với dữ liệu mẫu (gửi tới chính kho) */
    public static function testConnection(): array
    {
        if (!static::hasToken()) {
            static::setLastCall('error', 'Kiểm tra kết nối thất bại: chưa nhập Token.');
            return ['ok' => false, 'message' => 'Chưa nhập Token Viettel Post.'];
        }
        $sender = static::sender();
        if ($sender['province_id'] <= 0 || $sender['district_id'] <= 0) {
            static::setLastCall('error', 'Kiểm tra kết nối thất bại: chưa cấu hình Tỉnh/Quận kho gửi.');
            return ['ok' => false, 'message' => 'Cần cấu hình Tỉnh/Quận kho gửi trước khi kiểm tra.'];
        }
        $payload = [
            'PRODUCT_TYPE'      => (string)get_setting('vtp_product_type', 'HH'),
            'PRODUCT_WEIGHT'    => 1000,
            'PRODUCT_PRICE'     => 0,
            'MONEY_COLLECTION'  => 0,
            'ORDER_SERVICE'     => (string)get_setting('vtp_service', ''),
            'SENDER_PROVINCE'   => (int)$sender['province_id'],
            'SENDER_DISTRICT'   => (int)$sender['district_id'],
            'RECEIVER_PROVINCE' => (int)$sender['province_id'],
            'RECEIVER_DISTRICT' => (int)$sender['district_id'],
        ];
        $fee = static::calculateFee($payload);
        if (!$fee['ok']) {
            static::setLastCall('error', 'Kiểm tra kết nối thất bại: ' . $fee['message']);
            return ['ok' => false, 'message' => $fee['message']];
        }
        $msg = 'Kết nối thành công. Cước thử (1kg, cùng tỉnh): ' . number_format($fee['fee'], 0, ',', '.') . ' đ.';
        static::setLastCall('ok', $msg);
        return ['ok' => true, 'message' => $msg];
    }

    /** Đăng nhập lấy Token mới (tuỳ chọn cho tài khoản có đăng nhập) */
    public static function fetchToken(string $username, string $password): array
    {
        if ($username === '' || $password === '') {
            return ['ok' => false, 'message' => 'Cần nhập tài khoản và mật khẩu đăng nhập.'];
        }
        $res = static::httpPost('user/login', ['username' => $username, 'password' => $password], null, 15);
        if ($res['status'] !== 200) {
            return ['ok' => false, 'message' => 'Đăng nhập Viettel Post thất bại: ' . static::msg($res)];
        }
        $token = (string)static::firstVal($res['body'], ['data.token', 'token']);
        if ($token === '' && isset($res['body']['data']) && is_array($res['body']['data'])) {
            $token = (string)($res['body']['data']['token'] ?? '');
        }
        if ($token === '') {
            return ['ok' => false, 'message' => 'Viettel Post không trả về Token. ' . static::msg($res)];
        }
        static::setToken($token);
        static::setLastCall('ok', 'Đã lấy Token mới từ Viettel Post.');
        return ['ok' => true, 'message' => 'Đã lấy Token mới thành công.'];
    }

    /** Kiểm tra environment có cURL không (hiển thị cảnh báo admin) */
    public static function curlAvailable(): bool
    {
        return function_exists('curl_init');
    }
}