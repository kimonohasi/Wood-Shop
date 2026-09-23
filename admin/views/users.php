<?php
/** Danh sách khách hàng admin */
declare(strict_types=1);
$totalUsers = (int)$total;
$activeCount = array_reduce($rows, fn($c, $u) => $c + ((int)$u['status'] === 1 ? 1 : 0), 0);
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0">Khách hàng</h1>
    </div>
    <div class="dropdown">
        <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"><?= icon('bi-download', 'me-1') ?>Xuất dữ liệu</button>
        <ul class="dropdown-menu dropdown-menu-end">
            <?php $__exportQ = http_build_query(array_filter(['q' => $f['q'] ?? '', 'status' => $f['status'] ?? ''])); ?>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/quan-tri/khach-hang/xuat?<?= e($__exportQ) ?>&export=csv"><?= icon('bi-filetype-csv', 'me-1') ?>CSV</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/quan-tri/khach-hang/xuat?<?= e($__exportQ) ?>&export=xlsx"><?= icon('bi-file-earmark-excel', 'me-1') ?>Excel (.xlsx)</a></li>
        </ul>
    </div>
</div>

<div class="row g-3 mt-1 mb-3">
    <div class="col-6 col-lg-4"><div class="admin-stat"><div class="stat-label">Tổng tài khoản</div><div class="h5 mb-0 mt-1"><?= number_format($totalUsers) ?></div></div></div>
    <div class="col-6 col-lg-4"><div class="admin-stat"><div class="stat-label">Đang hoạt động</div><div class="h5 mb-0 mt-1 text-success"><?= $activeCount ?></div></div></div>
    <div class="col-6 col-lg-4"><div class="admin-stat"><div class="stat-label">Đã khóa</div><div class="h5 mb-0 mt-1 text-danger"><?= max(0, $totalUsers - $activeCount) ?></div></div></div>
</div>

<div class="admin-card mb-3">
    <div class="card-body">
        <form class="row g-2" method="get">
            <div class="col-md-6">
                <input type="text" class="form-control form-control-sm" name="q" placeholder="Tìm theo tên, email, SĐT" value="<?= e($f['q'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="1" <?= ($f['status'] ?? '') === '1' ? 'selected' : '' ?>>Hoạt động</option>
                    <option value="0" <?= ($f['status'] ?? '') === '0' ? 'selected' : '' ?>>Đã khóa</option>
                </select>
            </div>
            <div class="col-md-3 d-grid"><button class="btn btn-outline-primary btn-sm">Lọc</button></div>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead><tr><th>Khách hàng</th><th>SĐT</th><th>Điểm</th><th class="text-center">Đơn</th><th>Tham gia</th><th>TT</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody id="userRows">
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7"><div class="empty-state"><?= icon('bi-people') ?>Không có khách hàng</div></td></tr>
                <?php else: foreach ($rows as $__u): ?>
                    <tr data-user-id="<?= (int)$__u['id'] ?>">
                        <td data-label="Khách hàng">
                            <div class="fw-semibold small"><?= e($__u['name']) ?></div>
                            <div class="text-muted small"><?= e($__u['email']) ?></div>
                        </td>
                        <td data-label="SĐT" class="small"><?= e($__u['phone']) ?></td>
                        <td data-label="Điểm" class="small"><?= (int)$__u['points'] ?></td>
                        <td data-label="Đơn" class="text-center"><?= (int)$__u['order_count'] ?? 0 ?></td>
                        <td data-label="Tham gia" class="text-muted small"><?= format_date($__u['created_at'], 'd/m/Y') ?></td>
                        <td data-label="TT" class="text-center" data-status-cell>
                            <span class="badge <?= $__u['status'] ? 'text-bg-success' : 'text-bg-secondary' ?>" data-status="<?= $__u['status'] ? 'active' : 'locked' ?>"><?= $__u['status'] ? 'Hoạt động' : 'Khóa' ?></span>
                        </td>
                        <td data-label="Thao tác" class="text-end text-nowrap">
                            <div class="d-flex align-items-center justify-content-end gap-1">
                            <button type="button" class="btn btn-sm <?= $__u['status'] ? 'btn-outline-warning' : 'btn-outline-success' ?> user-lock-btn" data-id="<?= (int)$__u['id'] ?>" data-name="<?= e($__u['name']) ?>" title="<?= $__u['status'] ? 'Khóa tài khoản' : 'Mở khóa tài khoản' ?>"><?= icon($__u['status'] ? 'bi-lock-fill' : 'bi-unlock-fill') ?></button>
                            <a href="<?= BASE_URL ?>/quan-tri/khach-hang/xem/<?= (int)$__u['id'] ?>" class="btn btn-sm btn-light"><?= icon('bi-eye') ?></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/includes/partials/pagination.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.user-lock-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const id = this.dataset.id, name = this.dataset.name;
      const currentlyLocked = this.querySelector('i').classList.contains('bi-lock-fill');
      wcConfirm('Bạn có chắc muốn ' + (currentlyLocked ? 'khóa' : 'mở khóa') + ' tài khoản "' + name + '"?', function () {
        const fd = new FormData(); fd.append('id', id);
        fetch(<?= json_encode(BASE_URL) ?> + '/quan-tri/khach-hang/khoa/' + id, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(r => r.json())
          .then(res => {
            if (!res.ok) { wcToast(res.message || 'Có lỗi xảy ra', 'danger'); return; }
            wcToast(res.message, 'success');
            updateRow(id, res.locked);
          })
          .catch(() => wcToast('Lỗi kết nối, vui lòng thử lại.', 'danger'));
      });
    });
  });

  function updateRow(id, locked) {
    const row = document.querySelector('tr[data-user-id="' + id + '"]');
    if (!row) return;
    const badge = row.querySelector('[data-status]');
    badge.className = 'badge ' + (locked ? 'text-bg-secondary' : 'text-bg-success');
    badge.dataset.status = locked ? 'locked' : 'active';
    badge.textContent = locked ? 'Khóa' : 'Hoạt động';
    const btn = row.querySelector('.user-lock-btn');
    btn.className = 'btn btn-sm ' + (locked ? 'btn-outline-success' : 'btn-outline-warning');
    btn.title = locked ? 'Mở khóa tài khoản' : 'Khóa tài khoản';
    const uu = btn.querySelector('svg use');
    if (uu) uu.setAttribute('href', WOODCON_BASE_URL + '/assets/icons/sprite.svg#' + (locked ? 'bi-unlock-fill' : 'bi-lock-fill'));
    row.querySelector('[data-status-cell]').innerHTML = badge.outerHTML;
  }
});
</script>