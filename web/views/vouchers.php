<?php
/** Trang khuyến mãi */
declare(strict_types=1);
?>
<div class="container py-4">
    <div class="app-crumb"><a href="<?= BASE_URL ?>">Trang chủ</a><span class="sep">/</span><span>Khuyến mãi</span></div>
    <h1 class="app-section-title mb-4">Mã giảm giá &amp; ưu đãi</h1>
    <p class="text-muted">Săn ngay các mã giảm giá hấp dẫn và voucher miễn phí vận chuyển đang có tại WoodCon.</p>

    <h2 class="fs-5 fw-bold mb-3">Mã giảm giá đơn hàng</h2>
    <div class="row g-3 mb-4">
        <?php foreach ($discounts as $__v): ?>
            <div class="col-md-4">
                <div class="card h-100"><div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="app-chip active text-uppercase"><?= e($__v['code']) ?></span>
                        <span class="small text-muted"><?= (int)$__v['discount_value'] ?>%</span>
                    </div>
                    <div class="fw-semibold"><?= e($__v['name']) ?></div>
                    <div class="small text-muted"><?= e($__v['description']) ?></div>
                    <div class="small text-muted mt-2">Đơn tối thiểu: <?= format_money((int)$__v['min_order_value']) ?></div>
                </div></div>
            </div>
        <?php endforeach; ?>
    </div>

    <h2 class="fs-5 fw-bold mb-3">Mã miễn phí vận chuyển</h2>
    <div class="row g-3">
        <?php foreach ($freeships as $__v): ?>
            <div class="col-md-4">
                <div class="card h-100"><div class="card-body">
                    <span class="app-chip text-uppercase" style="color:var(--wc-success);border-color:var(--wc-success)"><?= e($__v['code']) ?></span>
                    <div class="fw-semibold mt-2"><?= e($__v['name']) ?></div>
                    <div class="small text-muted"><?= e($__v['description']) ?></div>
                </div></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>