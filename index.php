<?php
/**
 * WoodCon - Front controller (điểm vào duy nhất cho toàn bộ website)
 * Rewrite: .htaccess chuyển mọi URL về index.php?route=$1
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

use WoodCon\Controllers\{
    AccountController,
    AdminController,
    AuthController,

    BenefitsController,
    CartController,
    CategoryController,
    CheckoutController,
    ContactController,
    HomeController,
    NewsController,
    ProductController,
    SearchController,
    TrackController,
    VoucherController,
    WarrantyController
};

// Lấy route từ .htaccess (?route=...) hoặc tự dò theo REQUEST_URI
$route = trim($_GET['route'] ?? '', '/');
if ($route === '' && isset($_SERVER['REQUEST_URI'])) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
    $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $base = rtrim($base, '/');
    if ($base && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    $route = trim($path, '/');
}

$segments = $route === '' ? [] : explode('/', $route);
$first = $segments[0] ?? '';

try {
    switch ($first) {
        case '':
            (new HomeController())->index();
            break;

        case 'danh-muc':
            (new CategoryController())->show($segments[1] ?? '');
            break;

        case 'san-pham':
            (new ProductController())->show($segments[1] ?? '');
            break;

        case 'tim-kiem':
            (new SearchController())->index();
            break;

        case 'tim-kiem-ket-qua':
            (new SearchController())->ajaxResults();
            break;

        case 'goi-y-tim-kiem':
            (new SearchController())->ajaxSuggest();
            break;

        case 'gio-hang':
            (new CartController())->index();
            break;

        case 'thanh-toan':
            (new CheckoutController())->index();
            break;

        case 'hoan-tat':
            (new CheckoutController())->success();
            break;

        case 'tra-cuu-don-hang':
            (new TrackController())->index();
            break;

        case 'tra-cuu-bao-hanh':
            (new WarrantyController())->index();
            break;

        case 'dang-nhap':
            (new AuthController())->login($segments[1] ?? '');
            break;

        case 'dang-ky':
            (new AuthController())->register();
            break;

        case 'quen-mat-khau':
            (new AuthController())->forgot();
            break;

        case 'google':
            (new AuthController())->googleCallback();
            break;

        case 'dang-xuat':
            (new AuthController())->logout();
            break;

        case 'tai-khoan':
            (new AccountController())->dispatch(array_slice($segments, 1));
            break;

        case 'tin-tuc':
            (new NewsController())->index();
            break;

        case 'tin':
            (new NewsController())->show($segments[1] ?? '');
            break;

        case 'khuyen-mai':
            (new VoucherController())->index();
            break;

        case 'quyen-loi':
            (new BenefitsController())->index();
            break;

        case 'lien-he':
            (new ContactController())->index();
            break;

        case 'quan-tri':
            (new AdminController())->dispatch(array_slice($segments, 1));
            break;

        default:
            http_response_code(404);
            require_once BASE_PATH . '/web/views/404.php';
    }
} catch (\Throwable $e) {
    write_log('error', '[Route ' . $route . '] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    // Request AJAX (popup auth, tìm kiếm...) phải nhận JSON để frontend hiển thị đúng thông báo,
    // tránh client parse nhầm trang HTML lỗi thô gây fallback chung chung.
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        json_response(['ok' => false, 'message' => 'Hệ thống đang gặp sự cố. Vui lòng thử lại sau.'], 500);
    }
    http_response_code(500);
    require_once BASE_PATH . '/web/views/500.php';
}