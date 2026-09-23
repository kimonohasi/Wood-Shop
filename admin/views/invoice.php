<?php
/**
 * WoodCon Admin - Trang in hóa đơn
 * Hai định dạng: POS 80mm (máy in nhiệt) và A5/A4 (máy in thường).
 * $type = 'pos' | 'a4'
 */
declare(strict_types=1);
$type = ($type ?? 'a4') === 'pos' ? 'pos' : 'a4';
$cfg   = $invoiceCfg ?? [];
$order = $order ?? [];
$items = $items ?? [];
$inv   = $invoice ?? [];   // bản ghi invoices (chứng từ chuẩn) — nếu có ưu tiên dùng

// Nguồn dữ liệu: bản ghi invoices (hợp lệ) hoặc fallback về $order (luồng in cũ)
$isRefund = ($inv['type'] ?? '') === 'REFUND_INVOICE';
$isInvoiceRec = !empty($inv);

$invNo   = $isInvoiceRec ? ($inv['invoice_number'] ?? '') : ($invoiceNumber ?? '');
$invDate = $isInvoiceRec ? (($inv['invoice_date'] ?? '') ? date('d/m/Y H:i', strtotime($inv['invoice_date'])) : '') : ($invoiceDate ?? date('d/m/Y H:i'));

// Số tiền theo công thức hóa đơn VAT-exclusive
if ($isInvoiceRec) {
    $moneySubtotal = (float)$inv['subtotal'] ?? 0;
    $moneyDiscount = (float)$inv['discount'] ?? 0;
    $moneyTier     = (float)($inv['tier_discount_amount'] ?? 0);
    $moneyTierPct  = (float)($inv['tier_discount_percent'] ?? 0);
    $moneyTaxable  = (float)$inv['taxable'] ?? 0;
    $moneyVatRate  = (float)$inv['vat_rate'] ?? 0;
    $moneyVat      = (float)$inv['vat'] ?? 0;
    $moneyShip     = (float)$inv['shipping_fee'] ?? 0;
    $moneyInstall  = (float)($inv['install_fee'] ?? 0);
    $moneyVatShip  = (float)($inv['vat_shipping_amount'] ?? 0);
    $moneyVatInstall = (float)($inv['vat_install_amount'] ?? 0);
    $moneyGrand    = (float)$inv['grand_total'] ?? 0;
    $customerName  = $inv['customer_name'] ?? '';
    $customerPhone = $inv['customer_phone'] ?? '';
    $customerEmail = $inv['customer_email'] ?? '';
    $customerAddr  = $inv['address'] ?? '';
} else {
    $moneySubtotal = (float)($order['subtotal'] ?? 0);
    $moneyDiscount = (float)($order['discount_amount'] ?? 0) + (float)($order['freeship_discount'] ?? 0);
    $moneyTier     = max(0, (float)($order['tier_discount_amount'] ?? 0));
    $moneyTierPct  = max(0, (float)($order['tier_discount_percent'] ?? 0));
    $moneyTaxable  = $moneySubtotal - $moneyDiscount - $moneyTier;
    $moneyVatRate  = (float)($order['vat_rate'] ?? 0);
    $moneyVat      = (float)($order['vat_amount'] ?? 0);
    $moneyShip     = (float)($order['shipping_fee'] ?? 0);
    $moneyInstall  = max(0, (float)($order['install_fee'] ?? 0));
    $__fsFree      = max(0, (float)($order['freeship_discount'] ?? 0));
    $__fsNetShip   = max(0, $moneyShip - $__fsFree);
    // VAT phí dịch vụ: mức thuế đọc từ cấu hình ĐỘC LẬP (mặc định 5%), không lệ thuộc thuế sản phẩm.
    $__fsTaxShip  = max(0, \WoodCon\TaxRate::shippingRate());
    $__fsTaxInstall = max(0, \WoodCon\TaxRate::installRate());
    $moneyVatShip  = (float)($order['vat_shipping_amount'] ?? 0) > 0
        ? (float)$order['vat_shipping_amount']
        : ($__fsTaxShip > 0 ? ($__fsNetShip - ($__fsNetShip / (1 + $__fsTaxShip / 100))) : 0.0);
    $moneyVatInstall = (float)($order['vat_install_amount'] ?? 0) > 0
        ? (float)$order['vat_install_amount']
        : ($__fsTaxInstall > 0 && $moneyInstall > 0 ? ($moneyInstall - ($moneyInstall / (1 + $__fsTaxInstall / 100))) : 0.0);
    $moneyGrand    = (float)($order['total_amount'] ?? 0);
    $customerName  = $order['customer_name'] ?? '';
    $customerPhone = $order['customer_phone'] ?? '';
    $customerEmail = $order['customer_email'] ?? '';
    $customerAddr  = $order['address'] ?? '';
}

// Bình thường hóa dòng sản phẩm (hỗ trợ cả snapshot invoice_items và order_items)
$__normItems = [];
foreach ($items as $__it) {
    $__qty = (int)($__it['quantity'] ?? 1);
    $__price = (float)($__it['unit_price'] ?? $__it['price'] ?? 0);
    $__line = (float)($__it['line_total'] ?? $__it['subtotal'] ?? ($__price * $__qty));
    $__normItems[] = [
        'name' => $__it['product_name'] ?? '',
        'variant_name' => $__it['variant_name'] ?? '',
        'variant_value' => $__it['variant_value'] ?? '',
        'quantity' => $__qty,
        'price' => $__price,
        'line' => $__line,
    ];
}
$items = $__normItems;

$payLabels = [
    'cod'  => 'Tiền mặt (COD)',
    'bank' => 'Chuyển khoản',
    'qr'   => 'Quét mã QR',
    'wallet'=> 'Ví điện tử',
];
function __inv_money($v): string { return number_format((float)$v, 0, ',', '.') . ' đ'; }
function __inv_rate($v): string {
    $n = (float)$v;
    if ($n <= 0) return '0';
    return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Hóa đơn <?= e($order['order_code'] ?? '') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        /* Dùng font có đầy đủ glyph tiếng Việt; fallback qua các font hệ thống phổ biến (Windows/macOS/Linux máy in nhiệt) */
        body { font-family: 'Manrope', 'Work Sans', 'DejaVu Sans', 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
               color: #000; margin: 0; padding: 24px; background: #eee; }
        .sheet { background: #fff; margin: 0 auto; padding: 24px; }
        .no-print-btn { display:inline-flex; align-items:center; gap:8px; border:0; padding:11px 20px;
                    border-radius:8px; font-size:15px; font-weight:600; cursor:pointer; text-decoration:none; }
        .no-print-btn.is-nav { background:#a0714f; color:#fff; }
        .no-print-btn.is-nav:hover { background:#8a5f3f; }
        .no-print-btn.is-nav .no-print-icon { font-size:18px; line-height:1; }
        .no-print-btn.is-print { background:#fff; color:#333; border:1px solid #ccc; }
        .no-print-btn.is-print:hover { background:#f2f2f2; }
        .brand { display: flex; align-items: center; gap: 12px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .brand-mark { background: #7a4a2b; color: #fff; font-weight: 800; font-size: 22px; width: 44px; height: 44px;
                      display: flex; align-items: center; justify-content: center; border-radius: 8px; }
        .brand-name { font-size: 22px; font-weight: 800; line-height: 1.05; }
        .brand-tax { font-size: 11px; color: #444; }
        .title { text-align: center; margin: 16px 0 4px; }
        .title h1 { font-size: 20px; margin: 0; letter-spacing: 1px; text-transform: uppercase; }
        .title .sub { font-size: 11px; color: #555; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 14px 0; font-size: 13px; }
        .meta .k { color: #555; }
        table.items { width: 100%; border-collapse: collapse; font-size: 13px; }
        table.items th { background: #f4f4f4; text-align: left; padding: 6px; border-bottom: 2px solid #000; }
        table.items td { padding: 6px; border-bottom: 1px solid #ddd; vertical-align: top; }
        table.items .r { text-align: right; white-space: nowrap; }
        table.items .c { text-align: center; }
        .totals { width: 100%; margin-top: 10px; font-size: 13px; }
        .totals td { padding: 3px 6px; }
        .totals .r { text-align: right; }
        .totals .grand td { font-size: 16px; font-weight: 800; border-top: 2px solid #000; padding-top: 6px; }
        .footer { margin-top: 16px; text-align: center; font-size: 12px; color: #333; }
        .legal { font-size: 10px; color: #666; margin-top: 10px; border-top: 1px dashed #999; padding-top: 8px; }
        .addr { margin-top: 14px; font-size: 12px; border-top: 1px dashed #999; padding-top: 8px; color: #555; }
        button.print-btn { position: fixed; top: 16px; right: 16px; }

        /* POS 80mm */
        .pos { width: 80mm; max-width: 80mm; padding: 8px 6px; }
        .pos .brand { flex-direction: column; gap: 4px; border-bottom: 1px dashed #000; text-align: center; }
        .pos .brand-name { font-size: 16px; }
        .pos .title h1 { font-size: 14px; }
        .pos table.items, .pos .totals, .pos .meta { font-size: 11px; }
        .pos .meta { grid-template-columns: 1fr; gap: 2px; }
        .pos table.items td, .pos table.items th { padding: 3px; }
        .pos .totals .grand td { font-size: 13px; }

        @media print {
            body { background: #fff; padding: 0; }
            .sheet { padding: 8px; box-shadow: none; }
            .no-print, .print-btn, .toolbar { display: none !important; }
        }
    </style>
</head>
<body class="<?= $type ?>-page">
    <div class="no-print" style="display:flex;gap:10px;align-items:center;margin-bottom:16px;">
        <a href="<?= e(BASE_URL . '/quan-tri/hoa-don') ?>" class="no-print-btn is-nav" title="Xem tất cả hóa đơn">
            <span class="no-print-icon">▤</span> Danh sách Hóa đơn
        </a>
        <button type="button" class="no-print-btn is-print" onclick="window.print()">🖨️ In hóa đơn</button>
    </div>

    <div class="sheet <?= $type === 'pos' ? 'pos' : 'a4' ?>">
        <?php if ($type === 'a4'): ?>
        <div class="brand">
            <div class="brand-mark">W</div>
            <div>
                <div class="brand-name"><?= e($cfg['invoice_company_name'] ?: 'WoodCon') ?></div>
                <?php if (!empty($cfg['invoice_tax_code'])): ?>
                    <div class="brand-tax">MST: <?= e($cfg['invoice_tax_code']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="brand">
            <div class="brand-name"><?= e($cfg['invoice_company_name'] ?: 'WoodCon') ?></div>
            <?php if (!empty($cfg['invoice_tax_code'])): ?>
                <div class="brand-tax">MST: <?= e($cfg['invoice_tax_code']) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="title">
            <h1><?= $isRefund ? 'HÓA ĐƠN ĐIỀU CHỈNH GIẢM' : 'HÓA ĐƠN BÁN HÀNG' ?></h1>
            <div class="sub"><?= e($invNo) ?> &nbsp;•&nbsp; <?= e($invDate) ?></div>
        </div>

        <div class="meta">
            <div><span class="k">Đơn hàng:</span> <strong><?= e($order['order_code'] ?? '') ?></strong></div>
            <div><span class="k">Thanh toán:</span> <?= e($payLabels[$order['payment_method'] ?? 'cod'] ?? $order['payment_method']) ?></div>
            <div><span class="k">Khách hàng:</span> <?= e($customerName) ?></div>
            <div><span class="k">SĐT:</span> <?= e($customerPhone) ?></div>
            <?php if (!empty($customerEmail)): ?>
            <div><span class="k">Email:</span> <?= e($customerEmail) ?></div>
            <?php endif; ?>
            <div style="grid-column:1/-1"><span class="k">Địa chỉ:</span> <?= e($customerAddr ?: ($order['address'] ?? '')) ?></div>
            <?php if (!empty($order['voucher_code'])): ?>
            <div><span class="k">Mã giảm giá:</span> <?= e($order['voucher_code']) ?></div>
            <?php endif; ?>
        </div>

        <table class="items">
            <thead><tr>
                <th>Sản phẩm</th><th class="c">SL</th><th class="r">Đơn giá</th><th class="r">Thành tiền</th>
            </tr></thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                <tr>
                    <td>
                        <?= e($it['name']) ?>
                        <?php foreach (['variant_name','variant_value'] as $__vk): if (!empty($it[$__vk])): ?>
                            <div style="font-size:10px;color:#666"><?= e($it[$__vk]) ?></div>
                        <?php endif; endforeach; ?>
                    </td>
                    <td class="c"><?= (int)$it['quantity'] ?></td>
                    <td class="r"><?= __inv_money($it['price']) ?></td>
                    <td class="r"><?= __inv_money($it['line']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="totals">
            <tr><td>Tạm tính (chưa thuế)</td><td class="r"><?= __inv_money($moneySubtotal) ?></td></tr>
            <?php if ((float)$moneyDiscount > 0): ?>
            <tr><td>Chiết khấu</td><td class="r">-<?= __inv_money($moneyDiscount) ?></td></tr>
            <?php endif; ?>
            <?php if ((float)$moneyTier > 0): ?>
            <tr><td>Giảm giá hạng thành viên<?= (float)$moneyTierPct > 0 ? ' (' . __inv_rate($moneyTierPct) . '%)' : '' ?></td><td class="r">-<?= __inv_money($moneyTier) ?></td></tr>
            <?php endif; ?>
            <tr><td>Số tiền chịu thuế</td><td class="r"><?= __inv_money($moneyTaxable) ?></td></tr>
            <tr><td>Thuế GTGT (<?= __inv_rate($moneyVatRate) ?>%)</td><td class="r"><?= __inv_money($moneyVat) ?></td></tr>
            <tr><td>Phí vận chuyển</td><td class="r"><?= __inv_money($moneyShip) ?></td></tr>
            <?php if ((float)$moneyVatShip > 0): ?>
            <tr><td>VAT phí vận chuyển (đã gồm trong giá)</td><td class="r"><?= __inv_money($moneyVatShip) ?></td></tr>
            <?php endif; ?>
            <?php if ((float)$moneyInstall > 0): ?>
            <tr><td>Phí lắp đặt</td><td class="r"><?= __inv_money($moneyInstall) ?></td></tr>
            <?php if ((float)$moneyVatInstall > 0): ?>
            <tr><td>VAT phí lắp đặt (đã gồm trong giá)</td><td class="r"><?= __inv_money($moneyVatInstall) ?></td></tr>
            <?php endif; ?>
            <?php endif; ?>
            <tr class="grand"><td>TỔNG CỘNG</td><td class="r"><?= __inv_money($moneyGrand) ?></td></tr>
        </table>

        <?php if (!empty($cfg['invoice_footer'])): ?>
        <div class="footer"><?= e($cfg['invoice_footer']) ?></div>
        <?php endif; ?>

        <?php if (!empty($cfg['invoice_legal_note'])): ?>
        <div class="legal"><?= e($cfg['invoice_legal_note']) ?></div>
        <?php endif; ?>

        <div class="addr">
            <?php if (!empty($cfg['invoice_address'])): ?>Địa chỉ: <?= e($cfg['invoice_address']) ?><br><?php endif; ?>
            <?php if (!empty($cfg['invoice_phone'])): ?>Điện thoại: <?= e($cfg['invoice_phone']) ?><?php endif; ?>
        </div>
    </div>
</body>
</html>
