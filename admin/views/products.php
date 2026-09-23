<?php
/** Danh sách sản phẩm admin */
declare(strict_types=1);

$stats ??= [
    'total' => 0, 'active' => 0, 'hidden' => 0, 'best_seller' => 0,
    'featured' => 0, 'is_new' => 0, 'in_stock' => 0, 'low_stock' => 0,
];
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title">Sản phẩm</h1>
    </div>
    <div class="d-flex align-items-stretch gap-2">
        <a href="<?= BASE_URL ?>/quan-tri/san-pham/tao" class="btn btn-primary btn-sm btn-block"><?= icon('bi-plus-lg', 'me-1') ?>Thêm sản phẩm</a>
        <div class="dropdown d-grid flex-grow-1">
            <button class="btn btn-success btn-sm dropdown-toggle w-100" type="button" data-bs-toggle="dropdown"><?= icon('bi-download', 'me-1') ?>Xuất dữ liệu</button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/quan-tri/san-pham/xuat?<?= e(http_build_query(array_filter(['q' => $q, 'cat' => $cat]))) ?>&export=csv"><?= icon('bi-filetype-csv', 'me-1') ?>CSV</a></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/quan-tri/san-pham/xuat?<?= e(http_build_query(array_filter(['q' => $q, 'cat' => $cat]))) ?>&export=xlsx"><?= icon('bi-file-earmark-excel', 'me-1') ?>Excel (.xlsx)</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- ===== STAT CARDS ===== -->
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Tổng sản phẩm</div>
                    <div class="stat-value mt-1"><?= (int)$stats['total'] ?></div>
                </div>
                <?= icon('bi-box-seam', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Đang hoạt động</div>
                    <div class="stat-value mt-1 text-success"><?= (int)$stats['active'] ?></div>
                </div>
                <?= icon('bi-check-circle', 'stat-icon', 'style="color:var(--wc-success)"') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Đang ẩn</div>
                    <div class="stat-value mt-1"><?= (int)$stats['hidden'] ?></div>
                </div>
                <?= icon('bi-eye-slash', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Sản phẩm bán chạy</div>
                    <div class="stat-value mt-1 text-warning"><?= (int)$stats['best_seller'] ?></div>
                </div>
                <?= icon('bi-fire', 'stat-icon', 'style="color:var(--wc-warning)"') ?>
            </div>
        </div>
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Sản phẩm nổi bật</div>
                    <div class="stat-value mt-1"><?= (int)$stats['featured'] ?></div>
                </div>
                <?= icon('bi-star-fill', 'stat-icon', 'style="color:var(--wc-warning)"') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Hàng mới</div>
                    <div class="stat-value mt-1"><?= (int)$stats['is_new'] ?></div>
                </div>
                <?= icon('bi-stars', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Tồn kho</div>
                    <div class="stat-value mt-1"><?= (int)$stats['in_stock'] ?></div>
                </div>
                <?= icon('bi-boxes', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Sắp hết hàng</div>
                    <div class="stat-value mt-1 <?= (int)$stats['low_stock'] ? 'text-danger' : '' ?>"><?= (int)$stats['low_stock'] ?></div>
                </div>
                <?= icon('bi-exclamation-triangle', 'stat-icon', 'style="color:var(--wc-danger)"') ?>
            </div>
        </div>
    </div>
</div>

<div class="admin-card mb-3">
    <div class="card-body">
        <form class="row g-2" method="get">
            <div class="col-md-5">
                <input type="text" class="form-control form-control-sm" name="q" placeholder="Tìm theo tên hoặc SKU" value="<?= e($q) ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="cat">
                    <option value="0">Tất cả danh mục</option>
                    <?php foreach ($categories as $__c): ?>
                        <option value="<?= (int)$__c['id'] ?>" <?= $cat === (int)$__c['id'] ? 'selected' : '' ?>><?= e($__c['name']) ?></option>
                        <?php if (!empty($__c['children'])): foreach ($__c['children'] as $__cc): ?>
                            <option value="<?= (int)$__cc['id'] ?>" <?= $cat === (int)$__cc['id'] ? 'selected' : '' ?>>&nbsp;&nbsp;↳ <?= e($__cc['name']) ?></option>
                        <?php endforeach; endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-outline-primary btn-sm">Lọc</button></div>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th style="width:56px">Ảnh</th>
                    <th>Sản phẩm</th>
                    <th>SKU</th>
                    <th>Danh mục</th>
                    <th class="money">Giá</th>
                    <th class="text-center">Kho</th>
                    <th class="text-center">Đã bán</th>
                    <th class="text-center">Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="9"><div class="empty-state"><?= icon('bi-box') ?>Chưa có sản phẩm. <a href="<?= BASE_URL ?>/quan-tri/san-pham/tao" class="btn btn-sm btn-primary mt-2">Thêm sản phẩm đầu tiên</a></div></td></tr>
                <?php else: foreach ($rows as $__p): ?>
                    <tr>
                        <td><img src="<?= e(image_url($__p['cover_image'] ?? '')) ?>" class="rounded" width="48" height="48" style="object-fit:cover" alt=""></td>
                        <td data-label="Sản phẩm"><a href="<?= BASE_URL ?>/quan-tri/san-pham/sua/<?= (int)$__p['id'] ?>" class="fw-semibold text-decoration-none"><?= e($__p['name']) ?></a>
                            <?php if ($__p['sale_price']): ?><span class="badge text-bg-warning ms-1">KM</span><?php endif; ?>
                            <?php if ($__p['is_featured']): ?><?= icon('bi-star-fill', 'text-warning ms-1') ?><?php endif; ?>
                        </td>
                        <td data-label="SKU" class="text-muted small"><?= e($__p['sku']) ?></td>
                        <td data-label="Danh mục" class="small"><?= e($__p['category_name'] ?? '—') ?></td>
                        <td data-label="Giá" class="money">
                            <div class="fw-semibold"><?= format_money((int)$__p['price']) ?></div>
                            <?php if ($__p['sale_price']): ?><div class="text-success small"><?= format_money((int)$__p['sale_price']) ?></div><?php endif; ?>
                        </td>
                        <td data-label="Kho" class="text-center <?= (int)$__p['quantity'] <= 5 ? 'text-danger fw-bold' : '' ?>"><?= (int)$__p['quantity'] ?></td>
                        <td data-label="Đã bán" class="text-center"><?= (int)$__p['sold_count'] ?></td>
                        <td data-label="Trạng thái" class="text-center">
                            <a href="<?= BASE_URL ?>/quan-tri/san-pham/trang-thai/<?= (int)$__p['id'] ?>" class="badge <?= $__p['status'] ? 'text-bg-success' : 'text-bg-secondary' ?> text-decoration-none" data-confirm="Đổi trạng thái sản phẩm?">
                                <?= $__p['status'] ? 'Hoạt động' : 'Ẩn' ?>
                            </a>
                        </td>
                        <td data-label="Thao tác" class="text-end">
                            <a href="<?= BASE_URL ?>/quan-tri/san-pham/sua/<?= (int)$__p['id'] ?>" class="btn btn-sm btn-light" title="Sửa"><?= icon('bi-pencil') ?></a>
                            <a href="<?= BASE_URL ?>/quan-tri/san-pham/xoa/<?= (int)$__p['id'] ?>" class="btn btn-sm btn-outline-danger" title="Xóa" data-confirm="Xóa sản phẩm này?"><?= icon('bi-trash') ?></a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/includes/partials/pagination.php'; ?>