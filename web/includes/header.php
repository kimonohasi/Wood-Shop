<?php
/**
 * WoodCon - HEADER chung (BỘ PHẬN DUY NHẤT toàn trang web - chuẩn Mộc An)
 * Quy tắc "prompt dung chung": header/footer chỉ viết 1 nơi, mọi trang phải dùng chung.
 * Dữ liệu: $pageTitle (tùy chọn), $categories (tự nạp), giỏ hàng/đăng nhập.
 */

declare(strict_types=1);

use WoodCon\Cart;

$__cartCount = Cart::countItems();
$__user = current_user();

$__route = $_GET['route'] ?? '';
$__isHome     = $__route === '';
$__isProducts = str_starts_with($__route, 'san-pham') || str_starts_with($__route, 'danh-muc') || str_starts_with($__route, 'tim-kiem');
$__isPromo    = $__route === 'khuyen-mai';
$__isBenefits = $__route === 'quyen-loi';
$__isNews     = str_starts_with($__route, 'tin');
$__isContact  = $__route === 'lien-he';

// Hạng thành viên + hạng kế tiếp (hiển thị menu tài khoản)
$__mt = null; $__nt = null;
if ($__user) {
    $__mt = \WoodCon\User::membershipTier((int)$__user['id']);
    $__nt = \WoodCon\User::nextMembershipTier((int)$__user['id']);
}
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'WoodCon - Nội thất gỗ cao cấp') ?></title>
    <meta name="description" content="<?= e($metaDescription ?? 'WoodCon - Nội thất gỗ cao cấp: sofa, bàn, giường, tủ, kệ. Giao hàng toàn quốc, bảo hành lâu dài.') ?>">
    <meta name="robots" content="index, follow">
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;700&family=Work+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme.css?v=15">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth-modal.css?v=1">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme-view-transition.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/site.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/floating-widgets.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/goong-autocomplete.css?v=1">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/password-toggle.css">
    <script>
        window.WOODCON_BASE_URL = '<?= BASE_URL ?>';
        window.WOODCON_CSRF = '<?= csrf_token() ?>';
    </script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="app-layout">
<script>
    (function () {
        try {
            if (localStorage.getItem('woodcon-theme') === 'dark') {
                document.documentElement.setAttribute('data-bs-theme', 'dark');
                document.documentElement.style.setProperty('background-color', '#1b1c1e');
                document.body.classList.add('theme-dark');
            }
        } catch (e) {}
    })();
</script>

<header class="app-header" id="appHeader">
    <div class="container d-flex align-items-center gap-2 gap-lg-4 py-3">

        <!-- Logo -->
        <a class="app-brand" href="<?= BASE_URL ?>">
            <span class="app-brand-mark">W</span>
            <span class="app-brand-text d-none d-sm-inline">Wood<span>Con</span></span>
        </a>

        <!-- NAV chính (desktop) -->
        <nav class="d-none d-lg-block ms-auto" aria-label="Menu chính">
            <ul class="app-nav-list">
                <li><a href="<?= BASE_URL ?>" class="<?= $__isHome ? 'active' : '' ?>">Trang chủ</a></li>
                <li><a href="<?= BASE_URL ?>/tim-kiem" class="<?= $__isProducts ? 'active' : '' ?>">Sản phẩm</a></li>
                <li><a href="<?= BASE_URL ?>/khuyen-mai" class="<?= $__isPromo ? 'active' : '' ?>">Khuyến mãi</a></li>
                <li><a href="<?= BASE_URL ?>/quyen-loi" class="<?= $__isBenefits ? 'active' : '' ?>">Quyền lợi</a></li>
                <li><a href="<?= BASE_URL ?>/tin-tuc" class="<?= $__isNews ? 'active' : '' ?>">Tin tức</a></li>
                <li><a href="<?= BASE_URL ?>/lien-he" class="<?= $__isContact ? 'active' : '' ?>">Liên hệ</a></li>
            </ul>
        </nav>

        <!-- Actions -->
        <div class="ms-auto d-flex align-items-center gap-1">
            <div class="app-header-icons d-flex align-items-center gap-1 me-2 me-md-3 me-lg-4">
                <button class="app-icon-btn app-theme-toggle" type="button" id="appThemeToggle" onclick="window.WOODCON_THEME_TOGGLE && window.WOODCON_THEME_TOGGLE()" title="Chế độ hiển thị" aria-label="Chế độ hiển thị">
                    <?= icon('ms-dark_mode', 'id="appThemeIcon"') ?>
                </button>

                <a class="app-icon-btn" href="<?= BASE_URL ?>/tim-kiem" title="Tìm kiếm" aria-label="Tìm kiếm">
                    <?= icon('ms-search') ?>
                </a>

                <!-- Giỏ hàng -->
                <a class="app-icon-btn" href="<?= BASE_URL ?>/gio-hang" title="Giỏ hàng">
                    <?= icon('ms-shopping_bag') ?>
                    <span class="app-badge" id="cartCount"><?= (int)$__cartCount ?></span>
                </a>
            </div>

            <?php if ($__user): ?>
                <div class="dropdown">
                    <a class="app-icon-btn" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Tài khoản">
                        <?= icon('ms-person') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li class="px-3 py-1 text-truncate"><small><strong><?= e($__user['name']) ?></strong></small></li>
                        <?php if ($__mt): ?>
                            <li class="px-3 py-1"><small class="text-muted">Hạng: <?= e($__mt['name'] ?? '') ?>
                            <?php if ((float)($__nt['min_total_spent'] ?? 0) > 0): ?>
                                <span class="d-block text-muted mt-1">Mua thêm <?= e(format_money((int)max(0, ((float)$__nt['min_total_spent'] - (float)($__user['total_spent'] ?? 0))))) ?> để lên hạng <?= e($__nt['name'] ?? '') ?></span>
                            <?php endif; ?>
                            </small></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/tai-khoan"><?= icon('ms-person_outline', 'me-2', 'style="font-size:18px"') ?>Thông tin tài khoản</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/tai-khoan/don-hang"><?= icon('ms-inventory_2', 'me-2', 'style="font-size:18px"') ?>Đơn hàng của tôi</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/tai-khoan/yeu-thich"><?= icon('ms-favorite_border', 'me-2', 'style="font-size:18px"') ?>Yêu thích</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/dang-xuat"><?= icon('ms-logout', 'me-2', 'style="font-size:18px"') ?>Đăng xuất</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <button type="button" class="app-btn-login" onclick="window.openAuthModal && window.openAuthModal('login')" title="Đăng nhập">Đăng nhập</button>
            <?php endif; ?>

            <button class="app-icon-btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-label="Menu">
                <?= icon('ms-menu') ?>
            </button>
        </div>
    </div>
</header>

<!-- Offcanvas mobile -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu">
    <div class="offcanvas-header">
        <span class="app-brand-text">Wood<span>Con</span></span>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Đóng"></button>
    </div>
    <div class="offcanvas-body">
        <a class="d-block py-2 fw-semibold border-bottom" href="<?= BASE_URL ?>">Trang chủ</a>
        <a class="d-block py-2 border-bottom" href="<?= BASE_URL ?>/tim-kiem">Sản phẩm</a>
        <a class="d-block py-2 border-bottom" href="<?= BASE_URL ?>/khuyen-mai">Khuyến mãi</a>
        <a class="d-block py-2 border-bottom" href="<?= BASE_URL ?>/quyen-loi">Quyền lợi</a>
        <a class="d-block py-2 border-bottom" href="<?= BASE_URL ?>/tin-tuc">Tin tức</a>
        <a class="d-block py-2 border-bottom" href="<?= BASE_URL ?>/lien-he">Liên hệ</a>
    </div>
</div>

<!-- Auth modal: đăng nhập / đăng ký / quên mật khẩu (Sunihost-style card) -->
<div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered auth-modal-dialog">
        <div class="modal-content auth-modal-card">
            <div class="modal-body">
                <button type="button" class="btn-close auth-modal-close" data-bs-dismiss="modal" aria-label="Đóng"></button>

                <!-- ============ Pane: ĐĂNG NHẬP ============ -->
                <section class="auth-pane" id="authPaneLogin">
                    <h2 class="auth-modal-title">Đăng nhập</h2>
                    <p class="auth-modal-sub">Đăng nhập để tiếp tục mua sắm</p>
                    <form class="auth-form mt-3" data-auth-action="login">
                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="next" class="auth-next">
                        <div class="mb-3">
                            <label class="form-label auth-label">Tên tài khoản</label>
                            <input type="text" class="form-control" name="account" required autocomplete="username" placeholder="Nhập tài khoản, số điện thoại hoặc email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label auth-label">Mật khẩu</label>
                            <input type="password" class="form-control" name="password" required autocomplete="current-password" placeholder="Nhập mật khẩu">
                        </div>
                        <div class="auth-remember mb-3">
                            <label class="d-flex align-items-center gap-2 mb-0 small">
                                <input type="checkbox" class="form-check-input mt-0" name="remember"> Ghi nhớ
                            </label>
                            <button type="button" class="btn btn-link p-0 auth-switch-link" data-auth-goto="forgot">Quên mật khẩu?</button>
                        </div>
                        <?php if (RECAPTCHA_SITE_KEY): ?>
                        <div class="auth-robot mb-3">
                            <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
                        </div>
                        <?php endif; ?>
                        <button class="btn btn-primary w-100 auth-submit" type="submit">
                            Đăng nhập
                        </button>
                        <div class="auth-or my-3"><span>hoặc</span></div>
                        <button type="button" class="auth-google-btn w-100" data-auth-google>
                            <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
                                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                            </svg>
                            Đăng nhập bằng Google
                        </button>
                        <div class="auth-error" hidden></div>
                        <p class="auth-modal-foot mt-3">Chưa có tài khoản? <button type="button" class="btn btn-link p-0 auth-switch-link" data-auth-goto="register">Đăng ký ngay</button></p>
                    </form>
                </section>

                <!-- ============ Pane: ĐĂNG KÝ ============ -->
                <section class="auth-pane" id="authPaneRegister" style="display:none">
                    <h2 class="auth-modal-title">Đăng ký tài khoản</h2>
                    <p class="auth-modal-sub">Tạo tài khoản để tích điểm và nhận ưu đãi thành viên</p>
                    <form class="auth-form mt-3" data-auth-action="register">
                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="next" class="auth-next">
                        <div class="mb-3">
                            <label class="form-label auth-label">Họ và tên</label>
                            <input type="text" class="form-control" name="name" required placeholder="Nhập họ và tên">
                        </div>
                        <div class="mb-3">
                            <label class="form-label auth-label">Tên đăng nhập</label>
                            <input type="text" class="form-control" name="username" minlength="3" maxlength="30" required
                                   placeholder="Nhập tên đăng nhập" pattern="[a-zA-Z0-9_.]+" autocomplete="username">
                        </div>
                        <div class="mb-3">
                            <label class="form-label auth-label">Email</label>
                            <input type="email" class="form-control" name="email" required placeholder="Nhập email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label auth-label">Số điện thoại</label>
                            <input type="tel" class="form-control" name="phone" required placeholder="Nhập số điện thoại">
                        </div>
                        <div class="mb-3">
                            <label class="form-label auth-label">Mật khẩu</label>
                            <input type="password" class="form-control" name="password" minlength="6" required autocomplete="new-password" placeholder="Nhập mật khẩu">
                        </div>
                        <?php if (RECAPTCHA_SITE_KEY): ?>
                        <div class="auth-robot mb-3">
                            <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
                        </div>
                        <?php endif; ?>
                        <button class="btn btn-primary w-100 auth-submit" type="submit">
                            Đăng ký
                        </button>
                        <div class="auth-or my-3"><span>hoặc</span></div>
                        <button type="button" class="auth-google-btn w-100" data-auth-google>
                            <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
                                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                            </svg>
                            Đăng ký bằng Google
                        </button>
                        <div class="auth-error" hidden></div>
                        <p class="auth-modal-foot mt-3">Đã có tài khoản? <button type="button" class="btn btn-link p-0 auth-switch-link" data-auth-goto="login">Đăng nhập</button></p>
                    </form>
                </section>

                <!-- ============ Pane: QUÊN MẬT KHẨU ============ -->
                <section class="auth-pane" id="authPaneForgot" style="display:none">
                    <h2 class="auth-modal-title">Quên mật khẩu</h2>
                    <p class="auth-modal-sub">Nhập email hoặc số điện thoại để nhận mã xác nhận đặt lại mật khẩu</p>

                    <!-- Bước 1: chọn phương thức + gửi mã -->
                    <form class="auth-form mt-3" data-auth-action="forgot-send">
                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="step" value="send">
                        <input type="hidden" name="method" id="authMethodField" value="email">
                        <div class="d-flex gap-2 mb-3">
                            <button type="button" class="btn btn-sm flex-fill fw-semibold auth-method-tab btn-primary" data-method="email">
                                Qua Email
                            </button>
                            <button type="button" class="btn btn-sm flex-fill fw-semibold auth-method-tab btn-outline-secondary" data-method="phone">
                                Qua SMS
                            </button>
                        </div>
                        <div class="mb-3" id="authEmailField">
                            <label class="form-label auth-label">Email</label>
                            <input type="email" class="form-control" name="email" required placeholder="Nhập email đã đăng ký">
                        </div>
                        <div class="mb-3" id="authPhoneField" style="display:none">
                            <label class="form-label auth-label">Số điện thoại</label>
                            <input type="tel" class="form-control" name="phone" placeholder="Nhập số điện thoại đã đăng ký">
                        </div>
                        <?php if (RECAPTCHA_SITE_KEY): ?>
                        <div class="auth-robot mb-3">
                            <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
                        </div>
                        <?php endif; ?>
                        <button class="btn btn-primary w-100 auth-submit" type="submit">
                            Gửi mã xác nhận
                        </button>
                        <div class="auth-error" hidden></div>
                        <p class="auth-modal-foot mt-3">Đã nhớ ra mật khẩu? <button type="button" class="btn btn-link p-0 auth-switch-link" data-auth-goto="login">Đăng nhập</button></p>
                    </form>

                    <!-- Bước 2: nhập OTP + mật khẩu mới -->
                    <form class="auth-form mt-3" data-auth-action="forgot-reset" id="authForgotVerify" style="display:none">
                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="step" value="reset">
                        <input type="hidden" name="account" id="authResetAccount">
                        <input type="hidden" name="method" id="authResetMethod">
                        <div class="mb-3">
                            <label class="form-label auth-label">Mã xác nhận (OTP)</label>
                            <input type="text" class="form-control form-control-lg text-center" name="otp" maxlength="6"
                                   pattern="[0-9]{6}" required inputmode="numeric" autocomplete="one-time-code"
                                   placeholder="000000" style="letter-spacing:8px;font-weight:700">
                            <div class="form-text" id="authOtpHint"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label auth-label">Mật khẩu mới</label>
                            <input type="password" class="form-control" name="password" minlength="6" required autocomplete="new-password" placeholder="Tối thiểu 6 ký tự">
                        </div>
                        <button class="btn btn-primary w-100 auth-submit" type="submit">Đặt lại mật khẩu</button>
                        <div class="auth-error" hidden></div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</div>

<main class="app-main">