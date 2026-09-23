<?php
/** Quản lý tin tức admin */
declare(strict_types=1);
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0">Tin tức</h1>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newsModal" data-mode="create"><?= icon('bi-plus-lg', 'me-1') ?>Thêm bài viết</button>
</div>

<div class="admin-card mb-3">
    <div class="card-body">
        <form class="row g-2" method="get">
            <div class="col-md-6">
                <input type="text" class="form-control form-control-sm" name="q" placeholder="Tìm theo tiêu đề" value="<?= e($f['q'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="1" <?= ($f['status'] ?? '') === '1' ? 'selected' : '' ?>>Hiện</option>
                    <option value="0" <?= ($f['status'] ?? '') === '0' ? 'selected' : '' ?>>Ẩn</option>
                </select>
            </div>
            <div class="col-md-3 d-grid"><button class="btn btn-outline-primary btn-sm">Lọc</button></div>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="card-head">Danh sách bài viết</div>
    <div class="card-body p-0">
        <table class="table admin-table mb-0">
            <thead><tr><th style="width:64px">Ảnh</th><th>Tiêu đề</th><th class="text-center">Ngày</th><th class="text-center">TT</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="5"><div class="empty-state"><?= icon('bi-newspaper') ?>Chưa có bài viết</div></td></tr>
                <?php else: foreach ($rows as $__n): ?>
                    <tr>
                        <td><img src="<?= e(image_url($__n['image'] ?? '')) ?>" class="rounded" width="60" height="45" style="object-fit:cover" alt=""></td>
                        <td data-label="Tiêu đề"><div class="fw-semibold small"><?= e($__n['title']) ?></div>
                            <div class="text-muted small"><?= e($__n['slug']) ?></div></td>
                        <td data-label="Ngày" class="text-center text-muted small"><?= format_date($__n['created_at'] ?? null, 'd/m/Y') ?></td>
                        <td data-label="TT" class="text-center"><span class="badge <?= $__n['status'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $__n['status'] ? 'Hiện' : 'Ẩn' ?></span></td>
                        <td data-label="Thao tác" class="text-end">
                            <a href="<?= BASE_URL ?>/quan-tri/tin-tuc/sua/<?= (int)$__n['id'] ?>" class="btn btn-sm btn-light btn-edit-news"
                               data-bs-toggle="modal" data-bs-target="#newsModal" data-mode="edit"
                               data-id="<?= (int)$__n['id'] ?>" data-title="<?= e($__n['title']) ?>" data-author="<?= e($__n['author'] ?? '') ?>"
                               data-summary="<?= e($__n['summary'] ?? '') ?>" data-content="<?= e($__n['content'] ?? '') ?>"
                               data-status="<?= (int)$__n['status'] ?>" data-image="<?= e(image_url($__n['image'] ?? '')) ?>" title="Sửa"><?= icon('bi-pencil') ?></a>
                            <a href="<?= BASE_URL ?>/quan-tri/tin-tuc/xoa/<?= (int)$__n['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Xóa bài viết này?"><?= icon('bi-trash') ?></a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============ MODAL THÊM / SỬA BÀI VIẾT ============ -->
<div class="modal fade" id="newsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>/quan-tri/tin-tuc/luu">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" id="nwf_id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="newsModalTitle"><?= icon('bi-newspaper', 'me-1') ?>Thêm bài viết</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2 text-center" id="nwf_preview_wrap" style="display:none">
                        <img id="nwf_preview" src="" class="img-fluid rounded" style="max-height:110px;object-fit:cover" alt="Ảnh hiện tại">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Ảnh đại diện (chọn ảnh mới để thay thế)</label>
                        <input type="file" class="form-control form-control-sm" name="image" id="nwf_image" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Tiêu đề *</label>
                        <input type="text" class="form-control" name="title" id="nwf_title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Tác giả</label>
                        <input type="text" class="form-control" name="author" id="nwf_author">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Tóm tắt</label>
                        <textarea class="form-control" name="summary" id="nwf_summary" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Nội dung</label>
                        <textarea class="form-control" name="content" id="nwf_content" rows="6"></textarea>
                    </div>
                    <div class="form-check form-switch toggle-row">
                        <input class="form-check-input" type="checkbox" name="status" id="nwf_status" checked>
                        <label class="form-check-label small" for="nwf_status">Hiển thị</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button class="btn btn-primary"><?= icon('bi-check2', 'me-1') ?>Lưu bài viết</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('newsModal');
    const title = document.getElementById('newsModalTitle');
    const setVal = (id, v) => { const el = document.getElementById('nwf_' + id); if (el) el.value = v ?? ''; };
    const setStatus = v => { const c = document.getElementById('nwf_status'); if (c) c.checked = !!Number(v); };
    const previewWrap = document.getElementById('nwf_preview_wrap');
    const preview = document.getElementById('nwf_preview');

    function showPreview(src, has) {
        previewWrap.style.display = has ? 'block' : 'none';
        if (has) preview.src = src;
    }

    modal.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        if (btn && btn.dataset.mode === 'edit') return;
        title.innerHTML = '<?= icon('bi-newspaper', 'me-1') ?>Thêm bài viết';
        setVal('id', '0');
        setVal('title', '');
        setVal('author', '');
        setVal('summary', '');
        setVal('content', '');
        setVal('image', '');
        setStatus(1);
        showPreview('', false);
    });

    document.querySelectorAll('.btn-edit-news').forEach(btn => {
        btn.addEventListener('click', function () {
            title.innerHTML = '<?= icon('bi-pencil', 'me-1') ?>Sửa bài viết';
            setVal('id', this.dataset.id);
            setVal('title', this.dataset.title);
            setVal('author', this.dataset.author);
            setVal('summary', this.dataset.summary);
            setVal('content', this.dataset.content);
            setVal('image', '');
            setStatus(this.dataset.status);
            showPreview(this.dataset.image, !!this.dataset.image);
        });
    });
});
</script>

<?php require BASE_PATH . '/includes/partials/pagination.php'; ?>
