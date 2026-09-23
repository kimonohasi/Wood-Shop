<?php
/** Giám sát hệ thống (audit logs) — chỉ đọc, không xóa/sửa */
declare(strict_types=1);
use WoodCon\AuditLogger;
$__badge = function (string $a): string {
    return match ($a) {
        'CREATE'      => 'badge bg-success',
        'UPDATE'      => 'badge bg-primary',
        'DELETE'      => 'badge bg-danger',
        'HARD_DELETE' => 'badge bg-dark',
        'EXPORT'      => 'badge bg-info',
        'LOGIN'       => 'badge bg-secondary',
        'LOCK'        => 'badge bg-warning text-dark',
        'UNLOCK'      => 'badge bg-success',
        default       => 'badge bg-secondary',
    };
};
$__row = function (array $l) use ($__badge): string {
    $time = format_date($l['created_at'] ?? '', 'd/m/Y H:i:s');
    return '<tr>'
        . '<td data-label="ID">' . (int)$l['id'] . '</td>'
        . '<td data-label="Người thực hiện"><div class="fw-semibold">' . e($l['user_name'] ?? '') . '</div><div class="small text-muted">ID ' . (int)($l['user_id'] ?? 0) . '</div></td>'
        . '<td data-label="Chức vụ">' . e($l['role_name'] ?? '') . '</td>'
        . '<td data-label="Hành động"><span class="' . $__badge($l['action'] ?? '') . '">' . e(AuditLogger::actionLabel($l['action'] ?? '')) . '</span></td>'
        . '<td data-label="Module">' . e(AuditLogger::moduleLabel($l['module'] ?? '')) . '</td>'
        . '<td data-label="Mô tả" class="text-truncate" style="max-width:340px">' . e($l['description'] ?? '') . '</td>'
        . '<td data-label="IP" class="text-muted small">' . e($l['ip_address'] ?? '-') . '</td>'
        . '<td data-label="Thời gian" class="text-nowrap">' . $time . '</td>'
        . '</tr>';
};
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0">Giám sát hệ thống</h1>
    </div>
</div>

<div class="admin-card mt-3">
    <div class="card-body">
        <form id="auditFilter" class="row g-2">
        <input type="hidden" name="route" value="quan-tri/nhat-ky">
            <div class="col-12 col-md-3">
                <input type="text" class="form-control form-control-sm" name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="Tìm tên / mô tả / ID">
            </div>
            <div class="col-6 col-md-2">
                <select class="form-select form-select-sm" name="action">
                    <option value="">— Hành động —</option>
                    <?php foreach ($actions as $__a): ?>
                        <option value="<?= e($__a) ?>" <?= ($f['action'] ?? '') === $__a ? 'selected' : '' ?>><?= e(AuditLogger::actionLabel($__a)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select class="form-select form-select-sm" name="module">
                    <option value="">— Module —</option>
                    <?php foreach ($modules as $__m): ?>
                        <option value="<?= e($__m) ?>" <?= ($f['module'] ?? '') === $__m ? 'selected' : '' ?>><?= e(AuditLogger::moduleLabel($__m)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <input type="date" class="form-control form-control-sm" name="from" value="<?= e($f['from'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-2">
                <input type="date" class="form-control form-control-sm" name="to" value="<?= e($f['to'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-1">
                <button class="btn btn-primary btn-sm w-100"><?= icon('bi-funnel') ?></button>
            </div>
        </form>
    </div>
</div>

<div class="admin-card mt-3">
    <div class="card-head"><span>Tổng: <strong id="auditTotal"><?= number_format($total) ?></strong> bản ghi</span></div>
    <div class="card-body p-0 table-responsive">
        <table class="table admin-table mb-0">
            <thead><tr><th>ID</th><th>Người thực hiện</th><th>Chức vụ</th><th>Hành động</th><th>Module</th><th style="width:40%">Mô tả</th><th>IP</th><th>Thời gian</th></tr></thead>
            <tbody id="auditBody">
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8"><div class="empty-state"><?= icon('bi-journal-x') ?>Không có nhật ký phù hợp</div></td></tr>
                <?php else: foreach ($rows as $__l): echo $__row($__l); endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div id="auditPager" class="p-2"><?php $pagerBase = BASE_URL . '/quan-tri/nhat-ky'; include BASE_PATH . '/includes/partials/pagination.php'; ?></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  let t = null;
  document.getElementById('auditFilter').addEventListener('submit', function (e) {
    e.preventDefault();
    clearTimeout(t);
    t = setTimeout(load, 350);
  });
  function load() {
    const params = new URLSearchParams(new FormData(document.getElementById('auditFilter')));
    params.set('route', 'quan-tri/nhat-ky');
    fetch(<?= json_encode(BASE_URL) ?> + '/quan-tri/nhat-ky?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json())
      .then(res => {
        document.getElementById('auditBody').innerHTML = res.rows || '<tr><td colspan="8"><div class="empty-state"><?= icon('bi-journal-x') ?>Không có nhật ký phù hợp</div></td></tr>';
        document.getElementById('auditTotal').textContent = Number(res.total || 0).toLocaleString('vi-VN');
        document.getElementById('auditPager').innerHTML = res.pager || '';
      })
      .catch(() => {});
  }
});
</script>
