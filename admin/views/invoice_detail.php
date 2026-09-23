<?php
/** Chi tiết Hóa đơn (immutable — không có nút Xóa) */
declare(strict_types=1);
$inv      = $invoice ?? [];
$items    = $items ?? [];
$order    = $order ?? [];
$original = $original ?? null;
$isRefund = ($inv['type'] ?? '') === 'REFUND_INVOICE';
$typeLabel = \WoodCon\Invoice::TYPE_LABEL;
$fmt = fn($v) => format_money(abs((float)$v));
$sign = $isRefund ? '-' : '';
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= BASE_URL ?>/quan-tri/hoa-don" class="btn btn-outline-primary btn-sm" title="Danh sách hóa đơn"><?= icon('bi-list-ul', 'me-1') ?>Danh sách</a>
        <div>
<h1 class="h4 admin-page-title mb-0"><?= e($typeLabel[$inv['type']] ?? $inv['type']) ?> — <?= e($inv['invoice_number'] ?? '') ?></h1>
    </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/quan-tri/hoa-don/in/<?= urlencode($inv['invoice_number']) ?>?type=a4" target="_blank" class="btn btn-sm btn-primary"><?= icon('bi-printer', 'me-1') ?>In A4</a>
        <a href="<?= BASE_URL ?>/quan-tri/hoa-don/in/<?= urlencode($inv['invoice_number']) ?>?type=pos" target="_blank" class="btn btn-sm btn-outline-secondary"><?= icon('bi-receipt', 'me-1') ?>In POS</a>
    </div>
</div>

<?php if (!empty($original)): ?>
<div class="alert alert-info d-flex align-items-center gap-2 py-2 small mb-3">
    <?= icon('bi-link-45deg') ?>
    Liên kết đến hóa đơn bán gốc:
    <a href="<?= BASE_URL ?>/quan-tri/hoa-don/xem/<?= urlencode($original['invoice_number']) ?>" class="fw-semibold"><?= e($original['invoice_number']) ?></a>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="admin-card mb-3">
            <div class="card-head">Thông tin khách hàng</div>
            <div class="card-body small">
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Tên</span><span class="fw-semibold"><?= e($inv['customer_name'] ?: '—') ?></span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">SĐT</span><span><?= e($inv['customer_phone'] ?: '—') ?></span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Email</span><span><?= e($inv['customer_email'] ?: '—') ?></span></div>
                <div class="d-flex justify-content-between py-1 gap-2"><span class="text-muted text-nowrap">Địa chỉ</span><span class="text-end"><?= e($inv['address'] ?: '—') ?></span></div>
            </div>
        </div>
        <div class="admin-card">
            <div class="card-head">Chứng từ</div>
            <div class="card-body small">
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Số hóa đơn</span><span class="fw-semibold"><?= e($inv['invoice_number'] ?? '') ?></span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Ngày</span><span><?= e(format_date($inv['invoice_date'] ?? '')) ?></span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Đơn hàng</span>
                    <?php if (!empty($order['order_code'])): ?>
                        <a href="<?= BASE_URL ?>/quan-tri/don-hang/xem/<?= urlencode($order['order_code']) ?>"><?= e($order['order_code']) ?></a>
                    <?php else: ?><span>—</span><?php endif; ?>
                </div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Loại</span><span><?= e($typeLabel[$inv['type']] ?? '') ?></span></div>
                <?php if (!empty($inv['note'])): ?>
                <div class="d-flex justify-content-between py-1 gap-2"><span class="text-muted text-nowrap">Ghi chú</span><span class="text-end"><?= e($inv['note']) ?></span></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="admin-card mb-3">
            <div class="card-head">Sản phẩm</div>
            <div class="card-body p-0 table-responsive">
                <table class="table admin-table mb-0">
                    <thead><tr><th>Sản phẩm</th><th class="text-center">SL</th><th class="money">Đơn giá</th><th class="money">Thành tiền</th></tr></thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="4"><div class="empty-state"><?= icon('bi-box-seam') ?>Không có dòng</div></td></tr>
                        <?php else: foreach ($items as $it): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= e($it['product_name']) ?></div>
                                    <?php if (!empty($it['variant_value'])): ?>
                                        <div class="small text-muted"><?= e($it['variant_name'] ?? '') ?>: <?= e($it['variant_value']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= (int)$it['quantity'] ?><?= $isRefund ? '' : '' ?></td>
                                <td class="money"><?= $fmt($it['unit_price']) ?></td>
                                <td class="money"><?= $sign ?: '' ?><?= $fmt($it['line_total']) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-card">
            <div class="card-head">Số tiền</div>
            <div class="card-body">
                <table class="table admin-table mb-0 wc-compact">
                    <tbody>
                        <tr>
                            <td class="text-muted">Tạm tính (chưa thuế)</td>
                            <td class="money fw-semibold"><?= $sign ?: '' ?><?= $fmt($inv['subtotal'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Chiết khấu</td>
                            <td class="money text-danger"><?= $sign ? '-' : '-–' ?><?= $fmt($inv['discount'] ?? 0) ?></td>
                        </tr>
                        <tr class="table-light">
                            <td class="fw-bold">Số tiền chịu thuế <span class="text-muted small fw-normal">(= Tạm tính − Chiết khấu)</span></td>
                            <td class="money fw-bold"><?= $sign ?: '' ?><?= $fmt($inv['taxable'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Thuế GTGT (<?= rtrim(rtrim(number_format((float)($inv['vat_rate'] ?? 0), 2, '.', ''), '0'), '.') ?>%)</td>
                            <td class="money"><?= $sign ?: '' ?><?= $fmt($inv['vat'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Phí vận chuyển</td>
                            <td class="money"><?= $sign ?: '' ?><?= $fmt($inv['shipping_fee'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">VAT phí vận chuyển (đã gồm trong giá)</td>
                            <td class="money"><?= $sign ?: '' ?><?= $fmt($inv['vat_shipping_amount'] ?? 0) ?></td>
                        </tr>
                        <?php if ((float)($inv['install_fee'] ?? 0) != 0): ?>
                        <tr>
                            <td class="text-muted">Phí lắp đặt</td>
                            <td class="money"><?= $sign ?: '' ?><?= $fmt($inv['install_fee'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">VAT phí lắp đặt (đã gồm trong giá)</td>
                            <td class="money"><?= $sign ?: '' ?><?= $fmt($inv['vat_install_amount'] ?? 0) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="table-dark">
                            <td class="fw-bold fs-5">TỔNG CỘNG</td>
                            <td class="money fw-bold fs-5 text-end"><?= $sign ?: '' ?><?= $fmt($inv['grand_total'] ?? 0) ?></td>
                        </tr>
                    </tbody>
                </table>
                <div class="small text-muted mt-2"><?= icon('bi-shield-lock', 'me-1') ?>Chứng từ cố định, không thể xóa.</div>
            </div>
        </div>
    </div>
</div>
