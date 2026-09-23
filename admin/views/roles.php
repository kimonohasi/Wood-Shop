<?php
/** Phân quyền theo Chức vụ (accordion gọn - ẩn Chủ hệ thống) */
declare(strict_types=1);
$canEdit = \WoodCon\Permission::allows('roles', 'edit');
$moduleMeta = [
    'dashboard'  => ['Dashboard',             'bi-grid-1x2-fill',      '#6f42c1'],
    'orders'     => ['Đơn hàng',              'bi-receipt-cutoff',     '#0d6efd'],
    'approvals'  => ['Kiểm duyệt hủy/hoàn',  'bi-clipboard2-check-fill', '#0d6efd'],
    'invoices'   => ['Hóa đơn',               'bi-file-earmark-text',  '#0dcaf0'],
    'products'   => ['Sản phẩm',              'bi-box-seam-fill',      '#198754'],
    'categories' => ['Danh mục',              'bi-diagram-3-fill',     '#fd7e14'],
    'customers'  => ['Khách hàng',            'bi-people-fill',        '#20c997'],
    'vouchers'   => ['Khuyến mãi',            'bi-ticket-perforated-fill', '#6f42c1'],
    'banners'    => ['Banner',                'bi-images',             '#e83e8c'],
    'news'       => ['Tin tức',               'bi-newspaper',          '#17a2b8'],
    'brands'     => ['Thương hiệu',           'bi-shop',               '#fd7e14'],
    'taxes'      => ['Thuế (VAT)',            'bi-percent',            '#6c757d'],
    'warranties' => ['Bảo hành',              'bi-tools',              '#a0522d'],
    'contacts'   => ['Liên hệ / CSKH',        'bi-chat-dots-fill',     '#d63384'],
    'reviews'    => ['Đánh giá',              'bi-star-fill',          '#ffc107'],
    'reports'    => ['Báo cáo / Doanh thu',   'bi-bar-chart-fill',     '#dc3545'],
    'staffs'     => ['Nhân sự',               'bi-person-gear',        '#495057'],
];
// Lọc bỏ Chủ hệ thống (không cần hiển thị)
$roles = array_values(array_filter($roles, fn($r) => !(($r['code'] ?? '') === 'superadmin' || (int)($r['is_system'] ?? 0) === 1)));
$fullChecked = [];
foreach ($roles as $__r) {
    $id = (int)$__r['id'];
    $perms = $rolePerms[$id] ?? [];
    $totalChecked = 0;
    foreach ($groups as $__m => $__ps) {
        $totalChecked += count(array_filter($__ps, fn($p) => in_array((int)$p['id'], $perms, true)));
    }
    $fullChecked[$id] = $totalChecked;
}
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0">Phân quyền theo Chức vụ</h1>
        <span class="text-muted small">Nhấn vào từng nhóm để mở/đóng. Quyền quản trị nhạy cảm (Phân quyền, Giám sát, Cấu hình) chỉ dành cho Chủ hệ thống — không hiển thị ở đây.</span>
    </div>
</div>

<div class="row g-3 mt-1 permission-cards">
    <?php if (empty($roles)): ?>
        <div class="admin-card"><div class="card-body text-muted">Không có Chức vụ nào để phân quyền.</div></div>
    <?php endif; ?>
    <?php foreach ($roles as $__r): ?>
        <?php $__id = (int)$__r['id']; ?>
        <?php $__rolePerms = $rolePerms[$__id] ?? []; ?>
        <div class="col-12 col-lg-6 col-xxl-4">
            <div class="admin-card perm-card h-100 d-flex flex-column">
                <div class="card-head d-flex align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="perm-role-avatar"><?= e(mb_strtoupper(mb_substr($__r['name'], 0, 1))) ?></span>
                        <div>
                            <div class="fw-bold"><?= e($__r['name']) ?></div>
                            <div class="small text-muted"><?= (int)$__r['member_count'] ?> người · <span class="role-count-badge" data-role="<?= $__id ?>"><?= $fullChecked[$__id] ?></span> quyền đã cấp</div>
                        </div>
                    </div>
                    <?php if ($canEdit): ?>
                        <button class="btn btn-primary btn-sm save-role" data-role="<?= $__id ?>" data-name="<?= e($__r['name']) ?>"><?= icon('bi-check2', 'me-1') ?>Lưu</button>
                    <?php endif; ?>
                </div>
                <div class="card-body pt-2 perm-accordion">
                    <?php foreach ($groups as $__module => $__perms): ?>
                        <?php $meta = $moduleMeta[$__module] ?? [ucfirst($__module), 'bi-grid', '#6c757d']; ?>
                        <?php $checkedCount = count(array_filter($__perms, fn($__p) => in_array((int)$__p['id'], $__rolePerms, true))); ?>
                        <?php $checkedAll = $checkedCount === count($__perms); ?>
                        <div class="perm-module">
                            <button type="button" class="perm-module-head perm-toggle" data-role="<?= $__id ?>" data-module="<?= e($__module) ?>">
                                <span class="d-flex align-items-center gap-2">
                                    <?= icon($meta[1], '', 'style="color:' . e($meta[2]) . '"') ?>
                                    <span class="fw-semibold"><?= e($meta[0]) ?></span>
                                    <span class="perm-count-pill <?= $checkedCount === 0 ? '' : 'has' ?> <?= $checkedAll ? 'all' : '' ?>" data-role="<?= $__id ?>" data-module="<?= e($__module) ?>"><?= $checkedCount ?>/<?= count($__perms) ?></span>
                                </span>
                                <?= icon('bi-chevron-down', 'perm-chevron') ?>
                            </button>
                            <div class="perm-body" hidden>
                                <div class="perm-checks">
                                    <?php foreach ($__perms as $__p): ?>
                                        <?php $checked = in_array((int)$__p['id'], $__rolePerms, true); ?>
                                        <label class="perm-check <?= !$canEdit ? 'is-readonly' : '' ?>">
                                            <input class="form-check-input perm-<?= $__id ?> module-<?= $__id ?>-<?= e($__module) ?>"
                                                   type="checkbox" value="<?= (int)$__p['id'] ?>" data-role="<?= $__id ?>"
                                                   data-module="<?= e($__module) ?>" <?= $checked ? 'checked' : '' ?> <?= $canEdit ? '' : 'disabled' ?>>
                                            <span class="perm-check-label"><?= e($__p['label']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($canEdit): ?>
                                    <div class="perm-body-actions">
                                        <a href="#" class="toggle-mod text-decoration-none small" data-role="<?= $__id ?>" data-module="<?= e($__module) ?>" data-state="all">Chọn tất cả</a>
                                        <span class="text-muted mx-1">·</span>
                                        <a href="#" class="toggle-mod text-decoration-none small" data-role="<?= $__id ?>" data-module="<?= e($__module) ?>" data-state="none">Bỏ chọn</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
.permission-cards .admin-card{ transition:box-shadow .2s; }
.permission-cards .admin-card:hover{ box-shadow:0 8px 24px rgba(0,0,0,.08); }
.perm-role-avatar{ width:40px;height:40px;border-radius:.9rem;display:inline-flex;align-items:center;justify-content:center;font-weight:700;color:#fff;flex-shrink:0;
  background:linear-gradient(135deg,var(--wc-primary,#8b5a2b),var(--wc-secondary,#d4a26a)); }
.perm-card .card-body{ overflow-y:auto; }
.perm-accordion{ display:flex;flex-direction:column;gap:.5rem; }
.perm-module{ border:1px solid var(--wc-border,#e5e5e5);border-radius:.7rem;overflow:hidden; }
.perm-module.open{ border-color:var(--wc-primary,#8b5a2b); }
.perm-toggle{ width:100%;display:flex;align-items:center;justify-content:space-between;gap:.5rem;border:0;background:transparent;
  padding:.6rem .8rem;text-align:left;color:inherit;cursor:pointer;font-size:.9rem; }
.perm-toggle:hover{ background:color-mix(in srgb,var(--wc-primary,#8b5a2b) 6%,transparent); }
.perm-chevron{ transition:transform .2s ease;color:var(--wc-muted); }
.perm-module.open .perm-chevron{ transform:rotate(180deg); }
.perm-count-pill{ font-weight:600;font-size:.72rem;padding:.1rem .5rem;border-radius:999px;background:var(--wc-border,#e5e5e5);color:var(--wc-muted); }
.perm-count-pill.has{ background:var(--wc-primary,#8b5a2b);color:#fff; }
.perm-count-pill.all{ background:var(--wc-secondary,#d4a26a); }
.perm-body{ padding:.2rem .8rem .7rem; }
.perm-checks{ display:grid;grid-template-columns:1fr 1fr;gap:.25rem .5rem;margin-bottom:.5rem; }
.perm-check{ display:flex;align-items:center;gap:.45rem;padding:.25rem .4rem;border-radius:.45rem;cursor:pointer;font-size:.85rem; }
.perm-check:hover{ background:color-mix(in srgb,var(--wc-primary,#8b5a2b) 6%,transparent); }
.perm-check.is-readonly{ cursor:default; }
.perm-check input{ margin-top:0; }
.perm-check-label{ line-height:1.3; }
.perm-body-actions{ padding-top:.4rem;border-top:1px dashed var(--wc-border,#e5e5e5); }
@media (max-width:576px){ .perm-checks{ grid-template-columns:1fr; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Mở/đóng accordion - chỉ mở 1 module tại 1 thời điểm trong từng card
  document.querySelectorAll('.perm-toggle').forEach(btn => {
    btn.addEventListener('click', function () {
      const card = this.closest('.perm-card');
      const body = this.nextElementSibling;
      const wasOpen = body.hidden === false;
      card.querySelectorAll('.perm-module').forEach(m => {
        m.classList.remove('open');
        const b = m.querySelector('.perm-body');
        if (b) b.hidden = true;
      });
      if (!wasOpen) {
        this.closest('.perm-module').classList.add('open');
        body.hidden = false;
      }
    });
  });

  // Chọn/Bỏ tất theo module trong từng role
  document.querySelectorAll('.toggle-mod').forEach(a => {
    a.addEventListener('click', function (e) {
      e.preventDefault();
      const role = this.dataset.role, mod = this.dataset.module, state = this.dataset.state;
      document.querySelectorAll('.module-' + role + '-' + CSS.escape(mod)).forEach(cb => { cb.checked = (state === 'all'); });
      refreshRole(role);
    });
  });

  // Cập nhật số quyền khi click checkbox
  document.querySelectorAll('.perm-check input').forEach(cb => {
    cb.addEventListener('change', function () {
      refreshRole(this.dataset.role);
    });
  });

  function refreshRole(role) {
    // cập nhật pill từng module
    document.querySelectorAll('.perm-count-pill[data-role="' + role + '"]').forEach(pill => {
      const mod = pill.dataset.module;
      const cbs = document.querySelectorAll('.module-' + role + '-' + CSS.escape(mod));
      const checked = Array.prototype.filter.call(cbs, c => c.checked).length;
      pill.textContent = checked + '/' + cbs.length;
      pill.classList.toggle('has', checked > 0);
      pill.classList.toggle('all', checked === cbs.length);
    });
    // cập nhật tổng "x quyền đã cấp" ở header card
    const badge = document.querySelector('.role-count-badge[data-role="' + role + '"]');
    if (badge) {
      let total = 0;
      document.querySelectorAll('.perm-count-pill[data-role="' + role + '"]').forEach(pill => {
        total += parseInt(pill.textContent.split('/')[0], 10) || 0;
      });
      badge.textContent = total;
    }
  }

  // Lưu quyền theo role
  document.querySelectorAll('.save-role').forEach(btn => {
    btn.addEventListener('click', function () {
      const roleId = this.dataset.role, name = this.dataset.name;
      const perms = [];
      document.querySelectorAll('.perm-' + roleId + ':checked').forEach(cb => perms.push(cb.value));
      const c = this.innerHTML; this.disabled = true; this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
      const fd = new FormData();
      fd.append('_token', '<?= csrf_token() ?>');
      fd.append('role_id', roleId);
      perms.forEach(p => fd.append('perms[]', p));
      fetch(WOODCON_BASE_URL + '/quan-tri/phan-quyen/luu', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(res => { this.disabled = false; this.innerHTML = c; wcToast(res.message || 'Đã lưu', res.ok === false ? 'danger' : 'success'); })
        .catch(() => { this.disabled = false; this.innerHTML = c; wcToast('Lỗi kết nối, vui lòng thử lại.', 'danger'); });
    });
  });
});
</script>
