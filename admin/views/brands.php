<?php
/** Quản lý thương hiệu admin */
declare(strict_types=1);
$brandStats = $brandStats ?? ['total' => 0, 'active' => 0, 'inactive' => 0];
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0">Thương hiệu</h1>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#brandModal" data-mode="create"><?= icon('bi-plus-lg', 'me-1') ?>Thêm thương hiệu</button>
</div>

<!-- ===== STAT CARDS ===== -->
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-4">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Tổng thương hiệu</div>
                    <div class="stat-value mt-1"><?= (int)$brandStats['total'] ?></div>
                </div>
                <?= icon('bi-shop', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-4">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Hoạt động</div>
                    <div class="stat-value mt-1 text-success"><?= (int)$brandStats['active'] ?></div>
                </div>
                <?= icon('bi-check-circle', 'stat-icon', 'style="color:var(--wc-success)"') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-4">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Không hoạt động</div>
                    <div class="stat-value mt-1"><?= (int)$brandStats['inactive'] ?></div>
                </div>
                <?= icon('bi-eye-slash', 'stat-icon') ?>
            </div>
        </div>
    </div>
</div>

<div class="admin-card mb-3">
    <div class="card-body">
        <form class="row g-2" method="get">
            <div class="col-md-6">
                <input type="text" class="form-control form-control-sm" name="q" placeholder="Tìm theo tên thương hiệu" value="<?= e($f['q'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="1" <?= ($f['status'] ?? '') === '1' ? 'selected' : '' ?>>Hoạt động</option>
                    <option value="0" <?= ($f['status'] ?? '') === '0' ? 'selected' : '' ?>>Ẩn</option>
                </select>
            </div>
            <div class="col-md-3 d-grid"><button class="btn btn-outline-primary btn-sm">Lọc</button></div>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="card-body p-0">
        <table class="table admin-table mb-0">
            <thead><tr><th>Tên</th><th>Slug</th><th class="text-center">Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="4"><div class="empty-state"><?= icon('bi-shop') ?>Chưa có thương hiệu</div></td></tr>
                <?php else: foreach ($rows as $__b): ?>
                    <tr>
                        <td data-label="Tên" class="fw-semibold"><?= e($__b['name']) ?></td>
                        <td data-label="Slug" class="text-muted small"><?= e($__b['slug']) ?></td>
                        <td data-label="Trạng thái" class="text-center"><span class="badge <?= $__b['status'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $__b['status'] ? 'Hoạt động' : 'Ẩn' ?></span></td>
                        <td data-label="Thao tác" class="text-end">
                            <a href="<?= BASE_URL ?>/quan-tri/thuong-hieu/sua/<?= (int)$__b['id'] ?>" class="btn btn-sm btn-light btn-edit-brand"
                               data-bs-toggle="modal" data-bs-target="#brandModal" data-mode="edit"
                               data-id="<?= (int)$__b['id'] ?>" data-name="<?= e($__b['name']) ?>" data-status="<?= (int)$__b['status'] ?>" title="Sửa"><?= icon('bi-pencil') ?></a>
                            <a href="<?= BASE_URL ?>/quan-tri/thuong-hieu/xoa/<?= (int)$__b['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Xóa thương hiệu này?"><?= icon('bi-trash') ?></a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============ MODAL THÊM / SỬA THƯƠNG HIỆU ============ -->
<div class="modal fade" id="brandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="<?= BASE_URL ?>/quan-tri/thuong-hieu/luu">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" id="bf_id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="brandModalTitle"><?= icon('bi-shop', 'me-1') ?>Thêm thương hiệu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-muted">Tên thương hiệu *</label>
                        <input type="text" class="form-control" name="name" id="bf_name" required>
                    </div>
                    <div class="form-check form-switch toggle-row">
                        <input class="form-check-input" type="checkbox" name="status" id="bf_status" checked>
                        <label class="form-check-label small" for="bf_status">Hoạt động</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button class="btn btn-primary"><?= icon('bi-check2', 'me-1') ?>Lưu thương hiệu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('brandModal');
    const title = document.getElementById('brandModalTitle');
    const setVal = (id, v) => { const el = document.getElementById('bf_' + id); if (el) el.value = v ?? ''; };
    const setStatus = v => { const c = document.getElementById('bf_status'); if (c) c.checked = !!Number(v); };

    modal.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        if (btn && btn.dataset.mode === 'edit') return;
        title.innerHTML = '<?= icon('bi-shop', 'me-1') ?>Thêm thương hiệu';
        setVal('id', '0');
        setVal('name', '');
        setStatus(1);
    });

    document.querySelectorAll('.btn-edit-brand').forEach(btn => {
        btn.addEventListener('click', function () {
            title.innerHTML = '<?= icon('bi-pencil', 'me-1') ?>Sửa thương hiệu';
            setVal('id', this.dataset.id);
            setVal('name', this.dataset.name);
            setStatus(this.dataset.status);
        });
    });
});
</script>

<?php require BASE_PATH . '/includes/partials/pagination.php'; ?>
