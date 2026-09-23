<?php
/** Quản lý banner admin */
declare(strict_types=1);

use WoodCon\Admin;

/** @var array $rows  Banner trong trang hiện tại (đã phân trang) */
$__all = Admin::db()->query("SELECT * FROM banners ORDER BY position='slider' DESC, sort_order ASC, id ASC")->fetchAll();

/** Tách nhóm theo position */
$__mainBanners = [];   // position = slider  -> Banner chính (trượt)
$__subBanners  = [];   // còn lại            -> Banner phụ
foreach ($__all as $__b) {
    if (($__b['position'] ?? '') === 'slider') {
        $__mainBanners[] = $__b;
    } else {
        $__subBanners[] = $__b;
    }
}

$__isEdit = !empty($edit);
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0">Banner</h1>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bannerModal" data-mode="create">
        <?= icon('bi-plus-lg', 'me-1') ?>Thêm banner
    </button>
</div>

<?php if ($__isEdit): ?>
    <div class="alert alert-info py-2 mb-3 small">
        Đang sửa banner <strong><?= e($edit['title'] ?: '#' . (int)$edit['id']) ?></strong> ·
        <a href="<?= BASE_URL ?>/quan-tri/banner">Hủy</a>
    </div>
<?php endif; ?>

<?php
/**
 * Render 1 dòng banner (compact) cho accordion body.
 */
$renderRow = static function (array $__b, string $__sectionId) {
    $__id = (int)$__b['id'];
    $__interval = (int)($__b['interval_ms'] ?? 0);
    ?>
    <tr>
        <td><img src="<?= e(image_url($__b['image'] ?? '')) ?>" class="rounded" width="80" height="45" style="object-fit:cover" alt=""></td>
        <td>
            <div class="fw-semibold small"><?= e($__b['title'] ?: '—') ?></div>
            <?php if ($__b['subtitle']): ?><div class="text-muted small"><?= e($__b['subtitle']) ?></div><?php endif; ?>
        </td>
        <td><span class="badge text-bg-light"><?= e($__b['position']) ?></span></td>
        <td class="text-center"><?= (int)$__b['sort_order'] ?></td>
        <td class="text-center">
            <?php if ($__b['position'] === 'slider' && $__interval > 0): ?>
                <span class="badge text-bg-info" title="Tốc độ trượt"><?= icon('bi-stopwatch') ?> <?= number_format($__interval / 1000, 1) ?>s</span>
            <?php endif; ?>
        </td>
        <td class="text-center"><span class="badge <?= $__b['status'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $__b['status'] ? 'Hiện' : 'Ẩn' ?></span></td>
        <td class="text-end">
            <a href="<?= BASE_URL ?>/quan-tri/banner/sua/<?= $__id ?>" class="btn btn-sm btn-light" title="Sửa"><?= icon('bi-pencil') ?></a>
            <a href="<?= BASE_URL ?>/quan-tri/banner/xoa/<?= $__id ?>" class="btn btn-sm btn-outline-danger" data-confirm="Xóa banner này?"><?= icon('bi-trash') ?></a>
        </td>
    </tr>
    <?php
};
?>

<!-- ============ ACCORDION: BANNER CHÍNH (TRƯỢT) + BANNER PHỤ ============ -->
<div class="accordion" id="bannerAccordion">

    <!-- ===== Banner chính (trượt) ===== -->
    <div class="accordion-item admin-card border-0 mb-2">
        <h2 class="accordion-header" id="head-banner-main">
            <button class="accordion-button collapsed px-3 py-3" type="button"
                    data-bs-toggle="collapse" data-bs-target="#collapseMain"
                    aria-expanded="false" aria-controls="collapseMain">
                <span class="fw-semibold"><?= icon('bi-collection-play', 'me-2') ?>Banner chính (trượt)</span>
                <span class="badge text-bg-primary ms-2"><?= count($__mainBanners) ?></span>
            </button>
        </h2>
        <div id="collapseMain" class="accordion-collapse collapse" data-bs-parent="#bannerAccordion" aria-labelledby="head-banner-main">
            <div class="accordion-body px-3 py-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-2 mb-2 border-bottom">
                    <span class="fw-semibold small">Danh sách banner trượt</span>
                    <button type="button" class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="modal" data-bs-target="#bannerModal" data-mode="create" data-default-position="slider">
                        <?= icon('bi-plus-lg', 'me-1') ?>Thêm vào nhóm này
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table admin-table mb-0">
                        <thead><tr>
                            <th style="width:90px">Ảnh</th><th>Tiêu đề</th><th>Vị trí</th>
                            <th class="text-center" style="width:80px">Thứ tự</th>
                            <th class="text-center" style="width:90px">Tốc độ</th>
                            <th class="text-center" style="width:80px">TT</th>
                            <th class="text-end" style="width:120px">Thao tác</th>
                        </tr></thead>
                        <tbody>
                            <?php if (empty($__mainBanners)): ?>
                                <tr><td colspan="7"><div class="empty-state"><?= icon('bi-collection-play') ?>Chưa có banner chính</div></td></tr>
                            <?php else: foreach ($__mainBanners as $__b): ?>
                                <tr>
                                    <td><img src="<?= e(image_url($__b['image'] ?? '')) ?>" class="rounded" width="80" height="45" style="object-fit:cover" alt=""></td>
                                    <td>
                                        <div class="fw-semibold small"><?= e($__b['title'] ?: '—') ?></div>
                                        <?php if ($__b['subtitle']): ?><div class="text-muted small"><?= e($__b['subtitle']) ?></div><?php endif; ?>
                                    </td>
                                    <td><span class="badge text-bg-light"><?= e($__b['position']) ?></span></td>
                                    <td class="text-center"><?= (int)$__b['sort_order'] ?></td>
                                    <td class="text-center">
                                        <?php $__iv = (int)($__b['interval_ms'] ?? 0); ?>
                                        <?php if ($__iv > 0): ?>
                                            <span class="badge text-bg-info"><?= icon('bi-stopwatch') ?> <?= number_format($__iv / 1000, 1) ?>s</span>
                                        <?php else: ?>
                                            <span class="text-muted small">Mặc định 6s</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><span class="badge <?= $__b['status'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $__b['status'] ? 'Hiện' : 'Ẩn' ?></span></td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>/quan-tri/banner/sua/<?= (int)$__b['id'] ?>" class="btn btn-sm btn-light" title="Sửa"><?= icon('bi-pencil') ?></a>
                                        <a href="<?= BASE_URL ?>/quan-tri/banner/xoa/<?= (int)$__b['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Xóa banner này?"><?= icon('bi-trash') ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Banner phụ ===== -->
    <div class="accordion-item admin-card border-0 mb-2">
        <h2 class="accordion-header" id="head-banner-sub">
            <button class="accordion-button collapsed px-3 py-3" type="button"
                    data-bs-toggle="collapse" data-bs-target="#collapseSub"
                    aria-expanded="false" aria-controls="collapseSub">
                <span class="fw-semibold"><?= icon('bi-images', 'me-2') ?>Banner phụ</span>
                <span class="badge text-bg-secondary ms-2"><?= count($__subBanners) ?></span>
            </button>
        </h2>
        <div id="collapseSub" class="accordion-collapse collapse" data-bs-parent="#bannerAccordion" aria-labelledby="head-banner-sub">
            <div class="accordion-body px-3 py-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-2 mb-2 border-bottom">
                    <span class="fw-semibold small">Danh sách banner phụ</span>
                    <button type="button" class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="modal" data-bs-target="#bannerModal" data-mode="create" data-default-position="banner">
                        <?= icon('bi-plus-lg', 'me-1') ?>Thêm vào nhóm này
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table admin-table mb-0">
                        <thead><tr>
                            <th style="width:90px">Ảnh</th><th>Tiêu đề</th><th>Vị trí</th>
                            <th class="text-center" style="width:80px">Thứ tự</th>
                            <th class="text-center" style="width:80px">TT</th>
                            <th class="text-end" style="width:120px">Thao tác</th>
                        </tr></thead>
                        <tbody>
                            <?php if (empty($__subBanners)): ?>
                                <tr><td colspan="6"><div class="empty-state"><?= icon('bi-images') ?>Chưa có banner phụ</div></td></tr>
                            <?php else: foreach ($__subBanners as $__b): ?>
                                <tr>
                                    <td><img src="<?= e(image_url($__b['image'] ?? '')) ?>" class="rounded" width="80" height="45" style="object-fit:cover" alt=""></td>
                                    <td>
                                        <div class="fw-semibold small"><?= e($__b['title'] ?: '—') ?></div>
                                        <?php if ($__b['subtitle']): ?><div class="text-muted small"><?= e($__b['subtitle']) ?></div><?php endif; ?>
                                    </td>
                                    <td><span class="badge text-bg-light"><?= e($__b['position']) ?></span></td>
                                    <td class="text-center"><?= (int)$__b['sort_order'] ?></td>
                                    <td class="text-center"><span class="badge <?= $__b['status'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $__b['status'] ? 'Hiện' : 'Ẩn' ?></span></td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>/quan-tri/banner/sua/<?= (int)$__b['id'] ?>" class="btn btn-sm btn-light" title="Sửa"><?= icon('bi-pencil') ?></a>
                                        <a href="<?= BASE_URL ?>/quan-tri/banner/xoa/<?= (int)$__b['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Xóa banner này?"><?= icon('bi-trash') ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============ MODAL THÊM / SỬA BANNER ============ -->
<div class="modal fade" id="bannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>/quan-tri/banner/luu">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" id="bnf_id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="bannerModalTitle"><?= icon('bi-images', 'me-1') ?>Thêm banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2 text-center" id="bnf_preview_wrap" style="display:none">
                        <img id="bnf_preview" src="" class="img-fluid rounded" style="max-height:110px;object-fit:cover" alt="Ảnh hiện tại">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Ảnh banner (chọn ảnh mới để thay thế)</label>
                        <input type="file" class="form-control form-control-sm" name="image" id="bnf_image" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Tiêu đề</label>
                        <input type="text" class="form-control" name="title" id="bnf_title">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Phụ đề</label>
                        <input type="text" class="form-control" name="subtitle" id="bnf_subtitle">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Đường dẫn (link)</label>
                        <input type="text" class="form-control" name="link" id="bnf_link">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small text-muted">Vị trí</label>
                            <select class="form-select form-select-sm" name="position" id="bnf_position">
                                <option value="slider">slider — Banner chính (trượt)</option>
                                <option value="banner">banner — Banner trang trí</option>
                                <option value="flash_sale">flash_sale — Banner khuyến mãi</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted">Thứ tự</label>
                            <input type="number" class="form-control form-control-sm" name="sort_order" id="bnf_sort_order" value="0">
                        </div>
                    </div>

                    <!-- Chỉ hiện với position = slider -->
                    <div class="mb-3" id="bnf_interval_wrap" style="display:none">
                        <label class="form-label small text-muted">
                            <?= icon('bi-stopwatch', 'me-1') ?>Tốc độ trượt (ms)
                            <span class="text-muted ms-1">— chỉ áp dụng cho slider</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control" name="interval_ms" id="bnf_interval_ms"
                                   min="1000" max="60000" step="500" placeholder="Mặc định 6000">
                            <span class="input-group-text">ms</span>
                        </div>
                        <div class="form-text">
                            Khoảng thời gian chuyển slide tự động. Để trống = dùng mặc định 6000ms (6 giây).
                        </div>
                    </div>

                    <div class="form-check form-switch toggle-row">
                        <input class="form-check-input" type="checkbox" name="status" id="bnf_status" checked>
                        <label class="form-check-label small" for="bnf_status">Hoạt động</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button class="btn btn-primary"><?= icon('bi-check2', 'me-1') ?>Lưu banner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('bannerModal');
    const title = document.getElementById('bannerModalTitle');
    const intervalWrap = document.getElementById('bnf_interval_wrap');
    const intervalInput = document.getElementById('bnf_interval_ms');
    const positionSel = document.getElementById('bnf_position');
    const setVal = (id, v) => { const el = document.getElementById('bnf_' + id); if (el) el.value = v ?? ''; };
    const setStatus = v => { const c = document.getElementById('bnf_status'); if (c) c.checked = !!Number(v); };
    const previewWrap = document.getElementById('bnf_preview_wrap');
    const preview = document.getElementById('bnf_preview');

    function showPreview(src, has) {
        previewWrap.style.display = has ? 'block' : 'none';
        if (has) preview.src = src;
    }

    function toggleInterval() {
        const show = positionSel.value === 'slider';
        intervalWrap.style.display = show ? '' : 'none';
        if (!show) intervalInput.value = '';
    }
    positionSel.addEventListener('change', toggleInterval);

    modal.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        if (btn && btn.dataset.mode === 'edit') return;
        title.innerHTML = '<?= icon('bi-images', 'me-1') ?>Thêm banner';
        setVal('id', '0');
        setVal('title', '');
        setVal('subtitle', '');
        setVal('link', '');
        setVal('position', btn && btn.dataset.defaultPosition ? btn.dataset.defaultPosition : 'slider');
        setVal('sort_order', '0');
        setVal('image', '');
        setVal('interval_ms', '');
        setStatus(1);
        showPreview('', false);
        toggleInterval();
    });

    document.querySelectorAll('a[href*="/quan-tri/banner/sua/"]').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('href').split('/').pop();
            // Lấy dữ liệu từ controller đã truyền vào $edit
            const e = <?= json_encode($edit, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            if (!e || String(e.id) !== String(id)) return;
            title.innerHTML = '<?= icon('bi-pencil', 'me-1') ?>Sửa banner';
            setVal('id', e.id);
            setVal('title', e.title);
            setVal('subtitle', e.subtitle);
            setVal('link', e.link);
            setVal('position', e.position);
            setVal('sort_order', e.sort_order);
            setVal('interval_ms', e.interval_ms || '');
            setStatus(e.status);
            showPreview(e.image ? (e.image.startsWith('http') ? e.image : (window.WOODCON_BASE_URL || '') + '/uploads/' + e.image) : '', !!e.image);
            toggleInterval();
        });
    });
});
</script>

<?php require BASE_PATH . '/includes/partials/pagination.php'; ?>