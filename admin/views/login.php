<?php
/** Đăng nhập admin - giao diện độc lập (split-screen, tối giản) */
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme.css?v=15">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin-theme.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin-login.css?v=2">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/password-toggle.css">
</head>
<body class="admin-layout admin-login-page">

    <!-- ===== NÚT ĐỔI GIAO DIỆN (giữ nguyên cơ chế cũ: localStorage + WCThemeReveal) ===== -->
    <button class="admin-login-toggle admin-theme-toggle" type="button" id="loginThemeToggle" title="Chế độ sáng/tối" aria-label="Chế độ sáng/tối">
        <?= icon('bi-moon-stars') ?>
    </button>

    <div class="admin-login">

        <!-- ===== CỘT TRÁI - BRAND (ẩn trên mobile) ===== -->
        <aside class="admin-login-brand">
            <div class="admin-login-grain"></div>
            <div class="admin-login-brand__content">
                <div class="admin-login-brand__logo">
                    <span class="admin-login-brand__mark">W</span>
                    <span class="admin-login-brand__wordmark">
                        <b>WoodCon</b>
                        <span>Nội thất gỗ cao cấp</span>
                    </span>
                </div>
                <h1 class="admin-login-brand__title">
                    Vận hành &amp; kiểm soát <em>toàn bộ</em> WoodCon
                </h1>
                <p class="admin-login-brand__sub">
                    Trung tâm quản trị tập trung mọi hoạt động bán hàng, kho hàng
                    và chăm sóc khách hàng của hệ thống nội thất gỗ cao cấp.
                </p>
                <ul class="admin-login-brand__points">
                    <li class="admin-login-point">
                        <span class="admin-login-point__icon"><?= icon('bi-basket') ?></span>
                        <span><b>Quản lý đơn hàng</b><small>Theo dõi trạng thái &amp; thanh toán</small></span>
                    </li>
                    <li class="admin-login-point">
                        <span class="admin-login-point__icon"><?= icon('bi-boxes') ?></span>
                        <span><b>Kho hàng</b><small>Kiểm soát tồn kho theo thời gian thực</small></span>
                    </li>
                    <li class="admin-login-point">
                        <span class="admin-login-point__icon"><?= icon('bi-people') ?></span>
                        <span><b>Khách hàng</b><small>Hồ sơ &amp; lịch sử mua sắm</small></span>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- ===== CỘT PHẢI - FORM ===== -->
        <main class="admin-login-panel">
            <div class="admin-login-form-wrap" id="loginFormWrap">

                <!-- Logo nhỏ (mobile) -->
                <div class="admin-login-mobile-brand">
                    <span class="admin-login-brand__mark">W</span>
                    <span>
                        <b>WoodCon</b>
                        <i>Nội thất gỗ cao cấp</i>
                    </span>
                </div>

                <div class="admin-login-card" id="loginCard">

                    <h2 class="admin-login-card__title">Đăng nhập</h2>
                    <p class="admin-login-card__sub">Vui lòng nhập thông tin tài khoản để tiếp tục.</p>

                    <!-- GIỮ NGUYÊN - LOGIC ĐĂNG NHẬP: method=post (không action), CSRF, name/id/type các input, submit thường -->
                    <form method="post" id="adminLoginForm" autocomplete="on">
                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">

                        <div class="admin-login-field">
                            <label class="admin-login-field-label" for="loginEmail">Tài khoản</label>
                            <input type="email" id="loginEmail" name="email" class="admin-login-input"
                                   placeholder="Nhập tài khoản" required autofocus
                                   autocomplete="username"
                                   <?= !empty($error) ? 'aria-invalid="true"' : '' ?>>
                        </div>

                        <div class="admin-login-field">
                            <label class="admin-login-field-label" for="loginPass">Mật khẩu</label>
                            <div class="admin-login-input-wrap">
                                <input type="password" id="loginPass" name="password" class="admin-login-input"
                                       placeholder="Nhập mật khẩu" required
autocomplete="current-password"
                                        <?= !empty($error) ? 'aria-invalid="true"' : '' ?>>
                            </div>
                        </div>

                        <!-- Xác thực reCAPTCHA - cùng cơ chế trang đăng nhập người dùng (RECAPTCHA_SITE_KEY) -->
                        <?php if (RECAPTCHA_SITE_KEY): ?>
                        <div class="admin-login-field admin-recaptcha-field">
                            <div class="g-recaptcha" id="adminRecaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
                        </div>
                        <?php endif; ?>

                        <button type="submit" class="admin-login-submit" id="loginSubmit">Đăng nhập</button>
                    </form>
                    <!-- /GIỮ NGUYÊN - LOGIC ĐĂNG NHẬP -->

                    <a href="<?= BASE_URL ?>" class="admin-login-back">
                        <?= icon('bi-arrow-left') ?>
                        <span>Về website</span>
                    </a>
                </div>

                <div class="admin-login-foot">
                    &copy; <?= date('Y') ?> WoodCon · Nội thất gỗ cao cấp
                </div>
            </div>
        </main>
    </div>

    <script>window.WOODCON_BASE_URL = '<?= BASE_URL ?>';</script>
    <script src="<?= BASE_URL ?>/assets/js/theme-view-transition.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/password-toggle.js"></script>
    <script src="https://www.google.com/recaptcha/api.js?onload=onAdminRecaptchaReady&render=explicit" async defer></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/toast.js?v=11"></script>
    <script>
        (function () {
            /* ===== GIỮ NGUYÊN - cơ chế dark mode cũ (localStorage 'woodcon_theme' + WCThemeReveal) ===== */
            var saved = localStorage.getItem('woodcon_theme');
            if (saved === 'dark') { document.body.classList.add('theme-dark'); document.documentElement.setAttribute('data-bs-theme','dark'); }
            var btn = document.getElementById('loginThemeToggle');
            if (btn) btn.addEventListener('click', function () {
                var flip = function () {
                    var d = document.body.classList.toggle('theme-dark');
                    localStorage.setItem('woodcon_theme', d ? 'dark' : 'light');
                    document.documentElement.setAttribute('data-bs-theme', d ? 'dark' : 'light');
                    var use = btn.querySelector('use');
                    if (use) use.setAttribute('href', window.WOODCON_BASE_URL + '/assets/icons/sprite.svg#' + (d ? 'bi-sun' : 'bi-moon-stars'));
                    renderAdminCaptcha(); // đổi theme widget reCAPTCHA theo dark mode
                };
                if (window.WCThemeReveal) { window.WCThemeReveal.fromButton(btn, flip); }
                else { flip(); }
            });

            /* ===== Xác thực reCAPTCHA - render explicit để theo dark mode ===== */
            window.onAdminRecaptchaReady = renderAdminCaptcha;
            function renderAdminCaptcha() {
                var el = document.getElementById('adminRecaptcha');
                if (!el || !window.grecaptcha || !window.grecaptcha.render) return;
                var dark = document.body.classList.contains('theme-dark');
                el.innerHTML = '';
                window.grecaptcha.render(el, {
                    sitekey: el.getAttribute('data-sitekey'),
                    theme: dark ? 'dark' : 'light'
                });
            }
            if (window.grecaptcha) renderAdminCaptcha();

            /* ===== MỚI - trạng thái loading khi submit, chặn double-submit ===== */
            var form = document.getElementById('adminLoginForm');
            if (form) form.addEventListener('submit', function () {
                var btnSub = document.getElementById('loginSubmit');
                if (btnSub) {
                    btnSub.disabled = true;
                    btnSub.classList.add('is-loading');
                    btnSub.setAttribute('aria-busy', 'true');
                }
            });

            /* ===== MỚI - hiệu ứng shake + focus ô tài khoản khi có lỗi ===== */
            <?php if (!empty($error)): ?>
            window.addEventListener('DOMContentLoaded', function () {
                var card = document.getElementById('loginCard');
                if (card) {
                    card.classList.add('admin-login-shake');
                    setTimeout(function () { card.classList.remove('admin-login-shake'); }, 600);
                }
                var email = document.getElementById('loginEmail');
                if (email && !email.value) email.focus();
                if (window.showToast) showToast(<?= json_encode((string)$error, JSON_UNESCAPED_UNICODE) ?>, 'error');
            });
            <?php endif; ?>
        })();
    </script>
    <div id="toast-container"></div>
</body>
</html>