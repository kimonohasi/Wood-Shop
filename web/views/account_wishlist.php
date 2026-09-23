<?php
/** Danh sách yêu thích */
declare(strict_types=1);
?>
<div class="container py-4">
    <div class="row g-4">
        <aside class="col-lg-3">
            <div class="app-filter-card">
                <ul class="nav flex-column p-2 small">
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/tai-khoan">Thông tin</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/tai-khoan/don-hang">Đơn hàng</a></li>
                    <li class="nav-item"><a class="nav-link active" href="<?= BASE_URL ?>/tai-khoan/yeu-thich">Yêu thích</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/tai-khoan/diem-thuong">Điểm thưởng</a></li>
                </ul>
            </div>
        </aside>
        <div class="col-lg-9">
            <h1 class="app-section-title mb-3">Sản phẩm yêu thích</h1>
            <?php if (empty($items)): ?>
                <div class="app-empty"><?= icon('ms-favorite_border', 'd-block mb-2', 'style="font-size:3rem;color:var(--wc-outline)"') ?>Bạn chưa thêm sản phẩm nào vào danh sách yêu thích</div>
            <?php else: ?>
                <div class="row row-cols-2 row-cols-md-3 g-3">
                    <?php foreach ($items as $__p): ?><?php require BASE_PATH . '/web/views/partials/product_card.php'; ?><?php endforeach; ?>
                </div>
                <?php require BASE_PATH . '/includes/partials/pagination.php'; ?>
            <?php endif; ?>
        </div>
    </div>
</div>