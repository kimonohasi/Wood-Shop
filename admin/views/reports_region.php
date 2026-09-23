<?php
/** Region nội dung Báo cáo theo mode - dùng cho trang (reports.php) và AJAX (AdminController::reports frag=1). */
declare(strict_types=1);
if (!isset($r) || !is_array($r)) {
    return;
}
$periodLabel = static function (string $p): string {
    if (preg_match('/^(\d{4})-Q([1-4])$/', $p, $m)) {
        return 'Quý ' . $m[2] . '/' . $m[1];
    }
    if (preg_match('/^\d{4}$/', $p)) {
        return 'Năm ' . $p;
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $p, $m)) {
        $dt = strtotime($p);
        return date('d/m/Y', $dt) . ' (Tuần ' . date('W', $dt) . '/' . $m[1] . ')';
    }
    if (preg_match('/^(\d{4})-(\d{2})$/', $p, $m)) {
        return 'Tháng ' . (int)$m[2] . '/' . $m[1];
    }
    return $p;
};
$__rmode = $rmode ?? ($r['mode'] ?? '1');
?>
<?php if ($__rmode === '2'): ?>
<?php if (!empty($r['legal']['summary'])): $ls = $r['legal']['summary']; ?>
<div class="row g-3 mb-3">
    <div class="col-6 col-md-4 col-lg-2"><div class="admin-stat"><div class="stat-label">Số hóa đơn bán</div><div class="h5 mb-0 mt-1"><?= icon('bi-receipt', 'me-1 text-muted') ?><?= (int)$ls['num_invoices'] ?></div></div></div>
    <div class="col-6 col-md-4 col-lg-2"><div class="admin-stat"><div class="stat-label">Doanh thu chịu thuế</div><div class="h5 mb-0 mt-1 text-success"><?= format_money((float)$ls['sale_subtotal']) ?></div></div></div>
    <div class="col-6 col-md-4 col-lg-2"><div class="admin-stat"><div class="stat-label">VAT bán</div><div class="h5 mb-0 mt-1 text-success"><?= format_money((float)$ls['sale_vat']) ?></div></div></div>
    <div class="col-6 col-md-4 col-lg-2"><div class="admin-stat"><div class="stat-label">Trả hàng</div><div class="h5 mb-0 mt-1 text-danger"><?= format_money((float)$ls['refund_subtotal']) ?></div></div></div>
    <div class="col-6 col-md-4 col-lg-2"><div class="admin-stat"><div class="stat-label">VAT trả hàng</div><div class="h5 mb-0 mt-1 text-danger"><?= format_money((float)$ls['refund_vat']) ?></div></div></div>
    <div class="col-6 col-md-4 col-lg-2"><div class="admin-stat"><div class="stat-label">Số chịu thuế ròng</div><div class="h5 mb-0 mt-1 fw-bold"><?= format_money((float)$ls['taxable']) ?></div></div></div>
</div>

<div class="admin-card">
    <div class="card-body p-0 table-responsive">
        <table class="table admin-table mb-0">
            <thead><tr><th>Kỳ</th><th class="text-center">Số hóa đơn</th><th class="money">Bán (chưa thuế)</th><th class="money">VAT bán</th><th class="money">Trả hàng (chưa thuế)</th><th class="money">VAT trả hàng</th><th class="money">Số chịu thuế ròng</th><th class="money">VAT phải nộp</th></tr></thead>
            <tbody>
                <?php if (empty($r['legal']['rows'])): ?>
                    <tr><td colspan="8"><div class="empty-state"><?= icon('bi-receipt') ?>Chưa có hóa đơn trong khoảng này</div></td></tr>
                <?php else: foreach ($r['legal']['rows'] as $__row): ?>
                    <tr>
                        <td data-label="Kỳ" class="fw-semibold"><?= e($periodLabel($__row['period'])) ?></td>
                        <td data-label="Số hóa đơn" class="text-center"><?= (int)$__row['num_invoices'] ?></td>
                        <td data-label="Bán (chưa thuế)" class="money text-success"><?= format_money((float)$__row['sale_subtotal']) ?></td>
                        <td data-label="VAT bán" class="money text-success"><?= format_money((float)$__row['sale_vat']) ?></td>
                        <td data-label="Trả hàng (chưa thuế)" class="money text-danger"><?= format_money((float)$__row['refund_subtotal']) ?></td>
                        <td data-label="VAT trả hàng" class="money text-danger"><?= format_money((float)$__row['refund_vat']) ?></td>
                        <td data-label="Số chịu thuế ròng" class="money fw-bold"><?= format_money((float)$__row['taxable']) ?></td>
                        <td data-label="VAT phải nộp" class="money fw-bold"><?= format_money((float)$__row['vat']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<div class="row g-3 mb-3">
    <div class="col-6 col-md-2"><div class="admin-stat"><div class="stat-label">Tổng đơn</div><div class="h5 mb-0 mt-1"><?= number_format((int)$r['summary']['orders']) ?></div></div></div>
    <div class="col-6 col-md-2"><div class="admin-stat"><div class="stat-label">Doanh thu</div><div class="h5 mb-0 mt-1 text-success"><?= format_money((float)$r['summary']['revenue']) ?></div></div></div>
    <div class="col-6 col-md-2"><div class="admin-stat"><div class="stat-label">Giá trị hàng</div><div class="h5 mb-0 mt-1"><?= format_money((float)$r['summary']['goods']) ?></div></div></div>
    <div class="col-6 col-md-2"><div class="admin-stat"><div class="stat-label">Giảm giá</div><div class="h5 mb-0 mt-1 text-danger">-<?= format_money((float)$r['summary']['discount']) ?></div></div></div>
    <div class="col-6 col-md-2"><div class="admin-stat"><div class="stat-label">Phí ship</div><div class="h5 mb-0 mt-1"><?= format_money((float)$r['summary']['shipping']) ?></div></div></div>
    <div class="col-6 col-md-2"><div class="admin-stat"><div class="stat-label">VAT</div><div class="h5 mb-0 mt-1"><?= format_money((float)$r['summary']['vat']) ?></div></div></div>
</div>

<div class="admin-card">
    <div class="card-body p-0 table-responsive">
        <table class="table admin-table mb-0">
            <thead><tr><th>Kỳ</th><th class="text-center">Đơn</th><th class="money">Doanh thu</th><th class="money">Giá trị hàng</th><th class="money">Giảm giá</th><th class="money">Phí ship</th><th class="money">VAT</th></tr></thead>
            <tbody>
                <?php if (empty($r['rows'])): ?>
                    <tr><td colspan="7"><div class="empty-state"><?= icon('bi-graph-up') ?>Không có dữ liệu trong khoảng này</div></td></tr>
                <?php else: foreach ($r['rows'] as $__rows): ?>
                    <tr>
                        <td data-label="Kỳ" class="fw-semibold"><?= e($periodLabel($__rows['period'])) ?></td>
                        <td data-label="Đơn" class="text-center"><?= (int)$__rows['orders'] ?></td>
                        <td data-label="Doanh thu" class="money text-success"><?= format_money((float)$__rows['revenue']) ?></td>
                        <td data-label="Giá trị hàng" class="money"><?= format_money((float)$__rows['goods_value']) ?></td>
                        <td data-label="Giảm giá" class="money"><?= format_money((float)$__rows['discount']) ?></td>
                        <td data-label="Phí ship" class="money"><?= format_money((float)$__rows['shipping']) ?></td>
                        <td data-label="VAT" class="money"><?= format_money((float)$__rows['vat']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>