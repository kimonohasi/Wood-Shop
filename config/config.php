<?php
/**
 * WoodCon - Cấu hình toàn cục
 *
 * File này KHÔNG chứa secret. Mọi giá trị nhạy cảm (DB, Google OAuth,
 * reCAPTCHA, BASE_URL ghi đè...) đọc từ file `.env` ở thư mục gốc — bản mẫu
 * `.env.example` được commit, file `.env` thật bị `.gitignore`
 * (xem config/EnvLoader.php).
 *
 * Vì vậy config/config.php được COMMIT bình thường. ĐỪNG nhét secret vào đây —
 * hãy đặt trong `.env`.
 */

declare(strict_types=1);

use WoodCon\Database;
use WoodCon\Env;

// ---------- NẠP BIẾN MÔI TRƯỜNG ----------
require_once __DIR__ . '/EnvLoader.php';
Env::load(dirname(__DIR__) . '/.env');

// ---------- ĐỊNH NGHĨA ĐƯỜNG DẪN ----------
defined('BASE_PATH') || define('BASE_PATH', dirname(__DIR__));

// Tự động dò BASE_URL dựa trên DOCUMENT_ROOT (chạy ổn trên XAMPP ở mọi thư mục).
// Khi Apache tự dò sai (DocumentRoot lệch) thì đặt BASE_URL trong `.env`
// thay vì sửa file này.
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
$appDir  = str_replace('\\', '/', BASE_PATH);
$relative = $docRoot ? str_replace($docRoot, '', $appDir) : '';
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

$envBaseUrl = Env::get('BASE_URL', '');
if ($envBaseUrl !== '') {
    defined('BASE_URL') || define('BASE_URL', rtrim($envBaseUrl, '/'));
} else {
    defined('BASE_URL') || define('BASE_URL', ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $relative);
}
unset($envBaseUrl);

// Thư mục upload
defined('UPLOAD_PATH') || define('UPLOAD_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'uploads');
defined('UPLOAD_URL')  || define('UPLOAD_URL', BASE_URL . '/uploads');
defined('EXPORT_PATH') || define('EXPORT_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'exports');

// ---------- GOOGLE OAUTH (đăng nhập nhanh bằng Google) ----------
// Trên Google Cloud Console: tạo OAuth Client (loại Web), khai Redirect URI trùng chính xác
// GOOGLE_REDIRECT_URI (mặc định BASE_URL . '/google/callback'). Khai báo trong `.env`:
//   GOOGLE_CLIENT_ID=...  GOOGLE_CLIENT_SECRET=...
// Để TRỐNG = tắt chức năng đăng nhập Google (controller tự bỏ qua).
defined('GOOGLE_CLIENT_ID')     || define('GOOGLE_CLIENT_ID', Env::get('GOOGLE_CLIENT_ID'));
defined('GOOGLE_CLIENT_SECRET') || define('GOOGLE_CLIENT_SECRET', Env::get('GOOGLE_CLIENT_SECRET'));
defined('GOOGLE_REDIRECT_URI')  || define('GOOGLE_REDIRECT_URI', BASE_URL . '/google/callback');

// ---------- GOOGLE reCAPTCHA v2 ----------
// Khai báo trong `.env`: RECAPTCHA_SITE_KEY=...  RECAPTCHA_SECRET_KEY=...
// Để TRỐNG = bỏ qua bước xác thực captcha (chỉ dùng cho môi trường local/dev).
defined('RECAPTCHA_SITE_KEY')   || define('RECAPTCHA_SITE_KEY', Env::get('RECAPTCHA_SITE_KEY'));
defined('RECAPTCHA_SECRET_KEY') || define('RECAPTCHA_SECRET_KEY', Env::get('RECAPTCHA_SECRET_KEY'));

// ---------- THÔNG TIN KẾT NỐI DATABASE (đặt trong `.env`, mặc định hợp với XAMPP) ----------
defined('DB_HOST')    || define('DB_HOST', Env::get('DB_HOST', '127.0.0.1'));
defined('DB_NAME')    || define('DB_NAME', Env::get('DB_NAME', 'woodcon_shop'));
defined('DB_USER')    || define('DB_USER', Env::get('DB_USER', 'root'));
defined('DB_PASS')    || define('DB_PASS', Env::get('DB_PASS', ''));
defined('DB_CHARSET') || define('DB_CHARSET', Env::get('DB_CHARSET', 'utf8mb4'));

// ---------- MÚI GIỜ VIỆT NAM ----------
date_default_timezone_set('Asia/Ho_Chi_Minh');

// ---------- ENCODING / CHARSET (tránh lỗi font tiếng Việt) ----------
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_regex_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');
if (!headers_sent() && PHP_SAPI !== 'cli') {
    header('Content-Type: text/html; charset=UTF-8');
    // Security headers (bổ sung cho .htaccess)
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    if (function_exists('header_remove')) {
        @header_remove('X-Powered-By');
    }
}

// ---------- ERROR REPORTING ----------
// Bật/tắt hiển thị lỗi qua `.env`: APP_DEBUG=1 mới hiện chi tiết.
// Mặc định tắt để không lộ đường dẫn / stack trace cho client.
define('APP_DEBUG', in_array(strtolower(Env::get('APP_DEBUG', '0')), ['1', 'true', 'yes', 'on'], true));

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('display_startup_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/php-error.log');

// Custom error/exception handler — không lộ chi tiết khi APP_DEBUG=0
set_exception_handler(function (\Throwable $e) {
    error_log('[uncaught] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (PHP_SAPI === 'cli') { throw $e; }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    // Request AJAX nhận JSON để frontend hiển thị thông báo phù hợp (không parse nhầm HTML lỗi)
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        json_response(['ok' => false, 'message' => 'Hệ thống đang gặp sự cố. Vui lòng thử lại sau.'], 500);
    }
    $msg = APP_DEBUG
        ? nl2br(htmlspecialchars($e->getMessage() . "\n\n" . $e->getFile() . ':' . $e->getLine(), ENT_QUOTES, 'UTF-8'))
        : 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.';
    echo "<!doctype html><meta charset=utf-8><title>Lỗi</title><pre style=\"font-family:system-ui;padding:24px\">{$msg}</pre>";
    exit;
});

// ---------- GIỚI HẠN UPLOAD ----------
ini_set('upload_max_filesize', '8M');
ini_set('post_max_size', '12M');

// ---------- SESSION SECURITY ----------
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    // session.cookie_secure chỉ bật khi có HTTPS
    ini_set('session.cookie_secure', $https ? '1' : '0');
    // SameSite=Lax: giảm rủi ro CSRF lén gọi, vẫn cho phép điều hướng top-level (Google OAuth) đọc session
    ini_set('session.cookie_samesite', 'Lax');
    session_name('WOODCONSESSID');
    session_start();
}

// ---------- AUTO-LOAD CLASS (models + controllers) ----------
spl_autoload_register(function (string $class): void {
    $prefix = 'WoodCon\\';
    if (str_starts_with($class, $prefix)) {
        $name = str_replace($prefix, '', $class);
        if (str_starts_with($name, 'Controllers\\')) {
            $file = BASE_PATH . '/controllers/' . str_replace('\\', '/', substr($name, strlen('Controllers\\'))) . '.php';
        } else {
            $file = BASE_PATH . '/models/' . str_replace('\\', '/', $name) . '.php';
        }
        if (is_file($file)) {
            require_once $file;
        }
    }
});

// ---------- KẾT NỐI DATABASE + HÀM DÙNG CHUNG ----------
require_once __DIR__ . '/db.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/export.php';
require_once BASE_PATH . '/includes/Mailer.php';
