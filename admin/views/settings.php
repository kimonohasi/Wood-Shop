<?php
/** Cài đặt hệ thống admin (đọc/chỉnh sửa trực tiếp bảng settings, dạng accordion theo chủ đề) */
declare(strict_types=1);
use WoodCon\Admin;
$isSuper = Admin::isSuper();

// Các khóa chứa bí mật (mật khẩu / API key): KHÔNG đổ giá trị thật ra HTML,
// chỉ hiển thị dạng password với placeholder "đã lưu".
$__secretKeys = ['smtp_pass', 'ai_api_key', 'esms_secret', 'goong_api_key', 'google_client_secret', 'recaptcha_secret_key'];

// Nhóm cài đặt truyền từ controller (mỗi nhóm có nút Lưu riêng)
$sections = $settingsSections ?? [];
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title">Cài đặt</h1>
    </div>
    <?php if (!$isSuper): ?>
        <span class="badge text-bg-secondary">Chế độ chỉ đọc</span>
    <?php endif; ?>
</div>

<div class="accordion" id="settingsAccordion">
    <?php foreach ($sections as $__slug => $__cfg): $__title = $__cfg['title'] ?? $__slug; ?>
        <div class="accordion-item admin-card border-0 mb-2">
            <h2 class="accordion-header" id="head-<?= e($__slug) ?>">
                <button class="accordion-button collapsed px-3 py-3"
                        type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse-<?= e($__slug) ?>"
                        aria-expanded="false"
                        aria-controls="collapse-<?= e($__slug) ?>">
                    <span class="fw-semibold"><?= e($__title) ?></span>
                    <span class="ms-2 badge text-bg-light border small"><?= count($__cfg['fields'] ?? []) ?> mục</span>
                    <?php if (!empty($__cfg['status'])): $__stOk = (bool)($__cfg['status']['ok'] ?? false); ?>
                        <span class="ms-2 d-inline-flex align-items-center" title="<?= $__stOk ? 'Đã cấu hình' : 'Chưa cấu hình' ?>">
                            <?php if ($__stOk): ?>
                                <svg width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="10" fill="#22c55e"/><path d="M6 10l3 3 5-5" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <?php else: ?>
                                <svg width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="10" fill="#ef4444"/><path d="M7 7l6 6M13 7l-6 6" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/></svg>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </button>
            </h2>
            <div id="collapse-<?= e($__slug) ?>"
                 class="accordion-collapse collapse"
                 data-bs-parent="#settingsAccordion"
                 aria-labelledby="head-<?= e($__slug) ?>">
                <div class="accordion-body px-3 py-3">
                    <?php if ($isSuper): ?>
                        <form method="post" action="<?= BASE_URL ?>/quan-tri/cai-dat">
                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="section" value="<?= e($__slug) ?>">
                            <?php if (!empty($__cfg['note'])): ?>
                                <div class="alert alert-light border small text-muted mb-3 p-2 px-3"><?= nl2br(e((string)$__cfg['note'])) ?></div>
                            <?php endif; ?>
                            <div class="d-grid gap-3">
                                <?php foreach (($__cfg['fields'] ?? []) as $__key => $__label):
                                    $__cfgField = is_array($__label) ? $__label : ['label' => $__label];
                                    $__label    = $__cfgField['label'] ?? $__key;
                                    $__fType    = $__cfgField['type'] ?? 'text';
                                    $__secret   = in_array($__key, $__secretKeys, true);
                                    $__val      = get_setting($__key, '');
                                    $__isSet    = is_scalar($__val) && trim((string)$__val) !== '';
                                ?>
                                    <div>
                                        <label class="form-label mb-1"><?= e($__label) ?></label>
                                        <?php if ($__fType === 'toggle'): ?>
                                            <div class="form-check form-switch toggle-row">
                                                <input type="hidden" name="setting_<?= e($__key) ?>" value="0">
                                                <input class="form-check-input" type="checkbox"
                                                       name="setting_<?= e($__key) ?>" value="1"
                                                       id="tg-<?= e($__key) ?>"
                                                       <?= ((int)$__val === 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label small text-muted" for="tg-<?= e($__key) ?>">
                                                    <?= ((int)$__val === 1) ? 'Đang bật' : 'Đang tắt' ?>
                                                </label>
                                            </div>
                                        <?php elseif ($__secret): ?>
                                            <?php // Không đổ giá trị bí mật thật ra source HTML ?>
                                            <input type="password" class="form-control form-control-sm"
                                                   name="setting_<?= e($__key) ?>"
                                                   value="" placeholder="<?= $__isSet ? '•••••••• (đã lưu)' : 'Chưa thiết lập' ?>"
                                                   autocomplete="new-password">
                                        <?php else: ?>
                                            <input type="text" class="form-control form-control-sm"
                                                   name="setting_<?= e($__key) ?>"
                                                   value="<?= e($__isSet ? (string)$__val : '') ?>">
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                <button class="btn btn-primary px-4 btn-sm">
                                    <?= icon('bi-check2', 'me-1') ?>Lưu <?= mb_strtolower($__title) ?>
                                </button>
                            </div>
                        </form>
                        <?php if (!empty($__cfg['status'])): $__st = $__cfg['status']; $__stOk = (bool)($__st['ok'] ?? false); ?>
                            <div class="small mt-3 pt-2 border-top d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                <span class="d-flex align-items-center gap-2 text-<?= $__stOk ? 'success' : 'danger' ?>">
                                    <span id="st-<?= e($__slug) ?>-icon" class="d-inline-flex">
                                        <?php if ($__stOk): ?>
                                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="10" fill="#22c55e"/><path d="M6 10l3 3 5-5" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        <?php else: ?>
                                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="10" fill="#ef4444"/><path d="M7 7l6 6M13 7l-6 6" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/></svg>
                                        <?php endif; ?>
                                    </span>
                                    <span id="st-<?= e($__slug) ?>-msg"><?= e(trim((string)($__st['message'] ?? '')) !== '' ? (string)$__st['message'] : 'Chưa có trạng thái.') ?></span>
                                </span>
                                <?php if (!empty($__cfg['test'])): ?>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="st-<?= e($__slug) ?>-btn" data-settings-test="<?= e($__slug) ?>">
                                        <?= icon('bi-plug', 'me-1') ?>Kiểm tra kết nối
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (!$isSuper): ?>
    <div class="admin-card mt-3">
        <div class="card-body small text-muted">
            <?= icon('bi-lock', 'me-1') ?>Chỉ tài khoản super admin mới được chỉnh sửa cài đặt.
        </div>
    </div>
<?php endif; ?>

<script>
(function () {
    var baseUrl = (window.WOODCON_BASE_URL || '').replace(/\/+$/, '');
    var okIcon = '<svg width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="10" fill="#22c55e"/><path d="M6 10l3 3 5-5" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    var noIcon = '<svg width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="10" fill="#ef4444"/><path d="M7 7l6 6M13 7l-6 6" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/></svg>';
    document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('[data-settings-test]') : null;
        if (!btn) return;
        var slug = btn.getAttribute('data-settings-test');
        var tokenEl = document.querySelector('form [name="_token"]');
        var msgEl = document.getElementById('st-' + slug + '-msg');
        var iconEl = document.getElementById('st-' + slug + '-icon');
        if (msgEl) msgEl.textContent = 'Đang kiểm tra kết nối...';
        if (iconEl) iconEl.innerHTML = '<span class="spinner-border spinner-border-sm text-secondary"></span>';
        btn.disabled = true;
        var oldHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang kiểm tra...';
        var fd = new FormData();
        fd.append('_token', tokenEl ? tokenEl.value : (window.WOODCON_CSRF || ''));
        fd.append('section', slug);
        fd.append('settings_test', '1');
        fetch(baseUrl + '/quan-tri/cai-dat', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                btn.innerHTML = oldHtml;
                btn.disabled = false;
                var ok = !!(res && res.ok);
                if (msgEl) {
                    msgEl.textContent = (res && res.message) ? res.message : (ok ? 'Kết nối thành công.' : 'Kết nối thất bại.');
                    msgEl.parentElement.className = msgEl.parentElement.className.replace(/text-(success|danger)/, '') + (ok ? ' text-success' : ' text-danger');
                }
                if (iconEl) iconEl.innerHTML = ok ? okIcon : noIcon;
                if (ok && window.showToast) window.showToast('Kết nối thành công.', 'success');
            })
            .catch(function () {
                btn.innerHTML = oldHtml;
                btn.disabled = false;
                if (msgEl) msgEl.textContent = 'Không kết nối được máy chủ, vui lòng thử lại.';
                if (window.showToast) window.showToast('Lỗi khi gửi yêu cầu kiểm tra.', 'error');
            });
    });
})();
</script>
