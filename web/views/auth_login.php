<?php
/** Đăng nhập */
declare(strict_types=1);
?>
<div class="auth-split">

    <!-- Panel ảnh thương hiệu (desktop >= 992px) -->
    <aside class="auth-visual" aria-hidden="true">
        <img src="<?= BASE_URL ?>/assets/images/shop/dining-wood.jpg" alt="">
    </aside>

    <section class="auth-form-panel">
        <div class="auth-form-inner" style="max-width:440px">
            <div class="text-center mb-4">
                <h1 class="app-section-title">Đăng nhập</h1>
                <p class="text-muted small">Chào mừng bạn quay lại với WoodCon</p>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    <form method="post">
                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                        <div class="mb-3">
                            <label class="form-label small">Tài khoản (email hoặc số điện thoại)</label>
                            <input type="text" class="form-control" name="account" required autofocus autocomplete="username">
                            <div class="form-text">Có thể dùng email hoặc số điện thoại đã đăng ký.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Mật khẩu</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <a class="small" href="<?= BASE_URL ?>/quen-mat-khau">Quên mật khẩu?</a>
                        </div>
                        <?php if (RECAPTCHA_SITE_KEY): ?>
                        <div class="mb-3">
                            <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
                        </div>
                        <?php endif; ?>
                        <button class="btn btn-primary w-100">Đăng nhập</button>
                        <div class="auth-or my-3"><span>hoặc</span></div>
                        <a class="auth-google-btn w-100 text-decoration-none" href="<?= BASE_URL ?>/dang-nhap/google?next=<?= e(urlencode($next ?? BASE_URL)) ?>">
                            <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
                                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                            </svg>
                            Đăng nhập bằng Google
                        </a>
                    </form>
                </div>
            </div>
            <p class="text-center small mt-3 text-muted">Chưa có tài khoản? <a href="<?= BASE_URL ?>/dang-ky">Đăng ký ngay</a></p>
        </div>
    </section>
</div>