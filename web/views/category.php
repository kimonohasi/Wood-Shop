<?php
/** Trang danh mục sản phẩm */
declare(strict_types=1);
?>
<div class="app-container pb-4">

    <div class="app-crumb">
        <a href="<?= BASE_URL ?>">Trang chủ</a><span class="sep">/</span><span class="cur"><?= e($category['name']) ?></span>
    </div>

    <!-- Header -->
    <?php if (!empty($parents)): ?>
        <?php
        $__activeSlug = $category['slug'];
        foreach ($parents as $__p) {
            if ((int)$__p['id'] === (int)($category['parent_id'] ?? 0)) {
                $__activeSlug = $__p['slug'];
                break;
            }
        }
        $__chips = $parents; ?>
        <?php require BASE_PATH . '/web/views/partials/cat_chips.php'; ?>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-2 mb-3">
        <h1 class="app-section-title"><?= e($category['name']) ?></h1>
        <span class="text-muted"><?= (int)$total ?> sản phẩm</span>
    </div>

    <div class="d-flex align-items-center justify-content-end mb-3 flex-wrap gap-2">
        <select class="form-select form-select-sm w-auto" id="sortSelect">
            <option value="new" <?= $f['sort'] === 'new' ? 'selected' : '' ?>>Sắp xếp: Mới nhất</option>
            <option value="best" <?= $f['sort'] === 'best' ? 'selected' : '' ?>>Bán chạy nhất</option>
            <option value="price_asc" <?= $f['sort'] === 'price_asc' ? 'selected' : '' ?>>Giá: thấp đến cao</option>
            <option value="price_desc" <?= $f['sort'] === 'price_desc' ? 'selected' : '' ?>>Giá: cao đến thấp</option>
            <option value="sale" <?= $f['sort'] === 'sale' ? 'selected' : '' ?>>Ưu đãi tốt</option>
        </select>
    </div>

    <?php if (empty($products)): ?>
        <div class="app-empty">
            <?= icon('ms-search_off', 'd-block mb-3', 'style="font-size:3rem;color:var(--wc-outline)"') ?>
            Không tìm thấy sản phẩm phù hợp.
        </div>
    <?php else: ?>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($products as $__p): ?><?php require BASE_PATH . '/web/views/partials/product_card.php'; ?><?php endforeach; ?>
        </div>
        <?php require BASE_PATH . '/includes/partials/pagination.php'; ?>
    <?php endif; ?>
</div>

<script>
document.getElementById('sortSelect').addEventListener('change', function () {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', this.value);
    window.location = url.toString();
});
</script>