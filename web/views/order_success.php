<?php
/** Trang hoàn tất đặt hàng */
declare(strict_types=1);
use WoodCon\Order;
$__payLabels = ['cod' => 'Thanh toán khi nhận hàng (COD)', 'bank' => 'Chuyển khoản ngân hàng', 'qr' => 'Quét mã QR', 'wallet' => 'Ví điện tử'];
?>
<div class="container py-5" style="max-width:640px">
    <div class="text-center mb-4">
        <?= icon('ms-check_circle', 'style="font-size:4rem;color:var(--wc-success)"') ?>
        <h1 class="app-section-title mt-3">Đặt hàng thành công!</h1>
        <p class="text-muted">Cảm ơn bạn đã tin tưởng WoodCon. Chúng tôi sẽ xác nhận đơn hàng trong thời gian sớm nhất.</p>
    </div>

    <?php if ($order): ?>
        <div class="summary-box p-4 mb-4">
            <div class="d-flex justify-content-between py-1"><span class="text-muted">Mã đơn hàng</span><strong><?= e($order['order_code']) ?></strong></div>
            <div class="d-flex justify-content-between py-1">
                <span class="text-muted">Trạng thái</span>
                <span class="status-badge" style="background:var(--wc-surface-highest)">
                    <?= e(Order::STATUS_LABEL[$order['order_status']] ?? $order['order_status']) ?>
                </span>
            </div>
            <div class="d-flex justify-content-between py-1"><span class="text-muted">Phương thức</span><span><?= e($__payLabels[$order['payment_method']] ?? ucfirst($order['payment_method'])) ?></span></div>
            <div class="d-flex justify-content-between py-1"><span class="text-muted">Tổng cộng</span><strong><?= format_money((int)$order['total_amount']) ?></strong></div>
            <hr class="divider-dash">
            <div class="small text-muted mb-2">Danh sách sản phẩm:</div>
            <?php foreach ($items as $__it): ?>
                <div class="d-flex justify-content-between small py-1">
                    <span><?= e($__it['product_name']) ?> × <?= (int)$__it['quantity'] ?></span>
                    <span><?= format_money((int)$__it['subtotal']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="d-grid gap-2">
        <a href="<?= BASE_URL ?>/tra-cuu-don-hang?code=<?= e($order['order_code'] ?? '') ?>" class="btn btn-primary">Theo dõi đơn hàng</a>
        <a href="<?= BASE_URL ?>" class="btn btn-outline-primary">Tiếp tục mua sắm</a>
    </div>
</div>