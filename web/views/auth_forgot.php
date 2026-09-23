<?php
/** Quên mật khẩu - bước gửi OTP / xác minh (hỗ trợ Email + SMS) */
declare(strict_types=1);
$__method = $method ?? 'email';
$__account = $account ?? '';
?>
<div class="auth-split">

    <!-- Panel ảnh thương hiệu (desktop >= 992px) -->
    <aside class="auth-visual" aria-hidden="true">
        <img src="<?= BASE_URL ?>/assets/images/shop/table-wood.jpg" alt="">
    </aside>

    <section class="auth-form-panel">
        <div class="auth-form-inner" style="max-width:440px">
            <div class="text-center mb-4">
                <h1 class="app-section-title">Quên mật khẩu</h1>
                <p class="text-muted small">Nhập email hoặc số điện thoại để nhận mã xác nhận đặt lại mật khẩu</p>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    <?php if ($step === 'send'): ?>
                        <!-- Chọn cách nhận mã -->
                        <div class="d-flex gap-2 mb-3">
                            <button type="button" class="btn btn-sm flex-fill fw-semibold method-tab <?= $__method === 'email' ? 'btn-primary' : 'btn-outline-secondary' ?>"
                                    data-method="email" onclick="switchMethod('email')">
                                <?= icon('ms-mail', 'style="font-size:18px;vertical-align:-4px"') ?> Qua Email
                            </button>
                            <button type="button" class="btn btn-sm flex-fill fw-semibold method-tab <?= $__method === 'phone' ? 'btn-primary' : 'btn-outline-secondary' ?>"
                                    data-method="phone" onclick="switchMethod('phone')">
                                <?= icon('ms-sms', 'style="font-size:18px;vertical-align:-4px"') ?> Qua SMS
                            </button>
                        </div>

                        <form method="post" id="forgotForm">
                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="step" value="send">
                            <input type="hidden" name="method" id="methodField" value="<?= e($__method) ?>">

                            <!-- Email field -->
                            <div class="mb-3" id="emailField" style="<?= $__method === 'email' ? '' : 'display:none' ?>">
                                <label class="form-label small">Email</label>
                                <input type="email" class="form-control" name="email" placeholder="vd: an@example.com"
                                       value="<?= e($__account) ?>" <?= $__method === 'email' ? 'required autofocus' : '' ?>>
                            </div>

                            <!-- Phone field -->
                            <div class="mb-3" id="phoneField" style="<?= $__method === 'phone' ? '' : 'display:none' ?>">
                                <label class="form-label small">Số điện thoại</label>
                                <input type="tel" class="form-control" name="phone" placeholder="vd: 0912345678"
                                       value="<?= e($__account) ?>" <?= $__method === 'phone' ? 'required autofocus' : '' ?>>
                            </div>

                            <button class="btn btn-primary w-100" type="submit">Gửi mã xác nhận</button>
                        </form>

                    <?php else: ?>
                        <!-- Nhập OTP + mật khẩu mới -->
                        <form method="post">
                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="step" value="reset">
                            <input type="hidden" name="account" value="<?= e($__account) ?>">
                            <input type="hidden" name="method" value="<?= e($__method) ?>">

                            <div class="mb-3">
                                <label class="form-label small">Mã xác nhận (OTP)</label>
                                <input type="text" class="form-control form-control-lg text-center"
                                       name="otp" maxlength="6" pattern="[0-9]{6}" required autofocus
                                       style="letter-spacing:8px;font-size:1.4rem;font-weight:700;" placeholder="000000">
                                <div class="form-text">
                                    <?php if ($__method === 'email'): ?>
                                        Mã xác nhận đã được gửi đến email <?= e($__account) ?>
                                    <?php else: ?>
                                        Mã xác nhận đã được gửi đến số điện thoại <?= e($__account) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Mật khẩu mới</label>
                                <input type="password" class="form-control" name="password" minlength="6" required>
                            </div>
                            <button class="btn btn-primary w-100">Đặt lại mật khẩu</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <p class="text-center small mt-3 text-muted">Đã nhớ ra mật khẩu? <a href="<?= BASE_URL ?>/dang-nhap">Đăng nhập</a></p>
        </div>
    </section>
</div>

<script>
function switchMethod(method) {
    document.getElementById('methodField').value = method;
    document.getElementById('emailField').style.display = method === 'email' ? '' : 'none';
    document.getElementById('phoneField').style.display = method === 'phone' ? '' : 'none';
    document.querySelectorAll('.method-tab').forEach(btn => {
        const isActive = btn.dataset.method === method;
        btn.className = 'btn btn-sm flex-fill fw-semibold method-tab ' + (isActive ? 'btn-primary' : 'btn-outline-secondary');
    });
    if (method === 'email') {
        document.querySelector('#emailField input').focus();
    } else {
        document.querySelector('#phoneField input').focus();
    }
}
</script>