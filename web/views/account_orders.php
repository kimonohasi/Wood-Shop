<?php
/** Danh sách đơn hàng của tài khoản */
declare(strict_types=1);
use WoodCon\Order;
?>
<div class="container py-4">
    <div class="row g-4">
        <aside class="col-lg-3">
            <div class="app-filter-card">
                <div class="p-3 border-bottom"><div class="fw-bold"><?= e(current_user()['name']) ?></div></div>
                <ul class="nav flex-column p-2 small">
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/tai-khoan">Thông tin</a></li>
                    <li class="nav-item"><a class="nav-link active" href="<?= BASE_URL ?>/tai-khoan/don-hang">Đơn hàng</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/tai-khoan/yeu-thich">Yêu thích</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/tai-khoan/diem-thuong">Điểm thưởng</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/tai-khoan/doi-mat-khau">Đổi mật khẩu</a></li>
                </ul>
            </div>
        </aside>

        <div class="col-lg-9">
            <h1 class="app-section-title mb-3">Đơn hàng của tôi</h1>

            <?php if (empty($orders)): ?>
                <div class="app-empty"><?= icon('ms-package_2', 'd-block mb-2', 'style="font-size:3rem;color:var(--wc-outline)"') ?>Bạn chưa có đơn hàng nào</div>
            <?php else: ?>
                <div class="d-grid gap-3">
                    <?php foreach ($orders as $__o): ?>
                        <a class="card p-3" href="<?= BASE_URL ?>/tai-khoan/don-hang/<?= e($__o['order_code']) ?>" style="color:var(--wc-text)">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <div class="fw-bold"><?= e($__o['order_code']) ?></div>
                                    <div class="small text-muted"><?= format_date($__o['created_at']) ?></div>
                                </div>
                                <div class="text-end">
                                    <span class="status-badge" style="background:var(--wc-surface-highest)"><?= e(Order::STATUS_LABEL[$__o['order_status']] ?? $__o['order_status']) ?></span>
                                    <div class="fw-bold mt-1"><?= format_money((int)$__o['total_amount']) ?></div>
                                </div>
                            </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php require BASE_PATH . '/includes/partials/pagination.php'; ?>

        <?php endif; ?>
        </div>
    </div>
</div>