<?php
/** Chi tiết đơn hàng từ tài khoản */
declare(strict_types=1);
use WoodCon\Order;
?>
<div class="container py-4">
    <div class="row g-4">
        <aside class="col-lg-3">
            <div class="app-filter-card">
                <ul class="nav flex-column p-2 small">
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/tai-khoan">Thông tin</a></li>
                    <li class="nav-item"><a class="nav-link active" href="<?= BASE_URL ?>/tai-khoan/don-hang">Đơn hàng</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/tai-khoan/yeu-thich">Yêu thích</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/tai-khoan/diem-thuong">Điểm thưởng</a></li>
                </ul>
            </div>
        </aside>

        <div class="col-lg-9">
            <div class="app-crumb"><a href="<?= BASE_URL ?>/tai-khoan/don-hang">Đơn hàng của tôi</a><span class="sep">/</span><span><?= e($order['order_code']) ?></span></div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h1 class="app-section-title mb-0"><?= e($order['order_code']) ?></h1>
                <span class="status-badge" style="background:var(--wc-surface-highest)"><?= e(Order::STATUS_LABEL[$order['order_status']] ?? $order['order_status']) ?></span>
            </div>

            <div class="card mb-3"><div class="card-body">
                <h6 class="fw-bold mb-2">Thông tin giao hàng</h6>
                <div class="small"><?= e($order['customer_name']) ?> - <?= e($order['customer_phone'] ?? '') ?></div>
                <div class="small text-muted"><?= e($order['address']) ?>, <?= e($order['ward'] ?? '') ?>, <?= e($order['district'] ?? '') ?>, <?= e($order['city'] ?? '') ?></div>
            </div></div>

            <div class="card mb-3"><div class="card-body">
                <h6 class="fw-bold mb-2">Sản phẩm</h6>
                <?php foreach ($items as $__it): ?>
                    <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                        <img src="<?= e(image_url($__it['cover_image'] ?? '')) ?>" width="52" height="52" class="rounded" style="object-fit:cover" alt="">
                        <div class="flex-grow-1">
                            <div class="fw-semibold small"><?= e($__it['product_name']) ?></div>
                            <div class="small text-muted"><?= format_money((int)$__it['price']) ?> × <?= (int)$__it['quantity'] ?></div>
                        </div>
                        <div class="fw-bold"><?= format_money((int)$__it['subtotal']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div></div>

            <?php
            // VAT tách ngược cho phí dịch vụ (đã gồm trong giá) — đồng bộ admin order_detail.
            $__acFreeship = max(0, (float)$order['freeship_discount']);
            $__acShip     = max(0, (float)$order['shipping_fee']);
            $__acInstall  = max(0, (float)$order['install_fee']);
            // Mức thuế cho phí dịch vụ đọc từ cấu hình ĐỘC LẬP (mặc định 5%), không lệ thuộc thuế sản phẩm.
            $__acSvcRate  = \WoodCon\TaxRate::shippingRate();
            $__acSvcInstallRate = \WoodCon\TaxRate::installRate();
            $__acShipNet  = max(0, $__acShip - $__acFreeship);
            $__acVatShip = (float)($order['vat_shipping_amount'] ?? 0) > 0
                ? (float)$order['vat_shipping_amount']
                : ($__acSvcRate > 0 ? ($__acShipNet - ($__acShipNet / (1 + $__acSvcRate / 100))) : 0.0);
            $__acVatInstall = (float)($order['vat_install_amount'] ?? 0) > 0
                ? (float)$order['vat_install_amount']
                : ($__acSvcInstallRate > 0 && $__acInstall > 0 ? ($__acInstall - ($__acInstall / (1 + $__acSvcInstallRate / 100))) : 0.0);
            ?>
            <div class="summary-box p-4">
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Phí vận chuyển</span><span><?= format_money((int)$order['shipping_fee']) ?></span></div>
                <?php if ($__acInstall > 0): ?><div class="d-flex justify-content-between py-1"><span class="text-muted">Phí lắp đặt</span><span><?= format_money((int)round($__acInstall)) ?></span></div><?php endif; ?>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Giảm giá</span><span class="text-success">-<?= format_money((int)$order['discount_amount']) ?></span></div>
                <?php $__acTierAmt = max(0, (float)($order['tier_discount_amount'] ?? 0)); if ($__acTierAmt > 0): ?><div class="d-flex justify-content-between py-1"><span class="text-muted">Giảm giá hạng thành viên<?= (float)($order['tier_discount_percent'] ?? 0) > 0 ? ' (' . rtrim(rtrim((string)$order['tier_discount_percent'], '0'), '.') . '%)' : '' ?></span><span class="text-success">-<?= format_money((int)round($__acTierAmt)) ?></span></div><?php endif; ?>
                <?php if ($__acFreeship > 0): ?><div class="d-flex justify-content-between py-1"><span class="text-muted">Miễn phí vận chuyển</span><span class="text-success">-<?= format_money((int)round($__acFreeship)) ?></span></div><?php endif; ?>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">VAT <?= rtrim(rtrim((string)($order['vat_rate'] ?? 8), '0'), '.') ?>%</span><span><?= format_money((int)$order['vat_amount']) ?></span></div>
                <?php if ($__acVatShip > 0): ?><div class="d-flex justify-content-between py-1"><span class="text-muted">VAT vận chuyển</span><span><?= format_money((int)round($__acVatShip)) ?></span></div><?php endif; ?>
                <?php if ($__acVatInstall > 0): ?><div class="d-flex justify-content-between py-1"><span class="text-muted">VAT lắp đặt</span><span><?= format_money((int)round($__acVatInstall)) ?></span></div><?php endif; ?>
                <div class="d-flex justify-content-between py-1 fw-bold fs-5 border-top mt-1 pt-2"><span>Tổng thanh toán</span><span><?= format_money((int)$order['total_amount']) ?></span></div>

                <?php if ($order['order_status'] === 'pending'): ?>
                    <div class="d-grid mt-3">
                        <form method="post" action="<?= BASE_URL ?>/tai-khoan/don-hang/<?= e($order['order_code']) ?>/huy" onsubmit="return confirm(t('web.account.order_detail.confirm_cancel'))">
                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                            <button class="btn btn-outline-danger w-100 btn-sm">Hủy đơn \(miễn phí\)</button>
                        </form>
                    </div>
                <?php elseif (in_array($order['order_status'], ['confirmed', 'preparing'], true)): ?>
                    <?php $__cr = $order['cancel_request_status'] ?? 'none'; ?>
                    <?php if ($__cr === 'none'): ?>
                        <div class="mt-3 p-3 border rounded bg-light">
                            <div class="small fw-semibold mb-2">Đơn đã xác nhận — yêu cầu hủy cần được duyệt:</div>
                            <form method="post" action="<?= BASE_URL ?>/tai-khoan/don-hang/<?= e($order['order_code']) ?>/huy-yeu-cau">
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                <select name="reason" class="form-select form-select-sm mb-2">
                                    <option>Tôi đặt nhầm sản phẩm</option>
                                    <option>Tìm được giá tốt hơn ở nơi khác</option>
                                    <option>Thời gian giao hàng quá lâu</option>
                                    <option>Thay đổi nhu cầu / không còn nhu cầu</option>
                                    <option>Lý do khác</option>
                                </select>
                                <?php if (($order['payment_status'] ?? '') === 'paid'): ?>
                                    <div class="small text-muted mb-2"><?= icon('ms-info', 'me-1', 'style="font-size:16px;vertical-align:-3px"') ?>Đơn đã thanh toán — số tiền sẽ được hoàn về theo phương thức bạn đã chọn\.</div>
                                <?php endif; ?>
                                <button class="btn btn-outline-danger w-100 btn-sm">Gửi yêu cầu hủy</button>
                            </form>
                        </div>
                    <?php elseif ($__cr === 'requested'): ?>
                        <div class="alert alert-warning small mt-3 mb-0"><?= icon('ms-history', 'me-1', 'style="font-size:16px;vertical-align:-3px"') ?>Yêu cầu hủy của bạn đang chờ admin xử lý\.</div>
                    <?php elseif ($__cr === 'rejected'): ?>
                        <div class="alert alert-secondary small mt-3 mb-0"><?= icon('ms-cancel', 'me-1', 'style="font-size:16px;vertical-align:-3px"') ?>Yêu cầu hủy đã bị từ chối\.<?php if ($order['cancel_reject_reason']): ?> Lý do: <?= e($order['cancel_reject_reason']) ?><?php endif; ?></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>