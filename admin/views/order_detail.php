<?php
/** Chi tiết đơn hàng admin */
declare(strict_types=1);
use WoodCon\Order;
$colors = ['pending'=>'warning','manual_verifying'=>'danger','confirmed'=>'info','preparing'=>'secondary','shipping'=>'primary','delivery_failed'=>'dark','delivered'=>'success','returned'=>'danger','cancelled'=>'secondary'];
// Tạm tính = tổng Thành tiền (SL × Đơn giá) của mọi dòng sản phẩm trong đơn — nguồn duy nhất.
$invSubtotal = 0.0;
foreach ($items as $__it) {
    $invSubtotal += (float)($__it['subtotal'] ?? ($__it['quantity'] * $__it['price']));
}
$methodLabel = ['cod'=>'COD','bank'=>'Chuyển khoản','qr'=>'QR code','wallet'=>'Ví điện tử'];
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0"><?= e($order['order_code']) ?></h1>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown"><?= icon('bi-printer', 'me-1') ?>In hóa đơn</button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><a class="dropdown-item" target="_blank" href="<?= BASE_URL ?>/quan-tri/don-hang/hoa-don/<?= e($order['order_code']) ?>?type=a4"><?= icon('bi-file-earmark-text', 'me-1') ?>Hóa đơn A5/A4</a></li>
                <li><a class="dropdown-item" target="_blank" href="<?= BASE_URL ?>/quan-tri/don-hang/hoa-don/<?= e($order['order_code']) ?>?type=pos"><?= icon('bi-receipt', 'me-1') ?>Hóa đơn POS 80mm</a></li>
            </ul>
        </div>
        <a href="<?= BASE_URL ?>/quan-tri/don-hang" class="btn btn-sm btn-light"><?= icon('bi-arrow-left', 'me-1') ?>Quay lại</a>
    </div>
</div>

<!-- Trust / COD gate box -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="admin-stat">
            <div class="stat-label">Trạng thái</div>
            <div class="h5 mb-0 mt-1"><span class="badge text-bg-<?= $colors[$order['order_status']] ?? 'secondary' ?>"><?= e(Order::STATUS_LABEL[$order['order_status']] ?? $order['order_status']) ?></span></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-stat">
            <div class="stat-label">Phương thức</div>
            <div class="fw-bold mt-1"><?= e($methodLabel[$order['payment_method']] ?? $order['payment_method']) ?>
                <span class="badge text-bg-<?= $order['payment_status']==='paid'?'success':($order['payment_status']==='refunded'?'secondary':'warning') ?> ms-1"><?= $order['payment_status']==='paid'?'Đã TT':($order['payment_status']==='refunded'?'Hoàn tiền':'Chưa TT') ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-stat">
            <div class="stat-label">Trust (tại thời điểm đặt)</div>
            <?php if ($order['trust_level_at_order'] === 'green'): ?><span class="badge text-bg-success">Xanh</span>
            <?php elseif ($order['trust_level_at_order'] === 'yellow'): ?><span class="badge text-bg-warning">Vàng</span>
            <?php elseif ($order['trust_level_at_order'] === 'red'): ?><span class="badge text-bg-danger">Đỏ</span>
            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
            <?php if ($order['otp_verified']): ?><span class="badge text-bg-info ms-1">OTP ✓</span><?php endif; ?>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-stat">
            <div class="stat-label">Tổng cộng</div>
            <div class="h5 fw-bold mb-0 mt-1"><?= format_money((int)$order['total_amount']) ?></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Thông tin khách + sản phẩm -->
    <div class="col-lg-7">
        <div class="admin-card mb-3">
            <div class="card-head">Khách hàng & giao hàng</div>
            <div class="card-body">
                <div class="row g-2 small">
                    <div class="col-6"><span class="text-muted">Tên:</span> <strong><?= e($order['customer_name']) ?></strong></div>
                    <div class="col-6"><span class="text-muted">SĐT:</span> <strong><?= e($order['customer_phone']) ?></strong></div>
                    <div class="col-6"><span class="text-muted">Email:</span> <?= e($order['customer_email'] ?: '—') ?></div>
                    <div class="col-6"><span class="text-muted">User:</span> <?= (int)$order['user_id'] ? '#' . (int)$order['user_id'] : 'Khách vãng lai' ?></div>
                    <div class="col-12"><span class="text-muted">Địa chỉ:</span> <?= e($order['address']) ?><?= $order['ward'] ? ', ' . e($order['ward']) : '' ?><?= $order['district'] ? ', ' . e($order['district']) : '' ?><?= $order['city'] ? ', ' . e($order['city']) : '' ?></div>
                    <div class="col-12"><span class="text-muted">Ghi chú:</span> <?= e($order['note'] ?: '—') ?></div>
                    <div class="col-6"><span class="text-muted">Voucher:</span> <?= e($order['voucher_code'] ?: '—') ?></div>
                    <div class="col-6"><span class="text-muted">Freeship:</span> <?= e($order['freeship_code'] ?: '—') ?></div>
                    <?php if ($order['manual_verify_note']): ?><div class="col-12 text-warning"><?= icon('bi-shield-exclamation') ?> <?= e($order['manual_verify_note']) ?></div><?php endif; ?>
                    <?php if ($order['risk_flag']): ?><div class="col-12 text-danger"><?= icon('bi-shield-exclamation') ?> Đơn có cờ rủi ro COD</div><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="card-head">Sản phẩm</div>
            <div class="card-body p-0">
                <table class="table admin-table mb-0">
                    <thead><tr><th>Sản phẩm</th><th class="text-center">SL</th><th class="money">Đơn giá</th><th class="money">Thành tiền</th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $__it): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= e(image_url($__it['cover_image'] ?? '')) ?>" width="40" height="40" class="rounded" style="object-fit:cover" alt="">
                                        <div>
                                            <div class="fw-semibold small"><?= e($__it['product_name']) ?></div>
                                            <div class="text-muted small"><?= e($__it['product_sku'] ?? '') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center"><?= (int)$__it['quantity'] ?></td>
                                <td class="money"><?= format_money((int)$__it['price']) ?></td>
                                <td class="money fw-semibold"><?= format_money((int)($__it['subtotal'] ?? ($__it['quantity'] * $__it['price']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php
                    // Tổng cộng = Tạm tính − Chiết khấu − Giảm giá hạng − Miễn phí ship − Điểm + Phí vận chuyển + Phí lắp đặt.
                    // Giá niêm yết/KM ĐÃ GỒM VAT (giá cuối cùng) -> KHÔNG cộng thêm VAT vào Tổng cộng.
                    // Các dòng VAT chỉ mang tính THÔNG TIN, tách ngược theo công thức:
                    //   giá trước thuế = giá bán / (1 + thuế/100) ; VAT = giá bán − giá trước thuế
                    //   - VAT sản phẩm       : tách ngược từ tiền hàng (Sản phẩm).
                    //   - VAT vận chuyển/lắp đặt: tách ngược RIÊNG từng loại từ phí thực thu
                    //     (phí ship sau freeship / phí lắp đặt), vì phí dịch vụ cũng là
                    //     doanh thu đã gồm VAT, không miễn thuế. Ưu tiên đọc giá trị đã
                    //     lưu (vat_shipping_amount / vat_install_amount) nếu có.
                    $invDiscount = max(0, (float)$order['discount_amount']);
                    $invFreeShip = max(0, (float)$order['freeship_discount']);
                    $invTier     = max(0, (float)($order['tier_discount_amount'] ?? 0));
                    $invTierPct  = max(0, (float)($order['tier_discount_percent'] ?? 0));
                    $invShip     = max(0, (float)$order['shipping_fee']);
                    $invInstall  = max(0, (float)$order['install_fee']);
                    $invPoints   = max(0, (int)$order['points_used']) * max(1, (int)get_setting('point_value', 1000));
                    $invVatRate  = max(0, (float)$order['vat_rate']);
                    $invVatGoods = 0.0;
                    foreach ($items as $__it) {
                        $line = (float)($__it['subtotal'] ?? ($__it['quantity'] * $__it['price']));
                        $rate = max(0, (float)($__it['vat_rate'] ?? $order['vat_rate']));
                        $invVatGoods += $rate > 0 ? ($line - ($line / (1 + $rate / 100))) : 0.0;
                    }
                    $invVatGoods   = (int)round($invVatGoods);
                    // Mức thuế cho phí dịch vụ đọc từ cấu hình ĐỘC LẬP (settings.tax_shipping_rate /
                    // tax_install_rate, mặc định 5%) — không còn lệ thuộc vào thuế suất sản phẩm.
                    $invShipNet = max(0, $invShip - $invFreeShip); // phí ship thực thu (đã gồm VAT)
                    $__invTaxShip = max(0, \WoodCon\TaxRate::shippingRate());
                    $__invTaxInstall = max(0, \WoodCon\TaxRate::installRate());
                    $invVatShip = (float)($order['vat_shipping_amount'] ?? 0) > 0
                        ? (float)$order['vat_shipping_amount']
                        : ($__invTaxShip > 0 ? ($invShipNet - ($invShipNet / (1 + $__invTaxShip / 100))) : 0.0);
                    $invVatInstall = (float)($order['vat_install_amount'] ?? 0) > 0
                        ? (float)$order['vat_install_amount']
                        : ($__invTaxInstall > 0 && $invInstall > 0 ? ($invInstall - ($invInstall / (1 + $__invTaxInstall / 100))) : 0.0);
                    $invVatShip    = (int)round($invVatShip);
                    $invVatInstall = (int)round($invVatInstall);
                    $invTotal = (int)round($invSubtotal - $invDiscount - $invTier - $invFreeShip - $invPoints + $invShip + $invInstall);
                    ?>
                    <tfoot>
                        <tr><td colspan="3" class="text-end text-muted">Tạm tính</td><td class="money"><?= format_money((int)round($invSubtotal)) ?></td></tr>
                        <?php if ($invDiscount > 0): ?><tr><td colspan="3" class="text-end text-muted">Chiết khấu</td><td class="money text-success">-<?= format_money($invDiscount) ?></td></tr><?php endif; ?>
                        <?php if ($invTierPct > 0 && $invTier > 0): ?><tr><td colspan="3" class="text-end text-muted">Giảm giá hạng thành viên (<?= rtrim(rtrim((string)$invTierPct, '0'), '.') ?>%)</td><td class="money text-success">-<?= format_money($invTier) ?></td></tr><?php endif; ?>
                        <?php if ($invFreeShip > 0): ?><tr><td colspan="3" class="text-end text-muted">Miễn phí ship</td><td class="money text-success">-<?= format_money($invFreeShip) ?></td></tr><?php endif; ?>
                        <tr><td colspan="3" class="text-end text-muted">Phí vận chuyển</td><td class="money"><?= format_money($invShip) ?></td></tr>
                        <?php if ($invInstall > 0): ?><tr><td colspan="3" class="text-end text-muted">Phí lắp đặt</td><td class="money"><?= format_money($invInstall) ?></td></tr><?php endif; ?>
                        <?php if ($invPoints > 0): ?><tr><td colspan="3" class="text-end text-muted">Điểm thưởng</td><td class="money text-success">-<?= format_money($invPoints) ?></td></tr><?php endif; ?>
                        <tr class="text-muted"><td colspan="3" class="text-end">VAT sản phẩm (đã gồm trong giá)</td><td class="money"><?= format_money($invVatGoods) ?></td></tr>
                        <?php if ($invVatShip > 0): ?><tr class="text-muted"><td colspan="3" class="text-end">VAT phí vận chuyển (đã gồm trong giá)</td><td class="money"><?= format_money($invVatShip) ?></td></tr><?php endif; ?>
                        <?php if ($invVatInstall > 0): ?><tr class="text-muted"><td colspan="3" class="text-end">VAT phí lắp đặt (đã gồm trong giá)</td><td class="money"><?= format_money($invVatInstall) ?></td></tr><?php endif; ?>
                        <tr class="fw-bold"><td colspan="3" class="text-end">Tổng cộng</td><td class="money"><?= format_money($invTotal) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Cập nhật trạng thái -->
    <div class="col-lg-5">
        <div class="admin-card mb-3">
            <div class="card-head">Cập nhật trạng thái</div>
            <div class="card-body">
                <?php if (empty($transitions)): ?>
                    <div class="alert alert-light small mb-0"><?= icon('bi-lock', 'me-1') ?>Đơn ở trạng thái cuối, không thể đổi trạng thái.</div>
                <?php else: ?>
                    <form method="post" action="<?= BASE_URL ?>/quan-tri/don-hang/trang-thai/<?= e($order['order_code']) ?>">
                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                        <div class="mb-3">
                            <label class="form-label small text-muted">Chuyển sang</label>
                            <select class="form-select" name="order_status" required>
                                <?php foreach ($transitions as $__t): ?>
                                    <option value="<?= e($__t) ?>"><?= e(Order::STATUS_LABEL[$__t] ?? $__t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted">Ghi chú / mã vận đơn</label>
                            <input type="text" class="form-control" name="note" placeholder="Nếu chuyển sang giao hàng: VD:MãVậnĐơn" value="<?= e($order['tracking_code'] ?? '') ?>">
                            <div class="form-text small">Khi chuyển sang "Đang giao hàng", nhập mã vận đơn (VD:<strong>VD:123456</strong>) để lưu tracking.</div>
                        </div>
                        <?php if ($order['order_status'] === 'manual_verifying'): ?>
                            <div class="mb-3">
                                <label class="form-label small text-muted">Ghi chú đối soát thủ công (COD risk)</label>
                                <textarea class="form-control" name="manual_verify_note" rows="2" placeholder="Ghi kết quả xác minh SĐT / thanh toán trước..."><?= e($order['manual_verify_note'] ?? '') ?></textarea>
                            </div>
                        <?php endif; ?>
                        <button class="btn btn-primary w-100"><?= icon('bi-check2', 'me-1') ?>Cập nhật</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Theo dõi vận chuyển -->
        <div class="admin-card">
            <div class="card-head">Theo dõi vận chuyển</div>
            <div class="card-body small">
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Hãng vận chuyển</span><span><?= e($order['delivery_company'] ?: '—') ?></span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Mã vận đơn</span><span><?= e($order['tracking_code'] ?: '—') ?></span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Số lần giao thất bại</span><span class="<?= (int)$order['delivery_fail_count'] > 0 ? 'text-danger fw-bold' : '' ?>"><?= (int)$order['delivery_fail_count'] ?></span></div>
                <?php if ($order['delivered_at']): ?><div class="d-flex justify-content-between py-1"><span class="text-muted">Đã giao lúc</span><span><?= format_date($order['delivered_at']) ?></span></div><?php endif; ?>
                <?php if ($order['points_earned']): ?><div class="d-flex justify-content-between py-1"><span class="text-muted">Điểm tích</span><span><?= (int)$order['points_earned'] ?></span></div><?php endif; ?>
                <?php if ($order['cancel_request_status'] !== 'none'): ?>
                    <hr>
                    <div class="text-muted">Yêu cầu hủy: <span class="badge text-bg-warning"><?= e($order['cancel_request_status']) ?></span></div>
                    <?php if ($order['cancel_reason']): ?><div class="text-muted mt-1">Lý do: <?= e($order['cancel_reason']) ?></div><?php endif; ?>
                    <?php if ($order['cancel_reject_reason']): ?><div class="text-danger mt-1">Từ chối: <?= e($order['cancel_reject_reason']) ?></div><?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>