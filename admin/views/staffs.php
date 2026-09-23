<?php
/** Quản lý nhân sự admin */
declare(strict_types=1);
$isSuper = \WoodCon\Permission::isSuper();
$canAdd  = \WoodCon\Permission::allows('staffs', 'add');
$canEdit = \WoodCon\Permission::allows('staffs', 'edit');
$activeCount  = 0; $lockedCount = 0;
foreach ($rows as $__r) { if ((int)$__r['is_deleted'] === 1) continue; ((int)$__r['status'] === 1) ? $activeCount++ : $lockedCount++; }
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0">Quản lý Nhân sự</h1>
    </div>
    <?php if ($canAdd): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createStaffModal"><?= icon('bi-person-plus-fill', 'me-1') ?>Thêm nhân viên</button>
    <?php endif; ?>
</div>

<div class="row g-3 mt-1 mb-3">
    <div class="col-6 col-lg-4"><div class="admin-stat"><div class="stat-label">Tổng tài khoản</div><div class="h5 mb-0 mt-1"><?= number_format($summary['total']) ?></div></div></div>
    <div class="col-6 col-lg-4"><div class="admin-stat"><div class="stat-label">Đang hoạt động</div><div class="h5 mb-0 mt-1 text-success"><?= $activeCount ?></div></div></div>
    <div class="col-6 col-lg-4"><div class="admin-stat"><div class="stat-label">Đã khóa</div><div class="h5 mb-0 mt-1 text-danger"><?= $lockedCount ?></div></div></div>
</div>

<div class="admin-card">
    <div class="card-head d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span>Danh sách tài khoản</span>
        <form class="filter-bar d-flex align-items-center gap-2" method="get">
            <input type="hidden" name="route" value="quan-tri/nhan-su">
            <div class="form-check form-switch my-0 flex-shrink-0 toggle-row">
                <input class="form-check-input" type="checkbox" name="include_deleted" value="1" id="incDel" <?= !empty($f['include_deleted']) ? 'checked' : '' ?> onchange="this.form.submit()">
                <label class="form-check-label small text-nowrap my-0" for="incDel">Gồm tài khoản đã xóa</label>
            </div>
            <input type="text" class="form-control form-control-sm" name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="Tìm tên / email / sđt" style="width:220px">
            <button class="btn btn-outline-primary btn-sm flex-shrink-0"><?= icon('bi-search') ?></button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table admin-table mb-0 align-middle">
            <thead>
                <tr><th style="width:56px">ID</th><th>Nhân viên</th><th>Chức vụ</th><th class="text-center">Trạng thái</th><th class="text-center">Thao tác</th><th class="text-end">Hành động</th></tr>
            </thead>
            <tbody id="staffRows">
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6"><div class="empty-state"><?= icon('bi-people') ?>Không có tài khoản</div></td></tr>
                <?php else: foreach ($rows as $__s): ?>
                    <?php $isOwner = ($__s['role'] ?? '') === 'superadmin' || ($__s['role_code'] ?? '') === 'superadmin'; ?>
                    <tr data-staff-id="<?= (int)$__s['id'] ?>">
                        <td data-label="ID" class="text-muted"><?= (int)$__s['id'] ?></td>
                        <td data-label="Nhân viên">
                            <div class="d-flex align-items-center gap-2">
                                <span class="staff-avatar"><?= e(mb_strtoupper(mb_substr($__s['name'], 0, 1))) ?></span>
                                <div>
                                    <div class="fw-semibold"><?= e($__s['name']) ?> <?php if ($isOwner): ?><span class="badge text-bg-danger align-middle" style="font-size:.62em">Chủ hệ thống</span><?php endif; ?></div>
                                    <div class="small text-muted"><?= e($__s['email']) ?><?= $__s['phone'] ? ' · ' . e($__s['phone']) : '' ?></div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Chức vụ">
                            <?php if ($__s['role_name']): ?>
                                <span class="role-chip"><?= e($__s['role_name']) ?></span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Trạng thái" class="text-center">
                            <?php if (isset($__s['is_deleted']) && (int)$__s['is_deleted'] === 1): ?>
                                <span class="badge text-bg-dark rounded-pill px-3 py-2" data-status="deleted"><?= icon('bi-archive', 'me-1') ?>Đã xóa</span>
                            <?php elseif ((int)$__s['status'] === 1): ?>
                                <span class="badge text-bg-success rounded-pill px-3 py-2" data-status="active"><?= icon('bi-shield-check', 'me-1') ?>Hoạt động</span>
                            <?php else: ?>
                                <span class="badge text-bg-danger rounded-pill px-3 py-2" data-status="locked"><?= icon('bi-lock-fill', 'me-1') ?>Đã khóa</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Thao tác" class="text-center text-muted small"><?= (int)$__s['log_count'] ?> lượt</td>
                        <td data-label="Hành động" class="text-end text-nowrap">
                            <?php if (!$isOwner && $canEdit): ?>
                                <?php if ((int)$__s['status'] === 1 && (int)$__s['is_deleted'] !== 1): ?>
                                    <button class="btn btn-sm btn-outline-warning action-btn" data-action="block" data-id="<?= (int)$__s['id'] ?>" data-name="<?= e($__s['name']) ?>" title="Khóa tài khoản"><?= icon('bi-lock-fill') ?></button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-success action-btn" data-action="unblock" data-id="<?= (int)$__s['id'] ?>" data-name="<?= e($__s['name']) ?>" title="Mở khóa tài khoản"><?= icon('bi-unlock-fill') ?></button>
                                <?php endif; ?>
                                <?php if ($isSuper): ?>
                                    <button class="btn btn-sm btn-outline-danger action-btn" data-action="delete" data-id="<?= (int)$__s['id'] ?>" data-name="<?= e($__s['name']) ?>" title="Xóa tài khoản (soft)"><?= icon('bi-trash') ?></button>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php $pagerBase = BASE_URL . '/quan-tri/nhan-su' . (!empty($f['q']) ? '?q=' . urlencode($f['q']) : '') . (!empty($f['include_deleted']) ? (empty($f['q']) ? '?' : '&') . 'include_deleted=1' : ''); include BASE_PATH . '/includes/partials/pagination.php'; ?>
</div>

<?php if ($canAdd): ?>
<div class="modal fade" id="createStaffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="staffCreateForm">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><?= icon('bi-person-plus', 'me-1') ?>Thêm nhân viên</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <div class="mb-3">
                        <label class="form-label small text-muted">Họ tên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required placeholder="VD: Nguyễn Văn A">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Email đăng nhập <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required placeholder="nhanvien@woodcon.vn">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Số điện thoại</label>
                        <input type="text" class="form-control" name="phone" placeholder="09xx xxx xxx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Mật khẩu <span class="text-danger">*</span> (ít nhất 6 ký tự)</label>
                        <input type="password" class="form-control" name="password" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Chức vụ</label>
                        <select class="form-select" name="role_id">
                            <option value="">— Chưa gán —</option>
                            <?php foreach ($roles as $__r): if (($__r['code'] ?? '') === 'superadmin') continue; ?>
                                <option value="<?= (int)$__r['id'] ?>"><?= e($__r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary"><?= icon('bi-check-lg', 'me-1') ?>Tạo tài khoản</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.filter-bar .form-check-input{ margin-top:0; }
.staff-avatar{ width:38px;height:38px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;color:#fff;
  background:linear-gradient(135deg,var(--wc-primary, #8b5a2b),var(--wc-secondary,#d4a26a)); flex-shrink:0; }
.role-chip{ display:inline-flex;align-items:center;gap:.35rem;background:color-mix(in srgb,var(--wc-primary,#8b5a2b) 10%, transparent);color:var(--wc-primary,#8b5a2b);
  padding:.3rem .65rem;border-radius:.6rem;font-size:.8rem;font-weight:600; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  <?php if ($canAdd): ?>
  document.getElementById('staffCreateForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = this.querySelector('button[type=submit]'); btn.disabled = true;
    const fd = new FormData(this);
    fetch(WOODCON_BASE_URL + '/quan-tri/nhan-su/create', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json())
      .then(res => {
        if (res.ok) { wcToast(res.message, 'success'); setTimeout(() => location.reload(), 600); }
        else { wcToast(res.message || 'Có lỗi xảy ra', 'danger'); btn.disabled = false; }
      })
      .catch(() => { wcToast('Lỗi kết nối, vui lòng thử lại.', 'danger'); btn.disabled = false; });
  });
  <?php endif; ?>

  const LABEL = { block: 'khóa tài khoản', unblock: 'mở khóa tài khoản', delete: 'xóa (soft) tài khoản' };
  const VERB  = { block: 'khóa', unblock: 'mở khóa', delete: 'xóa' };

  document.querySelectorAll('.action-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const act = this.dataset.action, id = this.dataset.id, name = this.dataset.name;
      const extra = act === 'delete' ? '\n\nHành động vùng cấm — chỉ Chủ hệ thống. Lịch sử thao tác vẫn được giữ.' : '';
      wcConfirm('Bạn có chắc muốn ' + LABEL[act] + ' "' + name + '"?' + extra, function () {
        const fd = new FormData(); fd.append('id', id);
        fetch(WOODCON_BASE_URL + '/quan-tri/nhan-su/' + act, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(r => r.json())
          .then(res => {
            if (!res.ok) { wcToast(res.message || 'Có lỗi xảy ra', 'danger'); return; }
            wcToast(res.message, 'success');
            let row = document.querySelector('tr[data-staff-id="' + id + '"]');
            if (row) updateRow(row, act);
          })
          .catch(() => wcToast('Lỗi kết nối, vui lòng thử lại.', 'danger'));
      });
    });
  });

  function updateRow(row, act) {
    const badgeEl = row.querySelector('[data-status]');
    const isDeleted = act === 'delete';
    let html, cls;
    if (isDeleted) { html = '<?= icon('bi-archive', 'me-1') ?>Đã xóa'; cls = 'bg-dark'; }
    else if (act === 'block') { html = '<?= icon('bi-lock-fill', 'me-1') ?>Đã khóa'; cls = 'bg-danger'; }
    else { html = '<?= icon('bi-shield-check', 'me-1') ?>Hoạt động'; cls = 'bg-success'; }
    badgeEl.className = 'badge rounded-pill px-3 py-2 text-bg-' + cls;
    badgeEl.dataset.status = isDeleted ? 'deleted' : (act === 'block' ? 'locked' : 'active');
    badgeEl.innerHTML = html;
    // đổi nút hành động tương ứng
    const cell = row.querySelector('td:last-child');
    cell.querySelectorAll('.action-btn').forEach(b => {
      if (isDeleted) { b.style.display = 'none'; return; }
      const next = act === 'block' ? 'unblock' : 'block';
      if (b.dataset.action === act) { b.dataset.action = next; b.className = next === 'unblock' ? 'btn btn-sm btn-outline-success action-btn' : 'btn btn-sm btn-outline-warning action-btn'; b.title = next === 'unblock' ? 'Mở khóa tài khoản' : 'Khóa tài khoản'; var uu = b.querySelector('svg use'); if (uu) uu.setAttribute('href', WOODCON_BASE_URL + '/assets/icons/sprite.svg#' + (next === 'unblock' ? 'bi-unlock-fill' : 'bi-lock-fill')); }
    });
  }
});
</script>
