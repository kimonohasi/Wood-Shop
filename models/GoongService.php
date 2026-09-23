<?php
/**
 * WoodCon - Tích hợp Goong Maps API (gợi ý địa chỉ + geocode ngược)
 *
 * Lớp service backend DUY NHẤT gọi Goong Maps API. Api key nằm ở bảng settings
 * và chỉ được đọc/giải mã phía server — KHÔNG bao giờ rò ra JS trình duyệt.
 *
 * 3 endpoint:
 *   - autocomplete : https://rsapi.goong.io/Place/AutoComplete (input + location)
 *   - placeDetail  : https://rsapi.goong.io/Place/Detail       (place_id -> địa chỉ + lat/lng)
 *   - reverseGeocode: https://rsapi.goong.io/Geocode           (latlng -> địa chỉ)
 *
 * Xử lý lỗi/quota: khi status khác OK hoặc lỗi HTTP -> trả ok=false, ẩn gợi ý,
 * không chặn khách nhập tay, ghi log server để admin kiểm tra.
 *
 * Cache tạm: nếu khách gõ lại đúng chuỗi vừa tìm trong vài giây -> dùng lại kết
 * quả trong bảng goong_cache thay vì gọi Goong (tiết kiệm quota gói Free ~30k/ tháng).
 */

declare(strict_types=1);

namespace WoodCon;

use PDO;

class GoongService
{
    public const API_AUTOCOMPLETE  = 'https://rsapi.goong.io/Place/AutoComplete';
    public const API_DETAIL        = 'https://rsapi.goong.io/Place/Detail';
    public const API_GEOCODE       = 'https://rsapi.goong.io/Geocode';

    /** TTL cache ngắn: đủ để chặn trùng request khi khách gõ đi gõ lại, nhẹ cho DB */
    public const CACHE_TTL_SECONDS = 120;
    /** Giới hạn số dòng cache giữ lại (dọn bớt khi vượt) */
    public const CACHE_MAX_ROWS    = 500;

    // ================================================================
    // CẤU HÌNH / TRẠNG THÁI
    // ================================================================

    /** Bật/tắt tính năng gợi ý địa chỉ (đã cấu hình key + bật công tắc trong Cài đặt) */
    public static function enabled(): bool
    {
        return (int)get_setting('goong_autocomplete_enabled', 0) === 1 && static::apiKey() !== '';
    }

    /** API key đã giải mã (backend only — không bao giờ trả về client) */
    public static function apiKey(): string
    {
        return static::dec(trim((string)get_setting('goong_api_key', '')));
    }

    /** Thông tin cấu hình để dựng form admin */
    public static function config(): array
    {
        return [
            'enabled' => static::enabled(),
            'has_key' => trim((string)get_setting('goong_api_key', '')) !== '',
            'last'    => static::lastCallInfo(),
        ];
    }

    /** Trạng thái log gọi API gần nhất (hiển thị admin để debug quota/key) */
    public static function lastCallInfo(): array
    {
        return [
            'time'    => (string)get_setting('goong_last_call', ''),
            'status'  => (string)get_setting('goong_last_status', ''),
            'message' => (string)get_setting('goong_last_message', ''),
        ];
    }

    /**
     * Chuỗi "lat,lng" ưu tiên kết quả gần cửa hàng, cấu hình trong Cài đặt.
     * Trả về '' nếu chưa cấu hình hoặc tọa độ không hợp lệ (Goong bỏ qua location).
     */
    public static function biasLocationString(): string
    {
        $lat = (float)get_setting('goong_location_lat', 0);
        $lng = (float)get_setting('goong_location_lng', 0);
        if ($lat === 0.0 && $lng === 0.0) {
            return '';
        }
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return '';
        }
        return number_format($lat, 6, '.', '') . ',' . number_format($lng, 6, '.', '');
    }

    public static function setLastCall(string $status, string $message): void
    {
        set_setting('goong_last_call', date('Y-m-d H:i:s'), 'maps');
        set_setting('goong_last_status', $status, 'maps');
        set_setting('goong_last_message', mb_substr($message, 0, 500), 'maps');
    }

    /**
     * Kiểm tra kết nối thật tới Goong Maps (gọi thử AutoComplete).
     * Không phụ thuộc công tắc bật/tắt — chỉ cần API key. Kết quả cập nhật lastCall.
     * @return array{ok:bool, message:string}
     */
    public static function testConnection(): array
    {
        if (trim((string)get_setting('goong_api_key', '')) === '') {
            static::setLastCall('error', 'Kiểm tra kết nối thất bại: chưa nhập Goong API Key.');
            return ['ok' => false, 'message' => 'Chưa nhập Goong API Key (mục Bản đồ & gợi ý địa chỉ).'];
        }

        $location = static::biasLocationString();
        if ($location === '') {
            $location = '21.028511,105.804817'; // Hà Nội — chỉ để thử kết nối
        }
        $res = static::httpGet(static::API_AUTOCOMPLETE, [
            'api_key'       => static::apiKey(),
            'input'         => 'Trần Hưng Đạo',
            'more_compound' => 'true',
            'location'      => $location,
        ]);
        $body = $res['body'];

        if ($res['status'] === 200 && ($body['status'] ?? '') === 'OK' && isset($body['predictions']) && is_array($body['predictions'])) {
            $msg = 'Kết nối Goong Maps thành công (' . count($body['predictions']) . ' gợi ý).';
            static::setLastCall('ok', $msg);
            return ['ok' => true, 'message' => $msg];
        }

        static::fail('test-connection', $res);
        return ['ok' => false, 'message' => static::msg($res) . ' (HTTP ' . $res['status'] . ').'];
    }

    // ================================================================
    // MÃ HÓA API KEY (backend): AES-256-CBC nếu có WOODCON_APP_KEY
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
            return $value; // chưa cấu hình khóa mã hóa -> giữ giá trị chuẩn hiện tại
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

    // ================================================================
    // HTTP CLIENT (GET, giống mẫu ViettelPost / ai-provider)
    // ================================================================

    /** @return array{status:int, body:array, curl_errno:int, curl_error:string} */
    private static function httpGet(string $url, array $params, int $timeout = 8): array
    {
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'WoodCon/1.0',
        ]);
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

    /** Lấy message lỗi thân thiện bất kể Goong trả cấu trúc nào */
    private static function msg(array $res): string
    {
        $b = $res['body'];
        $m = $b['message'] ?? $b['error'] ?? '';
        if (is_string($m) && trim($m) !== '') {
            return trim($m);
        }
        if ($res['curl_errno'] !== 0) {
            return 'Không kết nối được Goong Maps (' . $res['curl_error'] . ')';
        }
        return 'Goong Maps trả lỗi HTTP ' . $res['status'];
    }

    // ================================================================
    // 3 ENDPOINT CHÍNH
    // ================================================================

    /**
     * Gợi ý địa chỉ (Place AutoComplete).
     * @param string      $input        chuỗi khách đang gõ
     * @param string|null $location     "lat,lng" ưu tiên kết quả gần khu vực (nếu có)
     * @param string|null $sessionToken UUID v4 của phiên gõ hiện tại (Goong tính phí theo phiên)
     * @return array{ok:bool, predictions:array}
     */
    public static function autocomplete(string $input, ?string $location = null, ?string $sessionToken = null): array
    {
        $input = trim($input);
        if ($input === '') {
            return ['ok' => false, 'predictions' => []];
        }
        if (!static::enabled()) {
            return ['ok' => false, 'predictions' => []];
        }

        // Cache tạm: giống hệt chuỗi vừa tìm + cùng vị trí -> dùng lại kết quả cũ
        $cacheKey = 'ac:' . str_lower_no_accent($input) . (($location !== null && $location !== '') ? '|' . $location : '');
        $cached = static::cacheGet($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $params = ['api_key' => static::apiKey(), 'input' => $input, 'more_compound' => 'true'];
        if ($location !== null && $location !== '') {
            $params['location'] = $location;
        }
        if ($sessionToken !== null && $sessionToken !== '') {
            $params['sessiontoken'] = $sessionToken;
        }

        $res = static::httpGet(static::API_AUTOCOMPLETE, $params);
        $body = $res['body'];

        if ($res['status'] === 200 && ($body['status'] ?? '') === 'OK' && isset($body['predictions']) && is_array($body['predictions'])) {
            $predictions = [];
            foreach ($body['predictions'] as $p) {
                if (!isset($p['place_id'], $p['description'])) {
                    continue;
                }
                $compound = [];
                if (isset($p['compound']) && is_array($p['compound'])) {
                    $compound = [
                        'ward'     => (string)($p['compound']['commune'] ?? ''),
                        'district' => (string)($p['compound']['district'] ?? ''),
                        'province' => (string)($p['compound']['province'] ?? ''),
                    ];
                }
                $predictions[] = [
                    'place_id'    => (string)$p['place_id'],
                    'description' => (string)$p['description'],
                    // Giữ structured_formatting nếu có (gạch chân phần khớp + tách thành phần địa chỉ)
                    'main_text'   => (string)($p['structured_formatting']['main_text'] ?? $p['description'] ?? ''),
                    'compound'    => $compound,
                ];
            }
            $result = ['ok' => true, 'predictions' => $predictions];
            static::cacheSet($cacheKey, $result);
            static::setLastCall('ok', 'AutoComplete OK (' . count($predictions) . ' kết quả)');
            return $result;
        }

        static::fail('autocomplete', $res);
        return ['ok' => false, 'predictions' => []];
    }

    /**
     * Chi tiết địa điểm (Place Detail): địa chỉ chuẩn hóa + tọa độ.
     * @return array{ok:bool, address?:string, lat?:float, lng?:float}
     */
    public static function placeDetail(string $placeId, ?string $sessionToken = null): array
    {
        $placeId = trim($placeId);
        if ($placeId === '') {
            return ['ok' => false];
        }
        if (!static::enabled()) {
            return ['ok' => false];
        }

        $params = ['api_key' => static::apiKey(), 'place_id' => $placeId];
        if ($sessionToken !== null && $sessionToken !== '') {
            $params['sessiontoken'] = $sessionToken;
        }

        $res = static::httpGet(static::API_DETAIL, $params);
        $body = $res['body'];

        if ($res['status'] === 200 && ($body['status'] ?? '') === 'OK' && isset($body['result']) && is_array($body['result'])) {
            $r = $body['result'];
            $loc = $r['geometry']['location'] ?? null;
            $address = (string)($r['formatted_address'] ?? '');
            if ($address !== '' && is_array($loc)) {
                $result = [
                    'ok'      => true,
                    'address' => $address,
                    'lat'     => (float)$loc['lat'],
                    'lng'     => (float)$loc['lng'],
                ];
                // Format lại địa chỉ chi tiết (tách thành phần nhỏ nếu có) để bổ trợ các ô Phường/Quận/Tỉnh
                if (isset($r['address_components']) && is_array($r['address_components'])) {
                    $result['components'] = static::parseComponents($r['address_components']);
                } elseif (isset($r['compound']) && is_array($r['compound'])) {
                    $result['components'] = [
                        'ward'     => (string)($r['compound']['commune'] ?? ''),
                        'district' => (string)($r['compound']['district'] ?? ''),
                        'province' => (string)($r['compound']['province'] ?? ''),
                    ];
                }
                static::setLastCall('ok', 'PlaceDetail OK');
                return $result;
            }
        }

        static::fail('place-detail', $res);
        return ['ok' => false];
    }

    /**
     * Geocode ngược (GEOCODE): tọa độ -> địa chỉ hiển thị, dùng khi khách kéo thả ghim.
     * @return array{ok:bool, address?:string, lat?:float, lng?:float}
     */
    public static function reverseGeocode(float $lat, float $lng): array
    {
        if (!static::enabled()) {
            return ['ok' => false];
        }
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return ['ok' => false];
        }

        $latlng = rtrim(rtrim(number_format($lat, 6, '.', ''), '0'), '.') . ',' . rtrim(rtrim(number_format($lng, 6, '.', ''), '0'), '.');
        $cacheKey = 'geo:' . $latlng;
        $cached = static::cacheGet($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $res = static::httpGet(static::API_GEOCODE, ['api_key' => static::apiKey(), 'latlng' => $latlng]);
        $body = $res['body'];

        if ($res['status'] === 200 && ($body['status'] ?? '') === 'OK' && isset($body['results'][0]) && is_array($body['results'][0])) {
            $r = $body['results'][0];
            $loc = $r['geometry']['location'] ?? null;
            $address = (string)($r['formatted_address'] ?? '');
            if ($address !== '' && is_array($loc)) {
                $result = [
                    'ok'      => true,
                    'address' => $address,
                    'lat'     => (float)$loc['lat'],
                    'lng'     => (float)$loc['lng'],
                ];
                if (isset($r['address_components']) && is_array($r['address_components'])) {
                    $result['components'] = static::parseComponents($r['address_components']);
                }
                static::cacheSet($cacheKey, $result);
                static::setLastCall('ok', 'Geocode OK');
                return $result;
            }
        }

        static::fail('reverse-geocode', $res);
        return ['ok' => false];
    }

    /** Tách địa chỉ thành các thành phần (số nhà/đường, phường, quận, tỉnh) nếu Goong cung cấp */
    private static function parseComponents(array $components): array
    {
        $out = [];
        foreach ($components as $c) {
            $types = $c['types'] ?? [];
            $name = (string)($c['short_name'] ?? $c['long_name'] ?? '');
            if (in_array('route', $types, true) || in_array('street_number', $types, true)) {
                $out['street'] = trim(($out['street'] ?? '') . ' ' . $name);
            }
            if (in_array('administrative_area_level_2', $types, true)) $out['district'] = $name;
            if (in_array('administrative_area_level_1', $types, true)) $out['province'] = $name;
            if (in_array('locality', $types, true) || in_array('sublocality_level_1', $types, true)) $out['ward'] = $name;
            if (in_array('poi', $types, true)) $out['poi'] = $name;
        }
        return $out;
    }

    // ================================================================
    // XỬ LÝ LỖI / QUOTA (không bao giờ lộ thông báo kỹ thuật cho khách)
    // ================================================================

    private static function fail(string $op, array $res): void
    {
        $message = static::msg($res);
        static::setLastCall('error', $op . ': ' . $message . ' (HTTP ' . $res['status'] . ')');
        write_log('goong', 'Goong [' . $op . '] ' . $message . ' (HTTP ' . $res['status'] . ')' . ($res['curl_errno'] ? ' curl#' . $res['curl_errno'] : ''));
    }

    // ================================================================
    // CACHE TẠM PHÍA SERVER (bảng goong_cache, TTL ngắn)
    // ================================================================

    private static function cacheGet(string $key): ?array
    {
        try {
            $stmt = Database::connect()->prepare(
                'SELECT payload FROM goong_cache
                 WHERE cache_key = ? AND created_at >= NOW() - INTERVAL ' . (int)static::CACHE_TTL_SECONDS . ' SECOND
                 LIMIT 1'
            );
            $stmt->execute([$key]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || $row['payload'] === null) {
                return null;
            }
            $data = json_decode((string)$row['payload'], true);
            return is_array($data) ? $data : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function cacheSet(string $key, array $value): void
    {
        try {
            $db = Database::connect();
            $stmt = $db->prepare(
                'INSERT INTO goong_cache (cache_key, payload, created_at)
                 VALUES (?, ?, NOW())
                 ON DUPLICATE KEY UPDATE payload = VALUES(payload), created_at = NOW()'
            );
            $stmt->execute([$key, json_encode($value, JSON_UNESCAPED_UNICODE)]);
            static::cachePrune($db);
        } catch (\Throwable) {
            // Cache lỗi không ảnh hưởng luồng chính — bỏ qua
        }
    }

    /** Dọn bớt dòng cache cũ khi vượt ngưỡng giữ tối đa */
    private static function cachePrune(PDO $db): void
    {
        try {
            $stmt = $db->query('SELECT COUNT(*) AS c FROM goong_cache');
            $count = (int)($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
            if ($count > static::CACHE_MAX_ROWS) {
                $db->exec(
                    'DELETE FROM goong_cache ORDER BY created_at ASC LIMIT '
                    . (int)max(50, $count - static::CACHE_MAX_ROWS)
                );
            }
        } catch (\Throwable) {
        }
    }
}