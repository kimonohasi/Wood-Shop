<?php
/** Tra cứu đơn hàng (không cần đăng nhập) */
declare(strict_types=1);
use WoodCon\Order;
?>
<div class="container py-4" style="max-width:860px">
    <div class="app-crumb"><a href="<?= BASE_URL ?>">Trang chủ</a><span class="sep">/</span><span>Tra cứu đơn hàng</span></div>
    <h1 class="app-section-title mb-4">Tra cứu đơn hàng</h1>

    <form class="row g-2 mb-4" method="post">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
        <div class="col-md-5">
            <input type="text" class="form-control" name="code" placeholder="Mã đơn hàng" value="<?= e($_GET['code'] ?? $_POST['code'] ?? '') ?>" required>
        </div>
        <div class="col-md-5">
            <input type="tel" class="form-control" name="phone" placeholder="Số điện thoại" required>
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-primary">Tra cứu</button>
        </div>
    </form>

    <?php if ($found): ?>
        <div class="alert alert-warning"><?= e($found) ?></div>
    <?php endif; ?>

    <?php if ($order): ?>
        <div class="summary-box p-4 mb-4">
            <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <div class="fw-bold fs-5"><?= e($order['order_code']) ?></div>
                    <div class="small text-muted">Đặt ngày <?= format_date($order['created_at']) ?></div>
                </div>
                <span class="status-badge" style="background:var(--wc-surface-highest)"><?= e(Order::STATUS_LABEL[$order['order_status']] ?? $order['order_status']) ?></span>
            </div>

            <hr class="divider-dash">
            <?php foreach ($items as $__it): ?>
                <div class="d-flex align-items-center gap-3 py-2">
                    <img src="<?= e(image_url($__it['cover_image'] ?? '')) ?>" width="56" height="56" class="rounded" style="object-fit:cover" alt="">
                    <div class="flex-grow-1">
                        <div class="fw-semibold small"><?= e($__it['product_name']) ?></div>
                        <div class="small text-muted"><?= format_money((int)$__it['price']) ?> × <?= (int)$__it['quantity'] ?></div>
                    </div>
                    <div class="fw-bold"><?= format_money((int)$__it['subtotal']) ?></div>
                </div>
            <?php endforeach; ?>

            <hr class="divider-dash">
            <?php
            // VAT tách ngược cho phí dịch vụ (đã gồm trong giá) — đồng bộ admin order_detail.
            $__trFreeship = max(0, (float)$order['freeship_discount']);
            $__trShip     = max(0, (float)$order['shipping_fee']);
            $__trInstall  = max(0, (float)$order['install_fee']);
            // Mức thuế cho phí dịch vụ đọc từ cấu hình ĐỘC LẬP (mặc định 5%), không lệ thuộc thuế sản phẩm.
            $__trSvcRate  = \WoodCon\TaxRate::shippingRate();
            $__trSvcInstallRate = \WoodCon\TaxRate::installRate();
            $__trShipNet  = max(0, $__trShip - $__trFreeship);
            $__trVatShip = (float)($order['vat_shipping_amount'] ?? 0) > 0
                ? (float)$order['vat_shipping_amount']
                : ($__trSvcRate > 0 ? ($__trShipNet - ($__trShipNet / (1 + $__trSvcRate / 100))) : 0.0);
            $__trVatInstall = (float)($order['vat_install_amount'] ?? 0) > 0
                ? (float)$order['vat_install_amount']
                : ($__trSvcInstallRate > 0 && $__trInstall > 0 ? ($__trInstall - ($__trInstall / (1 + $__trSvcInstallRate / 100))) : 0.0);
            ?>
            <div class="d-flex justify-content-between py-1"><span class="text-muted">Phí vận chuyển</span><span><?= format_money((int)$order['shipping_fee']) ?></span></div>
            <?php if ($__trInstall > 0): ?><div class="d-flex justify-content-between py-1"><span class="text-muted">Phí lắp đặt</span><span><?= format_money((int)round($__trInstall)) ?></span></div><?php endif; ?>
            <div class="d-flex justify-content-between py-1"><span class="text-muted">Giảm giá</span><span class="text-success">-<?= format_money((int)$order['discount_amount']) ?></span></div>
            <?php $__trTierAmt = max(0, (float)($order['tier_discount_amount'] ?? 0)); if ($__trTierAmt > 0): ?><div class="d-flex justify-content-between py-1"><span class="text-muted">Giảm giá hạng thành viên<?= (float)($order['tier_discount_percent'] ?? 0) > 0 ? ' (' . rtrim(rtrim((string)$order['tier_discount_percent'], '0'), '.') . '%)' : '' ?></span><span class="text-success">-<?= format_money((int)round($__trTierAmt)) ?></span></div><?php endif; ?>
            <?php if ($__trFreeship > 0): ?><div class="d-flex justify-content-between py-1"><span class="text-muted">Miễn phí vận chuyển</span><span class="text-success">-<?= format_money((int)round($__trFreeship)) ?></span></div><?php endif; ?>
            <div class="d-flex justify-content-between py-1"><span class="text-muted">VAT <?= rtrim(rtrim((string)($order['vat_rate'] ?? 8), '0'), '.') ?>%</span><span><?= format_money((int)$order['vat_amount']) ?></span></div>
            <?php if ($__trVatShip > 0): ?><div class="d-flex justify-content-between py-1"><span class="text-muted">VAT vận chuyển</span><span><?= format_money((int)round($__trVatShip)) ?></span></div><?php endif; ?>
            <?php if ($__trVatInstall > 0): ?><div class="d-flex justify-content-between py-1"><span class="text-muted">VAT lắp đặt</span><span><?= format_money((int)round($__trVatInstall)) ?></span></div><?php endif; ?>
            <div class="d-flex justify-content-between py-1 fw-bold fs-5"><span>Tổng thanh toán</span><span><?= format_money((int)$order['total_amount']) ?></span></div>

            <?php if ($order['order_status'] === 'pending'): ?>
                <div class="small text-muted mt-3">
                    Bạn có thể yêu cầu hủy đơn trong mục Đơn hàng của tôi (khi đã đăng nhập) hoặc liên hệ CSKH.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>