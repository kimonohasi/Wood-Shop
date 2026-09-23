<?php
/** Quản lý phiếu bảo hành */
declare(strict_types=1);
$__whStatus = ['active' => ['Còn bảo hành', 'success'], 'expired' => ['Hết hạn', 'secondary'], 'used' => ['Đã sử dụng', 'warning'], 'rejected' => ['Từ chối', 'danger']];
$__wStats = $stats ?? ['total' => 0, 'active' => 0, 'expired' => 0, 'used' => 0, 'rejected' => 0, 'expiring' => 0];
$__wStatusKey = ['Còn bảo hành' => 'active', 'Hết hạn' => 'expired', 'Đã sử dụng' => 'used', 'Từ chối' => 'rejected'];
$__wDotColor = [
    'success'   => 'var(--badge-active-text)',
    'warning'   => 'var(--badge-default-text)',
    'secondary' => 'var(--badge-inactive-text)',
    'danger'    => 'var(--badge-inactive-text)',
];
?>
<style>
    .wh-modal { background: var(--bg-surface); border: 1px solid var(--border-default); border-radius: var(--radius-md); box-shadow: none; }
    .wh-modal .wh-history-box { padding: .6rem 0; margin: 0; border-top: 1px solid var(--border-default); border-bottom: 1px solid var(--border-default); }
    .wh-modal .wh-timeline { max-height: 300px; overflow-y: auto; padding-right: 2px; margin: 0; }
    .wh-modal .wh-entry { position: relative; padding-left: 16px; }
    .wh-modal .wh-entry::before { content: ''; position: absolute; left: 3.5px; top: 0; bottom: 0; width: 1px; background: var(--border-default); }
    .wh-modal .wh-entry:first-child::before { top: 13px; }
    .wh-modal .wh-entry:last-child::before { bottom: auto; height: 15px; }
    .wh-modal .wh-entry:first-child:last-child::before { display: none; }
    .wh-modal .wh-entry + .wh-entry { padding-top: 8px; }
    .wh-modal .wh-dot { position: absolute; left: 0; top: 4px; width: 8px; height: 8px; border-radius: 50%; z-index: 1; }
    .wh-modal .wh-entry-text { font-size: var(--text-sm); line-height: 1.45; color: var(--text-primary); word-break: break-word; }
    .wh-modal .wh-entry-time { font-size: var(--text-xs); color: var(--text-muted); margin-top: 1px; }
    .wh-modal .wh-empty { font-size: var(--text-sm); color: var(--text-muted); padding: .15rem 0; }
</style>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0">Bảo hành</h1>
    </div>
    <?php if (\WoodCon\Permission::allows('warranties', 'export')): ?>
        <?php $__fb = http_build_query(array_filter(['q' => $q ?? ''])); ?>
        <div class="d-flex align-items-center gap-2">
            <div class="dropdown">
                <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"><?= icon('bi-download', 'me-1') ?>Xuất dữ liệu</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/quan-tri/bao-hanh/xuat?<?= e($__fb) ?>&export=csv"><?= icon('bi-filetype-csv', 'me-1') ?>CSV</a></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/quan-tri/bao-hanh/xuat?<?= e($__fb) ?>&export=xlsx"><?= icon('bi-file-earmark-excel', 'me-1') ?>Excel (.xlsx)</a></li>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ===== STAT CARDS ===== -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-4">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Tổng phiếu</div>
                    <div class="stat-value mt-1"><?= number_format($__wStats['total']) ?></div>
                </div>
                <?= icon('bi-tools', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Còn bảo hành</div>
                    <div class="stat-value mt-1 text-success"><?= number_format($__wStats['active']) ?></div>
                </div>
                <?= icon('bi-check-circle', 'stat-icon', 'style="color:var(--wc-success)"') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Hết hạn</div>
                    <div class="stat-value mt-1"><?= number_format($__wStats['expired']) ?></div>
                </div>
                <?= icon('bi-hourglass-bottom', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Đã sử dụng</div>
                    <div class="stat-value mt-1 text-warning"><?= number_format($__wStats['used']) ?></div>
                </div>
                <?= icon('bi-tools', 'stat-icon', 'style="color:var(--wc-warning)"') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Từ chối</div>
                    <div class="stat-value mt-1 text-danger"><?= number_format($__wStats['rejected']) ?></div>
                </div>
                <?= icon('bi-x-octagon', 'stat-icon', 'style="color:var(--wc-danger)"') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Sắp hết bảo hành</div>
                    <div class="stat-value mt-1 text-warning"><?= number_format($__wStats['expiring']) ?></div>
                </div>
                <?= icon('bi-exclamation-triangle', 'stat-icon', 'style="color:var(--wc-warning)"') ?>
            </div>
        </div>
    </div>
</div>

<form method="get" class="row g-2 mb-3" style="max-width:520px">
    <div class="col">
        <input type="text" class="form-control" name="q" value="<?= e($q) ?>" placeholder="Serial, mã đơn, SĐT, tên sản phẩm...">
    </div>
    <div class="col-auto">
        <button class="btn btn-primary">Tìm kiếm</button>
    </div>
</form>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr><th>Serial</th><th>Sản phẩm</th><th>Khách hàng</th><th>Đơn</th><th>Ngày mua</th><th>Hạn bảo hành</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8"><div class="empty-state"><?= icon('bi-tools') ?>Không có phiếu bảo hành nào</div></td></tr>
                <?php else: foreach ($rows as $__w): ?>
                    <?php $__st = $__whStatus[$__w['status']] ?? ['Khác', 'secondary']; ?>
                    <tr>
                        <td data-label="Serial" class="fw-semibold text-nowrap"><?= e($__w['serial_no']) ?></td>
                        <td data-label="Sản phẩm" class="small"><?= e($__w['product_name']) ?></td>
                        <td data-label="Khách hàng" class="small">
                            <div class="fw-semibold"><?= e($__w['customer_name']) ?></div>
                            <div class="text-muted"><?= e($__w['customer_phone'] ?? '') ?></div>
                        </td>
                        <td data-label="Đơn" class="text-nowrap small"><?= e($__w['order_code']) ?></td>
                        <td data-label="Ngày mua" class="text-nowrap small"><?= format_date($__w['purchase_date'] . ' 00:00:00') ?></td>
                        <td data-label="Hạn bảo hành" class="text-nowrap small"><?= format_date($__w['warranty_end'] . ' 00:00:00') ?></td>
                        <td data-label="Trạng thái"><span class="badge text-bg-<?= $__st[1] ?>"><?= $__st[0] ?></span></td>
                        <td data-label="Thao tác" class="text-end text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-secondary" title="Ghi sổ / đổi trạng thái" aria-label="Ghi sổ / đổi trạng thái" data-bs-toggle="modal" data-bs-target="#whModal-<?= (int)$__w['id'] ?>" style="width:34px;height:34px;padding:0;display:inline-flex;align-items:center;justify-content:center">
                                <?= icon('bi-journal-text', 'style="font-size:20px;line-height:1;color:var(--text-primary)"') ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($rows)): foreach ($rows as $__w): ?>
<div class="modal fade" id="whModal-<?= (int)$__w['id'] ?>" tabindex="-1" aria-labelledby="whModalLabel-<?= (int)$__w['id'] ?>" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content wh-modal">
            <div class="modal-header border-0 px-3 pt-3 pb-0">
                <h5 class="modal-title fs-6" id="whModalLabel-<?= (int)$__w['id'] ?>">
                    <span class="text-nowrap fw-semibold"><?= e($__w['serial_no']) ?></span>
                    <span class="d-block small text-muted fw-normal mt-1"><?= e($__w['product_name']) ?><?= $__w['variant_value'] ? ' - ' . e($__w['variant_value']) : '' ?></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body pt-3 px-3 pb-2 d-grid gap-3">
                <div class="wh-history-box">
                    <?php if (empty($__w['history'])): ?>
                        <div class="wh-empty">Chưa có ghi chú nào</div>
                    <?php else: ?>
                        <div class="wh-timeline">
                            <?php foreach ($__w['history'] as $__h): ?>
                                <?php $__hDot = 'var(--text-primary)';
                                if (str_starts_with($__h['description'], 'Đổi trạng thái: ')) {
                                    $__hPos = mb_strrpos($__h['description'], '→', 0, 'UTF-8');
                                    if ($__hPos !== false) {
                                        $__hKey = $__wStatusKey[trim(mb_substr($__h['description'], $__hPos + 1, null, 'UTF-8'))] ?? null;
                                        $__hDot = $__wDotColor[$__whStatus[$__hKey][1] ?? ''] ?? 'var(--text-primary)';
                                    }
                                } ?>
                                <div class="wh-entry">
                                    <span class="wh-dot" style="background:<?= $__hDot ?>"></span>
                                    <div class="wh-entry-body">
                                        <div class="wh-entry-text"><?= e($__h['description']) ?></div>
                                        <div class="wh-entry-time"><?= date('d/m/Y H:i', strtotime($__h['created_at'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <form id="whForm-<?= (int)$__w['id'] ?>" method="post" action="<?= BASE_URL ?>/quan-tri/bao-hanh/log/<?= (int)$__w['id'] ?>" class="d-grid gap-2">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <select name="status" class="form-select form-select-sm">
                        <?php foreach ($__whStatus as $__sk => $__sv): ?>
                            <option value="<?= $__sk ?>" <?= $__w['status'] === $__sk ? 'selected' : '' ?>><?= $__sv[0] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <textarea name="note" class="form-control form-control-sm" rows="2" placeholder="Nội dung (VD: tiếp nhận sửa bàn phím, thay bản lề...)" required></textarea>
                </form>
            </div>
            <div class="modal-footer border-0 px-3 pb-3 pt-0">
                <button type="submit" form="whForm-<?= (int)$__w['id'] ?>" class="btn btn-primary btn-sm"><?= icon('bi-check2', 'me-1') ?>Lưu</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; endif; ?>

<?php require BASE_PATH . '/includes/partials/pagination.php'; ?>