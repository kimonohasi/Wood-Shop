<?php
/**
 * WoodCon - Các hàm tiện ích dùng chung (helpers)
 * Includes đã nạp cấu hình + kết nối DB.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

use WoodCon\Database;

// ----------------------------------------------------------------
// BẢO MẬT
// ----------------------------------------------------------------

/** Escape XSS khi in ra HTML */
function e(?string $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Tạo / trả về CSRF token của phiên */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Trả về thẻ input ẩn chứa CSRF token */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Kiểm tra token CSRF gửi lên (POST) */
function verify_csrf(?string $token): bool
{
    $valid = isset($token, $_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
    if (!$valid) {
        http_response_code(419);
    }
    return $valid;
}

/** Validate form: trả về chuỗi lỗi hợp nhất */
function validate_form(array $data, array $rules): array
{
    $errors = [];
    foreach ($rules as $field => $rule) {
        $value = trim((string)($data[$field] ?? ''));
        $parts = [];
        if (is_string($rule)) {
            $parts = explode('|', $rule);
        } elseif (is_array($rule)) {
            foreach ($rule as $name => $param) {
                if (is_int($name)) {
                    $parts[] = (string)$param;
                } elseif ($param === true) {
                    $parts[] = (string)$name;
                } elseif ($name === 'min' || $name === 'max') {
                    $parts[] = $name . ':' . $param;
                }
            }
        } else {
            $parts = [(string)$rule];
        }
        foreach ($parts as $r) {
            if ($r === 'required' && $value === '') {
                $errors[$field] = 'Trường này không được để trống.';
            } elseif (str_starts_with($r, 'min:') && strlen($value) < (int)substr($r, 4)) {
                $errors[$field] = 'Nội dung phải có ít nhất ' . substr($r, 4) . ' ký tự.';
            } elseif (str_starts_with($r, 'max:') && strlen($value) > (int)substr($r, 4)) {
                $errors[$field] = 'Nội dung không được vượt quá ' . substr($r, 4) . ' ký tự.';
            } elseif ($r === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = 'Email không hợp lệ.';
            } elseif ($r === 'phone' && $value !== '' && !preg_match('/^(0|\+84)[0-9]{9,10}$/', str_replace(['.', '-', ' '], '', $value))) {
                $errors[$field] = 'Số điện thoại không hợp lệ.';
            } elseif ($r === 'username' && $value !== '' && !preg_match('/^[a-zA-Z0-9_\.]{3,30}$/', $value)) {
                $errors[$field] = 'Tên đăng nhập chỉ gồm chữ cái, số, dấu chấm, gạch dưới (3–30 ký tự).';
            } elseif ($r === 'numeric' && $value !== '' && !is_numeric($value)) {
                $errors[$field] = 'Giá trị phải là số.';
            } elseif ($r === 'integer' && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                $errors[$field] = 'Giá trị phải là số nguyên.';
            }
        }
    }
    return $errors;
}

/** Định dạng tiền Việt Nam */
function format_money(int|float|null $amount): string
{
    return number_format((float)($amount ?? 0), 0, ',', '.') . ' đ';
}

/**
 * Chuẩn hoá chuỗi sang dạng viết thường không dấu (để tìm kiếm tiếng Việt).
 * Áp dụng cho cả truy vấn lẫn cột tu_khoa_tim_kiem để tìm khớp "gù / go" hay "bàn ăn".
 */
function str_lower_no_accent(string $value): string
{
    return jsonx_normalize($value);
}

/** Nội bộ: dãy ký tự có dấu quy về chữ gốc để tìm kiếm không dấu */
function jsonx_normalize(string $value): string
{
    $chars = [
        'á'=>"a",'à'=>"a",'ả'=>"a",'ã'=>"a",'ạ'=>"a",'ă'=>"a",'ắ'=>"a",'ằ'=>"a",'ẳ'=>"a",'ẵ'=>"a",'ặ'=>"a",'â'=>"a",'ấ'=>"a",'ầ'=>"a",'ẩ'=>"a",'ẫ'=>"a",'ậ'=>"a",
        'Á'=>"a",'À'=>"a",'Ả'=>"a",'Ã'=>"a",'Ạ'=>"a",'Ă'=>"a",'Ắ'=>"a",'Ằ'=>"a",'Ẳ'=>"a",'Ẵ'=>"a",'Ặ'=>"a",'Â'=>"a",'Ấ'=>"a",'Ầ'=>"a",'Ẩ'=>"a",'Ẫ'=>"a",'Ậ'=>"a",
        'é'=>"e",'è'=>"e",'ẻ'=>"e",'ẽ'=>"e",'ẹ'=>"e",'ê'=>"e",'ế'=>"e",'ề'=>"e",'ể'=>"e",'ễ'=>"e",'ệ'=>"e",
        'É'=>"e",'È'=>"e",'Ẻ'=>"e",'Ẽ'=>"e",'Ẹ'=>"e",'Ê'=>"e",'Ế'=>"e",'Ề'=>"e",'Ể'=>"e",'Ễ'=>"e",'Ệ'=>"e",
        'ó'=>"o",'ò'=>"o",'ỏ'=>"o",'õ'=>"o",'ọ'=>"o",'ô'=>"o",'ố'=>"o",'ồ'=>"o",'ổ'=>"o",'ỗ'=>"o",'ộ'=>"o",'ơ'=>"o",'ớ'=>"o",'ờ'=>"o",'ở'=>"o",'ỡ'=>"o",'ợ'=>"o",
        'Ó'=>"o",'Ò'=>"o",'Ỏ'=>"o",'Õ'=>"o",'Ọ'=>"o",'Ô'=>"o",'Ố'=>"o",'Ồ'=>"o",'Ổ'=>"o",'Ỗ'=>"o",'Ộ'=>"o",'Ơ'=>"o",'Ớ'=>"o",'Ờ'=>"o",'Ở'=>"o",'Ỡ'=>"o",'Ợ'=>"o",
        'ú'=>"u",'ù'=>"u",'ủ'=>"u",'ũ'=>"u",'ụ'=>"u",'ư'=>"u",'ứ'=>"u",'ừ'=>"u",'ử'=>"u",'ữ'=>"u",'ự'=>"u",
        'Ú'=>"u",'Ù'=>"u",'Ủ'=>"u",'Ũ'=>"u",'Ụ'=>"u",'Ư'=>"u",'Ứ'=>"u",'Ừ'=>"u",'Ử'=>"u",'Ữ'=>"u",'Ự'=>"u",
        'í'=>"i",'ì'=>"i",'ỉ'=>"i",'ĩ'=>"i",'ị'=>"i",'Í'=>"i",'Ì'=>"i",'Ỉ'=>"i",'Ĩ'=>"i",'Ị'=>"i",
        'đ'=>"d",'Đ'=>"d",
        'ý'=>"y",'ỳ'=>"y",'ỷ'=>"y",'ỹ'=>"y",'ỵ'=>"y",'Ý'=>"y",'Ỳ'=>"y",'Ỷ'=>"y",'Ỹ'=>"y",'Ỵ'=>"y",
    ];
    return mb_strtolower(strtr($value, $chars), 'UTF-8');
}

/** Ảnh mặc định khi sản phẩm chưa có ảnh */
function default_image(): string
{
    return BASE_URL . '/assets/images/shop/table-wood.jpg';
}

/** Chuyển đường dẫn ảnh tương đối thành URL tuyệt đối (BASE_URL) */
function image_url(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return default_image();
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $p = ltrim($path, '/');
    // Đã có tiền tố thư mục web hợp lệ (assets/, uploads/) -> giữ nguyên
    if (str_starts_with($p, 'assets/') || str_starts_with($p, 'uploads/')) {
        return BASE_URL . '/' . $p;
    }
    // Path tương đối thư mục upload (products/, categories/, banners/, ...)
    // -> thêm tiền tố uploads/ vì file vật lý nằm dưới uploads/<thư mục>/
    return BASE_URL . '/uploads/' . $p;
}

/**
 * Xuất SVG icon từ sprite (assets/icons/sprite.svg).
 * $name là id symbol: 'bi-truck' hoặc 'ms-local_shipping'.
 * $class: các class bổ sung (gộp vào class="icon ..."); $attrs: chuỗi thuộc tính
 * (thuộc tính bổ sung được pass thẳng vào thẻ <svg>, VD: 'style="font-size:18px"').
 */
function icon(string $name, string $class = '', string $attrs = ''): string
{
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
    $svgClass = trim('icon ' . $class);
    return '<svg class="' . e($svgClass) . '" aria-hidden="true"'
        . ($attrs !== '' ? ' ' . $attrs : '')
        . '><use href="' . BASE_URL . '/assets/icons/sprite.svg#' . $id . '"></use></svg>';
}

/** Định dạng ngày giờ */
function format_date(?string $dt, string $format = 'd/m/Y H:i'): string
{
    if (!$dt) {
        return '—';
    }
    return date($format, strtotime($dt));
}

/** Redirect an toàn */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/** Trả về JSON */
function json_response(mixed $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Xác thực Google reCAPTCHA v2 (dùng chung cho toàn dự án).
 *  Cơ chế giống hệt AuthController::verifyRecaptcha() — cùng field
 *  g-recaptcha-response, cùng secret, cùng API siteverify. */
function verify_recaptcha(?string $response): bool
{
    if (!RECAPTCHA_SECRET_KEY) {
        return true; // Chưa cấu hình -> bỏ qua (giữ nguyên hành vi hiện tại)
    }
    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'secret'   => RECAPTCHA_SECRET_KEY,
            'response' => (string)$response,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    $data = json_decode((string)$body, true);
    return is_array($data) && ($data['success'] ?? false) === true;
}

// ----------------------------------------------------------------
// FLASH MESSAGE
// ----------------------------------------------------------------

function set_flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

/**
 * Xuất flash còn đọng trong session thành lời gọi showToast() ở cuối trang.
 * Gọi sau khi toast.js đã được nạp (vào footer layout). Tự unset để F5 không lặp.
 */
function render_flash_toasts(): void
{
    $map = ['success' => 'success', 'error' => 'error', 'warning' => 'warning', 'info' => 'info'];
    $toasts = [];
    foreach ($map as $key => $type) {
        if (isset($_SESSION['flash'][$key])) {
            $val = $_SESSION['flash'][$key];
            if (is_array($val)) {
                foreach ($val as $v) {
                    $toasts[] = ['type' => $type, 'msg' => (string)$v];
                }
            } else {
                $toasts[] = ['type' => $type, 'msg' => (string)$val];
            }
            unset($_SESSION['flash'][$key]);
        }
    }
    if (!$toasts) {
        return;
    }
    $json = json_encode(array_values($toasts), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
    echo "<script>(function(){var t=" . $json . ";if(!window.showToast)return;"
        . "var run=function(){t.forEach(function(x){showToast(x.msg,x.type);});};"
        . "if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',run);}else{run();}"
        . "})();</script>";
}

// ----------------------------------------------------------------
// SETTINGS (cấu hình website từ bảng settings, cache theo request)
// ----------------------------------------------------------------

/** Vùng nhớ cache settings dùng chung (để set_setting có thể làm mới cache của get_setting) */
final class WoodConSettingsCache
{
    public static ?array $data = null;
}

function get_setting(string $key, mixed $default = ''): mixed
{
    if (WoodConSettingsCache::$data === null) {
        WoodConSettingsCache::$data = [];
        try {
            $stmt = Database::connect()->query('SELECT setting_key, setting_value FROM settings');
            foreach ($stmt->fetchAll() as $row) {
                WoodConSettingsCache::$data[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable) {
        }
    }
    return array_key_exists($key, WoodConSettingsCache::$data) ? WoodConSettingsCache::$data[$key] : $default;
}

/**
 * Ghi (upsert) một setting vào bảng settings.
 * GROUP cuối cùng chỉ nhận các giá trị hợp lệ của bảng: general/member/shop...
 * Trả về true nếu thành công.
 */
function set_setting(string $key, mixed $value, string $group = 'general'): bool
{
    try {
        $db = Database::connect();
        $stmt = $db->prepare(
            "INSERT INTO settings (setting_key, setting_value, group_name, updated_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), group_name = VALUES(group_name), updated_at = NOW()"
        );
        $stmt->execute([$key, (string)$value, $group]);
        // Làm mới cache để đọc lại trong cùng request (ví dụ: vừa lưu Token vừa đọc lại)
        if (WoodConSettingsCache::$data !== null) {
            WoodConSettingsCache::$data[$key] = (string)$value;
        }
        return true;
    } catch (Throwable) {
        return false;
    }
}

// ----------------------------------------------------------------
// KHÁCH HÀNG ĐĂNG NHẬP
// ----------------------------------------------------------------

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        if (empty($_SESSION['user_id'])) {
            $user = null;
        } else {
            $stmt = Database::connect()->prepare('SELECT * FROM users WHERE id = ? AND status = 1 LIMIT 1');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
        }
    }
    return $user;
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('warning', 'Vui lòng đăng nhập để tiếp tục.');
        redirect(BASE_URL . '/dang-nhap?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    }
}

/** ID admin đang đăng nhập (0 nếu không có) */
function admin_id(): int
{
    return (int)($_SESSION['admin_id'] ?? 0);
}

// ----------------------------------------------------------------
// SLUG & TEXT
// ----------------------------------------------------------------

/** Tạo slug tiếng Việt để làm URL thân thiện SEO */
function slugify(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');

    // Bảng chuyển đổi tiếng Việt -> Latin (đầy đủ 6 thanh, tổng 67 ký tự)
    $viet = [
        'à','á','ả','ã','ạ','ă','ằ','ắ','ẳ','ẵ','ặ','â','ầ','ấ','ẩ','ẫ','ậ',
        'è','é','ẻ','ẽ','ẹ','ê','ề','ế','ể','ễ','ệ',
        'ì','í','ỉ','ĩ','ị',
        'ò','ó','ỏ','õ','ọ','ô','ồ','ố','ổ','ỗ','ộ','ơ','ờ','ớ','ở','ỡ','ợ',
        'ù','ú','ủ','ũ','ụ','ư','ừ','ứ','ử','ữ','ự',
        'ỳ','ý','ỷ','ỹ','ỵ',
        'đ',
    ];
    $latin = [
        'a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
        'e','e','e','e','e','e','e','e','e','e','e',
        'i','i','i','i','i',
        'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
        'u','u','u','u','u','u','u','u','u','u','u',
        'y','y','y','y','y',
        'd',
    ];
    $text = str_replace($viet, $latin, $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text ?: 'chu-de-' . time();
}

/** Slug duy nhất: nếu trùng slug sản phẩm khác -> tự thêm hậu tố số (-2, -3, ...) */
function unique_slug(int $productId, string $base): string
{
    $c = \WoodCon\Database::connect();
    $slug = $base;
    $i = 2;
    while (true) {
        $stmt = $c->prepare('SELECT id FROM products WHERE slug = ? AND id <> ? LIMIT 1');
        $stmt->execute([$slug, $productId]);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}

// ----------------------------------------------------------------
// UPLOAD ẢNH (validate + rename + resize + nén)
// ----------------------------------------------------------------

/**
 * Upload ảnh với validate loại/kích thước, tự rename, resize giữ tỷ lệ.
 * @param array  $file  mảng $_FILES[...]
 * @param string $folder thư mục con trong /uploads, VD: 'products'
 * @param int    $maxWidth chiều rộng tối đa sau resize
 * @return string|null tên file tương đối (VD: products/abc.jpg) hoặc null nếu lỗi
 */
function upload_image(array $file, string $folder = 'images', int $maxWidth = 1600): ?string
{
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $errors = [];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    if (!in_array($file['type'], $allowed, true)) {
        $errors[] = 'Định dạng ảnh không hợp lệ (chỉ chấp nhận JPG/PNG/WEBP/GIF).';
    }
    if ($file['size'] > 8 * 1024 * 1024) {
        $errors[] = 'Kích thước ảnh vượt quá 8MB.';
    }
    if (!empty($errors)) {
        set_flash('error', implode(' ', $errors));
        return null;
    }

    $targetDir = rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    // MIME thật từ NỘI DUNG file (finfo) — không tin loại file do client khai báo
    $mime = class_exists('finfo')
        ? (new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name'])
        : mime_content_type($file['tmp_name']);
    if (!is_string($mime) || !in_array($mime, $allowed, true)) {
        set_flash('error', 'File không phải là ảnh hợp lệ.');
        return null;
    }

    $ext = match ($mime) {
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        default      => 'jpg',
    };
    $name = $folder . '/' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

    $img = match ($mime) {
        'image/png'  => @imagecreatefrompng($file['tmp_name']),
        'image/webp' => @imagecreatefromwebp($file['tmp_name']),
        'image/gif'  => @imagecreatefromgif($file['tmp_name']),
        default      => @imagecreatefromjpeg($file['tmp_name']),
    };
    if (!$img) {
        set_flash('error', 'Không đọc được ảnh.');
        return null;
    }

    $w = imagesx($img);
    $h = imagesy($img);
    if ($w > $maxWidth) {
        $ratio = $maxWidth / $w;
        $newW = $maxWidth;
        $newH = (int)round($h * $ratio);
        $newImg = imagecreatetruecolor($newW, $newH);
        if ($mime === 'image/png' || $mime === 'image/webp' || $mime === 'image/gif') {
            imagealphablending($newImg, false);
            imagesavealpha($newImg, true);
        }
        imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($img);
        $img = $newImg;
    }

    $dest = UPLOAD_PATH . DIRECTORY_SEPARATOR . $name;
    $ok = false;
    switch ($mime) {
        case 'image/png':  $ok = imagepng($img, $dest, 8); break;
        case 'image/webp': $ok = imagewebp($img, $dest, 82); break;
        case 'image/gif':  $ok = imagegif($img, $dest); break;
        default:           $ok = imagejpeg($img, $dest, 85); break;
    }
    imagedestroy($img);

    return $ok ? $name : null;
}

// ----------------------------------------------------------------
// PHÂN TRANG
// ----------------------------------------------------------------

/**
 * Tính toán dữ liệu phân trang.
 * @return array{total:int, per_page:int, current_page:int, total_pages:int, offset:int, pages:array}
 */
function paginate(int $total, int $perPage, int $currentPage, int $window = 2): array
{
    $totalPages = max(1, (int)ceil($total / max(1, $perPage)));
    $currentPage = max(1, min($totalPages, $currentPage));
    return [
        'total'        => $total,
        'per_page'     => $perPage,
        'current_page' => $currentPage,
        'total_pages'  => $totalPages,
        'offset'       => ($currentPage - 1) * $perPage,
        'pages'        => paginate_page_range($currentPage, $totalPages, $window),
    ];
}

/** Sinh dãy số trang hiển thị với cửa sổ (window) quanh trang hiện tại. */
function paginate_page_range(int $cur, int $total, int $window = 2): array
{
    if ($total <= 1) return [];
    $from = max(1, $cur - $window);
    $to   = min($total, $cur + $window);
    $range = range($from, $to);
    if (!in_array(1, $range, true)) {
        array_unshift($range, 1);
        if ($from > 2) array_splice($range, 1, 0, '...');
    }
    if (!in_array($total, $range, true)) {
        if ($to < $total - 1) $range[] = '...';
        $range[] = $total;
    }
    return $range;
}

/**
 * Sinh URL phân trang giữ nguyên query string hiện có.
 * Khi trang = 1 sẽ bỏ tham số `page` để URL sạch.
 */
function paginate_url(int $page, ?string $basePath = null): string
{
    $qs = $_GET;
    unset($qs['route']);
    unset($qs['page']);
    if ($page > 1) $qs['page'] = $page;
    $path = $basePath ?: (strtok($_SERVER['REQUEST_URI'] ?? '/', '?'));
    $qs = array_filter($qs, fn($v) => $v !== '' && $v !== null);
    return $path . (empty($qs) ? '' : '?' . http_build_query($qs));
}

// ----------------------------------------------------------------
// LOG HỆ THỐNG
// ----------------------------------------------------------------

function write_log(string $type, string $description, ?int $adminId = null, ?int $userId = null): void
{
    try {
        $stmt = Database::connect()->prepare(
            'INSERT INTO logs (log_type, description, admin_id, user_id, ip, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$type, $description, $adminId, $userId, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Throwable) {
    }
}

// ----------------------------------------------------------------
// RATE LIMIT (chống brute-force login + spam)
// ----------------------------------------------------------------

/**
 * Ghi nhận một lần thử (đúng hoặc sai) cho một khóa (VD: 'login:email').
 * @return int số lần thử trong cửa sổ hiện tại
 */
function rate_limit_hit(string $key, int $maxAttempts, int $windowSeconds): int
{
    $now = time();
    $bucket = $_SESSION['__ratelimit'][$key] ?? ['count' => 0, 'reset_at' => $now + $windowSeconds];
    if ($now >= $bucket['reset_at']) {
        $bucket = ['count' => 0, 'reset_at' => $now + $windowSeconds];
    }
    $bucket['count']++;
    $_SESSION['__ratelimit'][$key] = $bucket;
    return $bucket['count'];
}

/**
 * Kiểm tra còn cho phép thử tiếp không. Trả về true nếu VƯỢT ngưỡng.
 */
function rate_limit_blocked(string $key, int $maxAttempts, int $windowSeconds): bool
{
    $now = time();
    $bucket = $_SESSION['__ratelimit'][$key] ?? ['count' => 0, 'reset_at' => $now + $windowSeconds];
    if ($now >= $bucket['reset_at']) {
        return false;
    }
    return $bucket['count'] >= $maxAttempts;
}

/** Reset bộ đếm sau khi đăng nhập thành công. */
function rate_limit_clear(string $key): void
{
    unset($_SESSION['__ratelimit'][$key]);
}

