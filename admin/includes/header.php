<?php
/**
 * WoodCon Admin - Header layout (sidebar + topbar)
 * Header/footer admin CHỈ định nghĩa ở đây, mọi trang admin đều include qua adminRender().
 * Giai đoạn 5: chức năng trước. Giao diện giai đoạn 6-7 sẽ thay áo theo PROMPT_MASTER_ADMIN_UI.
 */

declare(strict_types=1);

use WoodCon\Admin;
use WoodCon\Order;

$__admin = $admin ?? Admin::current();
$__currentPath = $_SERVER['REQUEST_URI'] ?? '';
$__segments = explode('/', trim(parse_url($__currentPath, PHP_URL_PATH) ?? '', '/'));
$__adminSection = $__segments[array_key_last($__segments)] ?? '';
// Xác định section đang active: lấy segment ngay sau 'quan-tri'
$_section = '';
$qsIdx = array_search('quan-tri', $__segments, true);
if ($qsIdx !== false && isset($__segments[$qsIdx + 1])) {
    $_section = $__segments[$qsIdx + 1];
}
$__nav = [
    ''            => ['label' => 'Dashboard',                'icon' => 'bi-grid-1x2-fill',        'perm' => ['dashboard', 'view']],
    'san-pham'    => ['label' => 'Sản phẩm',                 'icon' => 'bi-box-seam-fill',        'perm' => ['products', 'view']],
    'danh-muc'    => ['label' => 'Danh mục',                 'icon' => 'bi-diagram-3-fill',       'perm' => ['categories', 'view']],
    'thuong-hieu' => ['label' => 'Thương hiệu',              'icon' => 'bi-shop',                 'perm' => ['brands', 'view']],
    'voucher'     => ['label' => 'Khuyến mãi',               'icon' => 'bi-ticket-perforated-fill', 'perm' => ['vouchers', 'view']],
    'don-hang'    => ['label' => 'Đơn hàng',                 'icon' => 'bi-receipt-cutoff',       'perm' => ['orders', 'view']],
    'hoa-don'     => ['label' => 'Hóa đơn',                  'icon' => 'bi-file-earmark-text',    'perm' => ['invoices', 'view']],
    'bao-hanh'    => ['label' => 'Bảo hành',                 'icon' => 'bi-tools',                'perm' => ['warranties', 'view']],
    'duyet'       => ['label' => 'Duyệt hủy/hoàn',           'icon' => 'bi-clipboard2-check-fill', 'perm' => ['orders', 'edit']],
    'danh-gia'    => ['label' => 'Đánh giá',                 'icon' => 'bi-star-fill',            'perm' => ['reviews', 'view']],
    'khach-hang'  => ['label' => 'Khách hàng',               'icon' => 'bi-people-fill',          'perm' => ['customers', 'view']],
    'bao-cao'     => ['label' => 'Báo cáo',                  'icon' => 'bi-bar-chart-fill',       'perm' => ['reports', 'view']],
    'banner'      => ['label' => 'Banner',                   'icon' => 'bi-images',               'perm' => ['banners', 'view']],
    'tin-tuc'     => ['label' => 'Tin tức',                  'icon' => 'bi-newspaper',            'perm' => ['news', 'view']],
    'lien-he'     => ['label' => 'Liên hệ',                  'icon' => 'bi-chat-dots-fill',       'perm' => ['contacts', 'view']],
    'thue'        => ['label' => 'Thuế',                     'icon' => 'bi-percent',              'perm' => ['taxes', 'view']],
    'cai-dat'     => ['label' => 'Cài đặt',                  'icon' => 'bi-gear-fill',            'perm' => ['settings', 'edit']],
    'van-chuyen'  => ['label' => 'Vận chuyển',                'icon' => 'bi-truck',                'perm' => ['settings', 'edit']],
    'nhan-su'     => ['label' => 'Nhân sự',                  'icon' => 'bi-people-fill',          'perm' => ['staffs', 'view']],
    'phan-quyen'  => ['label' => 'Phân quyền',               'icon' => 'bi-shield-lock-fill',     'perm' => ['roles', 'view']],
    'nhat-ky'     => ['label' => 'Nhật ký',                  'icon' => 'bi-journal-code',         'perm' => ['audit', 'view']],
];
// Nhóm menu (label => [các key])
$__navGroups = [
    'Tổng quan'    => [''],
    'Cửa hàng'     => ['san-pham', 'danh-muc', 'thuong-hieu', 'voucher'],
    'Đơn hàng'     => ['don-hang', 'hoa-don', 'bao-hanh', 'duyet'],
    'Khách hàng'   => ['danh-gia', 'khach-hang'],
    'Vận hành'     => ['bao-cao', 'banner', 'tin-tuc', 'lien-he', 'van-chuyen'],
    'Hệ thống'     => ['thue', 'cai-dat', 'nhan-su', 'phan-quyen', 'nhat-ky'],
];
$__flatNav = [];
foreach ($__navGroups as $__g => $__keys) {
    foreach ($__keys as $__k) {
        $__item = $__nav[$__k];
        if (!empty($__item['perm']) && !\WoodCon\Permission::allows($__item['perm'][0], $__item['perm'][1])) {
            continue;
        }
        $__flatNav[$__k] = $__item;
    }
}

// Số yêu cầu chờ xử lý (hủy đơn + hoàn tiền)
$__pendingApprovals = 0;
try {
    $__pendingApprovals = (int)Admin::db()->query("SELECT
        (SELECT COUNT(*) FROM order_cancel_requests WHERE status='pending') +
        (SELECT COUNT(*) FROM refunds WHERE status='pending') AS c")->fetch()['c'];
} catch (\Throwable $__e) {
    $__pendingApprovals = 0;
}
// Số đánh giá chờ duyệt
$__pendingReviews = 0;
try {
    $__pendingReviews = (int)Admin::db()->query("SELECT COUNT(*) FROM reviews WHERE status='pending'")->fetchColumn();
} catch (\Throwable $__e) {
    $__pendingReviews = 0;
}
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme.css?v=15">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin-theme.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin-mobile.css?v=1">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme-view-transition.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/goong-autocomplete.css?v=1">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/password-toggle.css">
</head>
<body class="admin-layout">
<script>
    (function () {
        try {
            if (localStorage.getItem('woodcon_theme') === 'dark') {
                document.documentElement.setAttribute('data-bs-theme', 'dark');
                document.documentElement.style.setProperty('background-color', '#0f0f11');
                document.body.classList.add('theme-dark');
            }
        } catch (e) {}
    })();
</script>

<div class="d-flex">

    <!-- ===== SIDEBAR ===== -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-inner">
            <div class="d-flex align-items-center gap-2 px-3 py-3 border-bottom admin-brand-box">
                <a class="app-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/quan-tri" style="text-decoration:none">
                    <span class="app-brand-mark">W</span>
                    <span class="app-brand-text fw-bold">Wood<span style="color:var(--wc-secondary)">Con</span></span>
                </a>
                <button class="admin-collapse-toggle btn btn-sm border-0 ms-auto p-1 d-none d-lg-inline-flex" type="button" title="Thu gọn menu" aria-label="Thu gọn menu" style="color:var(--wc-muted)">
                    <?= icon('bi-arrow-left') ?>
                </button>
            </div>
            <nav class="p-2 d-grid gap-1 flex-grow-1">
                <?php foreach ($__navGroups as $__g => $__keys): ?>
                    <div class="admin-nav-label"><?= e($__g) ?></div>
                    <?php foreach ($__keys as $__key): ?>
                        <?php $__item = $__nav[$__key]; ?>
                        <?php if (!empty($__item['perm']) && !\WoodCon\Permission::allows($__item['perm'][0], $__item['perm'][1])): ?>
                            <?php continue; ?>
                        <?php endif; ?>
                        <?php $__href = $__key === '' ? BASE_URL . '/quan-tri' : BASE_URL . '/quan-tri/' . $__key; ?>
                        <?php $__active = ($_section === $__key); ?>
                        <a class="admin-nav-item <?= $__active ? 'active' : '' ?>" href="<?= e($__href) ?>" data-tip="<?= e($__item['label']) ?>">
                            <?= icon($__item['icon']) ?>
                            <span><?= e($__item['label']) ?></span>
                            <?php if ($__key === 'duyet' && $__pendingApprovals > 0): ?>
                                <span class="badge text-bg-danger ms-auto"><?= $__pendingApprovals ?></span>
                            <?php elseif ($__key === 'danh-gia' && $__pendingReviews > 0): ?>
                                <span class="badge text-bg-danger ms-auto"><?= $__pendingReviews ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </nav>
            <div class="admin-sidebar-foot d-grid gap-2">
                <a href="<?= BASE_URL ?>" target="_blank" rel="noopener" class="btn btn-sm w-100 admin-foot-sitebtn"><?= icon('bi-box-arrow-up-right', 'me-1') ?><span>Về website</span></a>
                <button class="btn btn-sm btn-outline-danger w-100" onclick="location.href='<?= BASE_URL ?>/quan-tri/dang-xuat'"><?= icon('bi-box-arrow-right', 'me-1') ?><span>Đăng xuất</span></button>
            </div>
        </div>
    </aside>
    <div class="admin-sidebar-backdrop d-lg-none" onclick="document.getElementById('adminSidebar').classList.remove('open');this.classList.remove('show')"></div>

    <!-- ===== MAIN ===== -->
    <div class="flex-grow-1 d-flex flex-column min-vh-100" style="min-width:0">

        <!-- ===== TOPBAR ===== -->
        <header class="admin-topbar d-flex align-items-center gap-2 gap-lg-3 px-3 px-lg-4 py-2">
            <button class="btn btn-sm d-lg-none" type="button" onclick="document.getElementById('adminSidebar').classList.toggle('open');document.querySelector('.admin-sidebar-backdrop')?.classList.toggle('show')"><?= icon('bi-list', 'fs-4') ?></button>
            <div class="d-none d-xl-flex align-items-center gap-2">
                <?= icon('bi-house-door', 'text-muted') ?>
                <span class="text-muted small">Quản trị</span>
                <span class="text-muted small">/</span>
                <span class="small fw-semibold"><?= e($pageTitle) ?></span>
            </div>

            <!-- Live search (điều hướng trang admin) -->
            <div class="position-relative flex-grow-1" style="max-width:420px">
                <?= icon('bi-search', 'position-absolute', 'style="left:.8rem;top:50%;transform:translateY(-50%);color:var(--wc-muted)"') ?>
                <input class="admin-search form-control form-control-sm ps-5" id="adminLiveSearch" type="text"
                       placeholder="Tìm trang, đơn hàng, khách hàng..." autocomplete="off">
                <div id="adminLiveResults" class="dropdown-menu w-100 mt-1 shadow-sm border-0" style="z-index:1200;max-height:340px;overflow:auto;display:none;border-radius:.6rem"></div>
            </div>

            <div class="d-flex align-items-center gap-2 ms-auto">
                <button class="admin-palette-trigger btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center" type="button" title="Tìm kiếm (Ctrl+K)">
                    <?= icon('bi-search') ?>
                </button>
                <button class="admin-theme-toggle btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center" type="button" title="Chế độ sáng/tối">
                    <?= icon('bi-moon-stars') ?>
                </button>
                <div class="dropdown">
                    <button class="btn btn-sm d-flex align-items-center gap-2 border-0" type="button" data-bs-toggle="dropdown">
                        <?= icon('bi-person-circle', 'fs-4', 'style="color:var(--wc-secondary)"') ?>
                        <span class="small fw-semibold d-none d-sm-inline"><?= e($__admin['name'] ?? '') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><span class="dropdown-item-text small text-muted"><?= e($__admin['email'] ?? '') ?></span></li>
                        <li><span class="dropdown-item-text small text-muted">Vai trò: <?= e(ucfirst($__admin['role'] ?? '')) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/quan-tri/dang-xuat"><?= icon('bi-box-arrow-right', 'me-2') ?>Đăng xuất</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- ===== COMMAND PALETTE (Ctrl+K) ===== -->
        <div class="modal fade" id="app-palette" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:640px">
                <div class="modal-content palette-box border-0 p-0 overflow-hidden" style="background:var(--wc-surface)">
                    <div class="palette-search d-flex align-items-center gap-2">
                        <?= icon('bi-search', 'palette-search-icon text-muted') ?>
                        <input class="palette-input py-2" id="paletteInput" type="text" placeholder="Tìm trang, đơn hàng, sản phẩm, voucher, khách hàng...">
                        <span class="admin-kbd">Esc</span>
                    </div>
                    <div id="paletteList" class="p-2 d-grid gap-1" style="max-height:360px;overflow:auto">
                        <div class="text-center text-muted small py-3">Nhập từ khóa để tìm kiếm nhanh</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== CONTENT ===== -->
        <main class="p-3 p-lg-4 flex-grow-1">