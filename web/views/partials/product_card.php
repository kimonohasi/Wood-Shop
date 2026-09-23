<?php
/** Product card chuẩn Mộc An (dùng chung: home, danh-muc, tim-kiem, liên quan) */
declare(strict_types=1);
$__price = \WoodCon\Product::effectivePrice($__p ?? $p);
$__old = ($__p['sale_price'] ?? 0) > 0 && $__p['sale_price'] < $__p['price'] ? (float)$__p['price'] : 0;
?>
<div class="col">
    <div class="app-product-card">
        <a class="media d-block" href="<?= BASE_URL ?>/san-pham/<?= e($__p['slug']) ?>" aria-label="<?= e($__p['name']) ?>">
            <?php if ($__old > 0): ?>
                <span class="sale-tag">-<?= (int)round((1 - $__price / $__old) * 100) ?>%</span>
            <?php endif; ?>
            <img loading="lazy" class="card-img" src="<?= e(image_url($__p['cover_image'] ?? '')) ?>" alt="<?= e($__p['name']) ?>">
        </a>
        <div class="card-body">
            <div class="card-cat"><?= e($__p['category_name'] ?? 'Nội thất') ?></div>
            <a class="d-block" href="<?= BASE_URL ?>/san-pham/<?= e($__p['slug']) ?>">
                <h3 class="card-title"><?= e($__p['name']) ?></h3>
            </a>
            <div class="d-flex align-items-baseline gap-2 mt-1">
                <span class="price"><?= format_money((int)$__price) ?></span>
                <?php if ($__old > 0): ?><span class="price-old"><?= format_money((int)$__old) ?></span><?php endif; ?>
            </div>
            <button type="button" class="btn-quick" data-quick-add="<?= (int)$__p['id'] ?>">
                <?= icon('ms-add_shopping_cart', 'me-1', 'style="font-size:16px;vertical-align:-3px"') ?>
                Thêm vào giỏ
            </button>
        </div>
    </div>
</div>