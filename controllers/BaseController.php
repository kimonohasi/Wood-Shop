<?php
/**
 * WoodCon - Base Controller
 * Cung cấp render layout + trợ giúp request/validation chung cho mọi controller.
 */

declare(strict_types=1);

namespace WoodCon\Controllers;

class BaseController
{
    /** Render trang web hoàn chỉnh (header + view + footer - header/footer CHỈ định nghĩa ở đây) */
    protected function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $pageTitle = $pageTitle ?? 'WoodCon - Nội thất gỗ cao cấp';
        require BASE_PATH . '/web/includes/header.php';
        require BASE_PATH . '/web/views/' . $view . '.php';
        require BASE_PATH . '/web/includes/footer.php';
        exit;
    }

    /** Render layout tối giản riêng cho nhóm trang xác thực (header/footer riêng, chỉ logo) */
    protected function renderAuth(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $pageTitle = $pageTitle ?? 'WoodCon - Nội thất gỗ cao cấp';
        require BASE_PATH . '/web/includes/auth_header.php';
        require BASE_PATH . '/web/views/' . $view . '.php';
        require BASE_PATH . '/web/includes/auth_footer.php';
        exit;
    }

    /** Render view không layout (dùng cho ajax/partial) */
    protected function partial(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require BASE_PATH . '/web/views/' . $view . '.php';
    }

    protected function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    /** Request AJAX (fetch từ phía client) */
    protected function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    protected function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    protected function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    protected function json(array $data, int $code = 200): never
    {
        json_response($data, $code);
    }

    protected function requireLogin(): void
    {
        require_login();
    }
}