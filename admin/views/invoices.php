<?php
/** Danh sách Hóa đơn (chứng từ kế toán—immutable, không có Xóa) */
declare(strict_types=1);
$rows  = $rows ?? [];
$pager = $pager ?? [];
$f     = $f ?? [];
$typeLabel = \WoodCon\Invoice::TYPE_LABEL;
$money = fn($v, bool $neg = false) => format_money(abs((float)$v));
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0">Hóa đơn</h1>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= BASE_URL ?>/quan-tri/bao-cao" class="btn btn-outline-primary btn-sm"><?= icon('bi-bar-chart-line', 'me-1') ?>Báo cáo thuế</a>
        <?php if (\WoodCon\Permission::allows('invoices', 'export')): ?>
            <?php $__fb = http_build_query(array_filter(['q' => $f['q'] ?? '', 'type' => $f['type'] ?? '', 'from' => $f['from'] ?? '', 'to' => $f['to'] ?? ''])); ?>
            <div class="dropdown">
                <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"><?= icon('bi-download', 'me-1') ?>Xuất dữ liệu</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/quan-tri/hoa-don/xuat?<?= e($__fb) ?>&export=csv"><?= icon('bi-filetype-csv', 'me-1') ?>CSV</a></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/quan-tri/hoa-don/xuat?<?= e($__fb) ?>&export=xlsx"><?= icon('bi-file-earmark-excel', 'me-1') ?>Excel (.xlsx)</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===== STAT CARDS ===== -->
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Tổng hóa đơn</div>
                    <div class="stat-value mt-1"><?= (int)($stats['total'] ?? 0) ?></div>
                </div>
                <?= icon('bi-file-earmark-text', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Hóa đơn bán</div>
                    <div class="stat-value mt-1 text-success"><?= (int)($stats['sale'] ?? 0) ?></div>
                </div>
                <?= icon('bi-receipt', 'stat-icon text-success') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Hóa đơn điều chỉnh</div>
                    <div class="stat-value mt-1 text-warning"><?= (int)($stats['adjust'] ?? 0) ?></div>
                </div>
                <?= icon('bi-arrow-left-right', 'stat-icon text-warning') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Hóa đơn hủy</div>
                    <div class="stat-value mt-1 text-danger"><?= (int)($stats['cancelled'] ?? 0) ?></div>
                </div>
                <?= icon('bi-x-circle', 'stat-icon text-danger') ?>
            </div>
        </div>
    </div>
</div>

<div class="admin-card mb-3">
    <div class="card-body">
        <form class="row g-2" method="get">
            <input type="hidden" name="route" value="quan-tri/hoa-don">
            <div class="col-md-3">
                <input type="search" class="form-control form-control-sm" name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="Số HĐ / khách / SĐT...">
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="type">
                    <option value="">Tất cả loại</option>
                    <option value="SALE_INVOICE" <?= ($f['type'] ?? '') === 'SALE_INVOICE' ? 'selected' : '' ?>>Hóa đơn bán</option>
                    <option value="REFUND_INVOICE" <?= ($f['type'] ?? '') === 'REFUND_INVOICE' ? 'selected' : '' ?>>Điều chỉnh / Hoàn tiền</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control form-control-sm" name="from" value="<?= e($f['from'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control form-control-sm" name="to" value="<?= e($f['to'] ?? '') ?>">
            </div>
            <div class="col-md-3 d-grid align-items-end"><button class="btn btn-outline-primary btn-sm">Lọc</button></div>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="card-body p-0 table-responsive">
        <table class="table admin-table mb-0">
            <thead><tr>
                <th>Số hóa đơn</th><th>Loại</th><th>Khách hàng</th><th class="text-center">Ngày HĐ</th>
                <th class="money">Chịu thuế</th><th class="money">VAT</th><th class="money">Tổng cộng</th>
                <th class="text-end">Thao tác</th>
            </tr></thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8"><div class="empty-state"><?= icon('bi-receipt') ?>Chưa có hóa đơn nào</div></td></tr>
                <?php else: foreach ($rows as $r): $isRefund = $r['type'] === 'REFUND_INVOICE'; ?>
                    <tr>
                        <td data-label="Số hóa đơn" class="fw-semibold text-nowrap">
                            <a href="<?= BASE_URL ?>/quan-tri/hoa-don/xem/<?= urlencode($r['invoice_number']) ?>"><?= e($r['invoice_number']) ?></a>
                        </td>
                        <td data-label="Loại">
                            <?php if ($isRefund): ?>
                                <span class="badge text-bg-danger"><?= e($typeLabel[$r['type']] ?? $r['type']) ?></span>
                            <?php else: ?>
                                <span class="badge text-bg-success"><?= e($typeLabel[$r['type']] ?? $r['type']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Khách hàng">
                            <div class="fw-semibold"><?= e($r['customer_name'] ?: '—') ?></div>
                            <div class="small text-muted"><?= e($r['customer_phone'] ?: '') ?></div>
                        </td>
                        <td data-label="Ngày HĐ" class="text-center"><?= e(format_date($r['invoice_date'], 'd/m/Y')) ?></td>
                        <td data-label="Chịu thuế" class="money <?= $isRefund ? 'text-danger' : '' ?>"><?= $money($r['taxable']) ?></td>
                        <td data-label="VAT" class="money <?= $isRefund ? 'text-danger' : '' ?>"><?= $money($r['vat']) ?></td>
                        <td data-label="Tổng cộng" class="money fw-bold <?= $isRefund ? 'text-danger' : '' ?>"><?= $money($r['grand_total']) ?></td>
                        <td data-label="Thao tác" class="text-end text-nowrap">
                            <a href="<?= BASE_URL ?>/quan-tri/hoa-don/xem/<?= urlencode($r['invoice_number']) ?>" class="btn btn-sm btn-outline-primary" title="Xem chi tiết"><?= icon('bi-eye') ?></a>
                            <a href="<?= BASE_URL ?>/quan-tri/hoa-don/in/<?= urlencode($r['invoice_number']) ?>?type=a4" target="_blank" class="btn btn-sm btn-outline-secondary" title="In"><?= icon('bi-printer') ?></a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($pager['total_pages'] ?? 0) && (int)($pager['total_pages'] ?? 1) > 1): ?>
        <div class="card-body py-2 border-top"><?php include BASE_PATH . '/includes/partials/pagination.php'; ?></div>
    <?php endif; ?>
</div>
