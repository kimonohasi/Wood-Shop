<?php
/** Kiểm duyệt đánh giá sản phẩm */
declare(strict_types=1);
$__tabs = [
    'pending'  => ['label' => 'Chờ duyệt', 'icon' => 'bi-hourglass-split'],
    'approved' => ['label' => 'Đã duyệt',  'icon' => 'bi-check2-circle'],
    'hidden'   => ['label' => 'Đã ẩn',     'icon' => 'bi-eye-slash'],
];
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0">Đánh giá sản phẩm</h1>
    </div>
</div>

<ul class="nav app-tabs mb-3">
    <?php $__fq = http_build_query(array_filter(['q' => $f['q'] ?? '', 'rating' => $f['rating'] ?? 0])); ?>
    <?php foreach ($__tabs as $__k => $__t): ?>
        <li class="nav-item">
            <a class="nav-link <?= $current === $__k ? 'active' : '' ?>" href="<?= BASE_URL ?>/quan-tri/danh-gia/<?= $__k ?><?= $__fq ? '?' . e($__fq) : '' ?>">
                <?= icon($__t['icon'], 'me-1') ?><?= $__t['label'] ?>
                <span class="badge text-bg-secondary ms-1"><?= (int)($counts[$__k] ?? 0) ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<div class="admin-card mb-3">
    <div class="card-body">
        <form class="row g-2" method="get">
            <div class="col-md-5">
                <input type="text" class="form-control form-control-sm" name="q" placeholder="Tìm theo khách hàng, email, sản phẩm" value="<?= e($f['q'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <select class="form-select form-select-sm" name="rating">
                    <option value="0">Tất cả số sao</option>
                    <?php for ($__s = 5; $__s >= 1; $__s--): ?>
                        <option value="<?= $__s ?>" <?= (int)($f['rating'] ?? 0) === $__s ? 'selected' : '' ?>><?= $__s ?> sao</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3 d-grid"><button class="btn btn-outline-primary btn-sm">Lọc</button></div>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th>Khách hàng</th>
                    <th>Sản phẩm</th>
                    <th>Đánh giá</th>
                    <th class="text-end" style="width:120px">Sao</th>
                    <th>Ngày</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6"><div class="empty-state"><?= icon('bi-star') ?>Không có đánh giá nào trong mục này</div></td></tr>
                <?php else: foreach ($rows as $__r): ?>
                    <tr>
                        <td data-label="Khách hàng">
                            <div class="fw-semibold small"><?= e($__r['user_name']) ?></div>
                            <div class="text-muted small"><?= e($__r['email']) ?></div>
                        </td>
                        <td data-label="Sản phẩm" class="small">
                            <a href="<?= BASE_URL ?>/san-pham/<?= e($__r['slug']) ?>" target="_blank" class="text-decoration-none"><?= e($__r['product_name']) ?></a>
                        </td>
                        <td data-label="Đánh giá" class="small" style="max-width:320px">
                            <?php if ($__r['title']): ?><div class="fw-semibold"><?= e($__r['title']) ?></div><?php endif; ?>
                            <div class="text-muted"><?= nl2br(e($__r['content'])) ?></div>
                        </td>
                        <td data-label="Sao" class="text-end text-nowrap" style="color:var(--wc-warning)">
                            <?= str_repeat(icon('bi-star-fill'), (int)$__r['rating']) ?>
                            <?= str_repeat(icon('bi-star'), 5 - (int)$__r['rating']) ?>
                        </td>
                        <td data-label="Ngày" class="text-muted small"><?= format_date($__r['created_at']) ?></td>
                        <td data-label="Thao tác" class="text-end text-nowrap">
                            <?php if ($__r['status'] === 'pending'): ?>
                                <?php foreach (['approved', 'hidden'] as $__to): ?>
                                    <form method="post" action="<?= BASE_URL ?>/quan-tri/danh-gia/<?= $__to ?>/<?= (int)$__r['id'] ?>" class="d-inline">
                                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                        <button class="btn btn-sm <?= $__to === 'approved' ? 'btn-success' : 'btn-outline-danger' ?>"><?= $__to === 'approved' ? 'Duyệt' : 'Ẩn' ?></button>
                                    </form>
                                <?php endforeach; ?>
                            <?php elseif ($__r['status'] === 'hidden'): ?>
                                <form method="post" action="<?= BASE_URL ?>/quan-tri/danh-gia/approved/<?= (int)$__r['id'] ?>" class="d-inline">
                                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                    <button class="btn btn-sm btn-outline-secondary">Khôi phục</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?= BASE_URL ?>/quan-tri/danh-gia/hidden/<?= (int)$__r['id'] ?>" class="d-inline">
                                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                    <button class="btn btn-sm btn-outline-danger" data-confirm="Ẩn đánh giá này khỏi trang sản phẩm?">Ẩn</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/includes/partials/pagination.php'; ?>