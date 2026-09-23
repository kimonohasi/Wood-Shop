<?php
/**
 * Partial: CHIPS DANH MỤC NHẤN NHANH
 * Biến nhận: $__chips (mảng danh mục cha để hiển thị), $__activeSlug (slug đang active)
 */
declare(strict_types=1);
$__catIcons = [
    'sofa-va-ghe'      => 'chair',
    'ban'              => 'table_restaurant',
    'giuong-va-tu'     => 'bed',
    'ke-va-luu-tru'    => 'shelves',
    'den-va-trang-tri' => 'light',
    'ngoai-troi'       => 'outdoor_grill',
];
?>
<div class="app-cat-chips">
    <a class="app-cat-chip <?= $__activeSlug === '' ? 'active' : '' ?>" href="<?= BASE_URL ?>/tim-kiem"><?= icon('ms-apps') ?>Tất cả</a>
    <?php foreach ($__chips as $__c): ?>
        <a class="app-cat-chip <?= $__activeSlug === $__c['slug'] ? 'active' : '' ?>" href="<?= BASE_URL ?>/danh-muc/<?= e($__c['slug']) ?>">
            <?= icon('ms-' . ($__catIcons[$__c['slug']] ?? 'storefront')) ?><?= e($__c['name']) ?>
        </a>
    <?php endforeach; ?>
</div>