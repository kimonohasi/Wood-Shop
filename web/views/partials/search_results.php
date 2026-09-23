<?php
/**
 * WoodCon - Partial: chỉ phần kết quả (active chips + grid)
 * Dùng cho AJAX khi filter thay đổi — chỉ phần này được thay mới.
 *
 * Biến cần: $f, $products, $total, $categories, $brands
 */
declare(strict_types=1);

$__f = $f ?? [];

// Tính filter đang active + chip tóm tắt
$__active = [];
$__activeCount = 0;
$__selCatIds = !empty($__f['cat_ids']) && is_array($__f['cat_ids'])
    ? array_map('intval', $__f['cat_ids'])
    : (is_array($__f['cat'] ?? null) ? array_map('intval', $__f['cat']) : []);
if (!empty($__selCatIds)) {
    $__active['cat'] = 'Loại';
    $__activeCount += count($__selCatIds);
}
$__selBrands = $f['brand_ids'] ?? [];
if (!is_array($__selBrands)) $__selBrands = !empty($f['brand']) ? [(int)$f['brand']] : [];
if (!empty($__selBrands)) {
    $__active['brand'] = 'Thương hiệu'; $__activeCount += count($__selBrands);
}
if (!empty($__f['min_price']) || !empty($__f['max_price'])) {
    $__active['price'] = 'Giá'; $__activeCount++;
}
if (!empty($__f['sale_only']))      { $__active['sale'] = 'Khuyến mãi'; $__activeCount++; }
if (!empty($__f['is_new']))         { $__active['new']  = 'Mới nhất';   $__activeCount++; }
if (!empty($__f['is_best_seller'])) { $__active['best'] = 'Bán chạy';   $__activeCount++; }

$__catLabel = '';
if (!empty($__selCatIds)) {
    $__catNames = [];
    foreach ($categories as $__cc) {
        if (in_array((int)$__cc['id'], $__selCatIds, true)) $__catNames[] = $__cc['name'];
        if (!empty($__cc['children'])) {
            foreach ($__cc['children'] as $__sub) {
                if (in_array((int)$__sub['id'], $__selCatIds, true)) $__catNames[] = $__sub['name'];
            }
        }
    }
    $__catLabel = !empty($__catNames)
        ? implode(', ', array_slice($__catNames, 0, 3)) . (count($__catNames) > 3 ? '…' : '')
        : '';
}
// Danh sách thương hiệu đang chọn theo id => name
$__brandNames = [];
foreach ($brands as $__bb) {
    if (in_array((int)$__bb['id'], array_map('intval', $__selBrands), true)) {
        $__brandNames[(int)$__bb['id']] = $__bb['name'];
    }
}
$__clearUrl  = BASE_URL . '/tim-kiem' . ($__f['q'] !== '' ? '?q=' . rawurlencode($__f['q']) : '');

// Helper xây URL xóa 1 key (giữ nguyên query còn lại, hỗ trợ key có [])
$__rmKey = static function (string $key) use ($__f) {
    $__q = $_GET;
    if (str_ends_with($key, '[]')) {
        $base = substr($key, 0, -2);
        unset($__q[$key], $__q[$base]);
    } else {
        unset($__q[$key], $__q[$key . '[]']);
    }
    $__q['route'] = 'tim-kiem';
    return BASE_URL . '/tim-kiem?' . http_build_query($__q);
};
$__rmPair = static function (array $keys) use ($__f) {
    $__q = $_GET;
    foreach ($keys as $__k) unset($__q[$__k]);
    $__q['route'] = 'tim-kiem';
    return BASE_URL . '/tim-kiem?' . http_build_query($__q);
};
$__rmBrand = static function (int $bid) {
    $__q = $_GET;
    if (isset($__q['brand']) && is_array($__q['brand'])) {
        $__q['brand'] = array_values(array_filter($__q['brand'], fn($v) => (int)$v !== $bid));
    } else {
        unset($__q['brand']);
    }
    $__q['route'] = 'tim-kiem';
    return BASE_URL . '/tim-kiem?' . http_build_query($__q);
};
?>

<!-- ============ ACTIVE CHIPS (AJAX reload) ============ -->
<div id="activeChipsSlot">
<?php if ($__activeCount > 0): ?>
    <div class="app-active-summary">
        <?php if (!empty($__active['cat'])): ?>
            <span class="app-mini-chip">Danh mục: <?= e($__catLabel ?: '...') ?>
                <a href="<?= e($__rmKey('cat_ids[]')) ?>" data-ajax-link class="app-mini-x"><?= icon('ms-close') ?></a>
            </span>
        <?php endif; ?>
        <?php if (!empty($__brandNames)): foreach ($__brandNames as $__bid => $__bname): ?>
            <span class="app-mini-chip">Thương hiệu: <?= e($__bname) ?>
                <a href="<?= e($__rmBrand((int)$__bid)) ?>" data-ajax-link class="app-mini-x"><?= icon('ms-close') ?></a>
            </span>
        <?php endforeach; endif; ?>
        <?php if (!empty($__active['price'])): ?>
            <span class="app-mini-chip"><?= format_money((int)($__f['min_price'] ?: 0)) ?> – <?= format_money((int)($__f['max_price'] ?: 0)) ?>
                <a href="<?= e($__rmPair(['min_price','max_price'])) ?>" data-ajax-link class="app-mini-x"><?= icon('ms-close') ?></a>
            </span>
        <?php endif; ?>
        <?php if (!empty($__active['new'])): ?>
            <span class="app-mini-chip">Mới nhất
                <a href="<?= e($__rmKey('new')) ?>" data-ajax-link class="app-mini-x"><?= icon('ms-close') ?></a>
            </span>
        <?php endif; ?>
        <?php if (!empty($__active['best'])): ?>
            <span class="app-mini-chip">Bán chạy
                <a href="<?= e($__rmKey('best')) ?>" data-ajax-link class="app-mini-x"><?= icon('ms-close') ?></a>
            </span>
        <?php endif; ?>
        <?php if (!empty($__active['sale'])): ?>
            <span class="app-mini-chip">Khuyến mãi
                <a href="<?= e($__rmKey('sale')) ?>" data-ajax-link class="app-mini-x"><?= icon('ms-close') ?></a>
            </span>
        <?php endif; ?>
        <a class="app-clear-link" href="<?= e($__clearUrl) ?>" data-ajax-link>Xóa tất cả</a>
    </div>
<?php endif; ?>
</div>

<!-- ============ SORT BAR (AJAX reload) ============ -->
<div class="app-sortbar" id="sortBarSlot">
    <label class="small text-muted me-1">Sắp xếp:</label>
    <select class="form-select form-select-sm" id="sortSelect" data-sort>
        <option value="best"       <?= $__f['sort'] === 'best' ? 'selected' : '' ?>>Bán chạy</option>
        <option value="new"        <?= $__f['sort'] === 'new' ? 'selected' : '' ?>>Mới nhất</option>
        <option value="price_asc"  <?= $__f['sort'] === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
        <option value="price_desc" <?= $__f['sort'] === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
    </select>
    <span class="text-muted small ms-auto"><span id="resultCount"><?= (int)$total ?></span> kết quả</span>
</div>

<!-- ============ PRODUCT GRID (AJAX reload) ============ -->
<div id="productGridSlot" class="app-ajax-slot">
<?php if (empty($products)): ?>
    <div class="app-empty">
        <?= icon('ms-search_off', 'd-block mb-2', 'style="font-size:3rem;color:var(--wc-outline)"') ?>
        Không có kết quả phù hợp <a href="<?= e($__clearUrl) ?>" data-ajax-link>Xem tất cả</a>.
    </div>
<?php else: ?>
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3">
        <?php foreach ($products as $__p): ?><?php require BASE_PATH . '/web/views/partials/product_card.php'; ?><?php endforeach; ?>
    </div>
    <?php $pagerBase = BASE_URL . '/tim-kiem'; require BASE_PATH . '/includes/partials/pagination.php'; ?>
<?php endif; ?>
</div>