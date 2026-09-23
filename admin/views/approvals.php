<?php
/** Kiểm duyệt yêu cầu hủy đơn + hoàn tiền */
declare(strict_types=1);
$__methodLabel = ['cod' => 'COD', 'bank' => 'Bank chuyển khoản', 'qr' => 'QR', 'wallet' => 'Ví'];
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0">Kiểm duyệt hủy / hoàn tiền</h1>
    </div>
    <?php if (\WoodCon\Permission::allows('approvals', 'export')): ?>
        <div class="d-flex align-items-center gap-2">
            <div class="dropdown">
                <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"><?= icon('bi-download', 'me-1') ?>Xuất dữ liệu</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/quan-tri/duyet/xuat?export=csv<?= $q !== '' ? '&q=' . rawurlencode($q) : '' ?>"><?= icon('bi-filetype-csv', 'me-1') ?>CSV</a></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/quan-tri/duyet/xuat?export=xlsx<?= $q !== '' ? '&q=' . rawurlencode($q) : '' ?>"><?= icon('bi-file-earmark-excel', 'me-1') ?>Excel (.xlsx)</a></li>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ===== STAT CARDS ===== -->
<div class="row g-3 mb-2">
    <div class="col-12">
        <div class="small text-muted fw-semibold text-uppercase mb-2" style="letter-spacing:.05em;font-size:.75rem;">Yêu cầu hủy đơn</div>
    </div>
    <div class="col-6 col-md-6 col-lg-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Tổng yêu cầu hủy</div>
                    <div class="stat-value mt-1"><?= number_format((int)($stats['cancels']['total'] ?? 0)) ?></div>
                </div>
                <?= icon('bi-x-circle', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Chờ duyệt</div>
                    <div class="stat-value mt-1"><?= number_format((int)($stats['cancels']['pending'] ?? 0)) ?></div>
                </div>
                <?= icon('bi-hourglass-split', 'stat-icon', 'style="color:var(--wc-warning)"') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Đã duyệt</div>
                    <div class="stat-value mt-1 text-success"><?= number_format((int)($stats['cancels']['approved'] ?? 0)) ?></div>
                </div>
                <?= icon('bi-check2-circle', 'stat-icon', 'style="color:var(--wc-success)"') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Từ chối</div>
                    <div class="stat-value mt-1 text-danger"><?= number_format((int)($stats['cancels']['rejected'] ?? 0)) ?></div>
                </div>
                <?= icon('bi-x-octagon', 'stat-icon', 'style="color:var(--wc-danger)"') ?>
            </div>
        </div>
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="small text-muted fw-semibold text-uppercase mt-4 mb-2" style="letter-spacing:.05em;font-size:.75rem;">Hoàn tiền</div>
    </div>
    <div class="col-6 col-md-6 col-lg-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Tổng hoàn tiền</div>
                    <div class="stat-value mt-1"><?= number_format((int)($stats['refunds']['total'] ?? 0)) ?></div>
                </div>
                <?= icon('bi-cash-stack', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Chờ hoàn tất</div>
                    <div class="stat-value mt-1"><?= number_format((int)($stats['refunds']['pending'] ?? 0)) ?></div>
                </div>
                <?= icon('bi-arrow-repeat', 'stat-icon', 'style="color:var(--wc-warning)"') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Đã hoàn tất</div>
                    <div class="stat-value mt-1 text-success"><?= number_format((int)($stats['refunds']['completed'] ?? 0)) ?></div>
                </div>
                <?= icon('bi-check2-circle', 'stat-icon', 'style="color:var(--wc-success)"') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Tiền chờ hoàn tất</div>
                    <div class="stat-value mt-1"><?= format_money((float)($stats['refunds']['pending_amount'] ?? 0)) ?></div>
                </div>
                <?= icon('bi-cash-coin', 'stat-icon') ?>
            </div>
        </div>
    </div>
</div>

<?php $__q = trim((string)($q ?? '')); ?>
<div class="admin-card mb-3">
    <form method="get" class="row g-2">
        <div class="col-md-5 col-lg-4">
            <input type="text" name="q" value="<?= e($__q) ?>" class="form-control form-control-sm" placeholder="Tìm theo mã đơn, khách hàng, SĐT, lý do...">
        </div>
        <div class="col-md-3 col-lg-2 d-grid">
            <button class="btn btn-outline-primary btn-sm"><?= icon('bi-search', 'me-1') ?>Tìm kiếm</button>
        </div>
        <?php if ($__q !== ''): ?>
            <div class="col-auto d-flex align-items-center">
                <a href="<?= BASE_URL ?>/quan-tri/duyet" class="text-danger text-decoration-none small"><?= icon('bi-x-circle', 'me-1') ?>Xóa lọc</a>
            </div>
        <?php endif; ?>
    </form>
</div>

<div class="admin-card mb-3">
    <div class="card-head">Yêu cầu hủy đơn chờ xử lý (<?= count($cancels) ?>)</div>
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead><tr><th>Đơn</th><th>Khách hàng</th><th>Lý do</th><th class="money">Tổng</th><th>Thanh toán</th><th>Ngày yêu cầu</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
                <?php if (empty($cancels)): ?>
                    <tr><td colspan="7"><div class="empty-state"><?= icon('bi-check2-circle') ?>Không có yêu cầu chờ xử lý</div></td></tr>
                <?php else: foreach ($cancels as $__c): ?>
                    <tr>
                        <td data-label="Đơn"><a href="<?= BASE_URL ?>/quan-tri/don-hang/xem/<?= e($__c['order_code']) ?>" class="fw-semibold text-decoration-none"><?= e($__c['order_code']) ?></a></td>
                        <td data-label="Khách hàng">
                            <div class="fw-semibold small"><?= e($__c['customer_name']) ?></div>
                            <div class="text-muted small"><?= e($__c['customer_phone']) ?></div>
                        </td>
                        <td data-label="Lý do" class="small"><?= e($__c['reason']) ?></td>
                        <td data-label="Tổng" class="money fw-semibold"><?= format_money((int)$__c['total_amount']) ?></td>
                        <td data-label="Thanh toán" class="small"><?= e($__methodLabel[$__c['payment_method']] ?? strtoupper((string)$__c['payment_method'])) ?>
                            <span class="d-block text-muted">
                                <?= $__c['payment_status'] === 'paid' ? 'Đã thanh toán' : ($__c['payment_status'] === 'refunded' ? 'Đã hoàn tiền' : 'Chưa thanh toán') ?>
                            </span>
                        </td>
                        <td data-label="Ngày yêu cầu" class="text-muted small"><?= format_date($__c['created_at']) ?></td>
                        <td data-label="Thao tác" class="text-end text-nowrap">
                            <form method="post" action="<?= BASE_URL ?>/quan-tri/duyet/huy/<?= (int)$__c['id'] ?>" class="d-inline">
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="decision" value="approve">
                                <button class="btn btn-sm btn-success" data-confirm="Đồng ý hủy đơn này? Đơn sẽ bị hủy (hoàn kho, hoàn tiền nếu đã thanh toán).">Duyệt</button>
                            </form>
                            <button class="btn btn-sm btn-outline-danger" onclick="document.getElementById('rej-<?= (int)$__c['id'] ?>').classList.toggle('d-none')">Từ chối</button>
                            <form id="rej-<?= (int)$__c['id'] ?>" method="post" action="<?= BASE_URL ?>/quan-tri/duyet/huy/<?= (int)$__c['id'] ?>" class="d-none mt-2 d-flex gap-2" style="max-width:260px">
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="decision" value="reject">
                                <input type="text" name="note" class="form-control form-control-sm" placeholder="Lý do từ chối" required>
                                <button class="btn btn-sm btn-outline-danger">Xác nhận</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $pager = $cancelsPager; require BASE_PATH . '/includes/partials/pagination.php'; ?>

<div class="admin-card">
    <div class="card-head">Hoàn tiền chờ xử lý (<?= count($refunds) ?>)</div>
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead><tr><th>Đơn</th><th>Khách hàng</th><th class="money">Số tiền</th><th>Phương thức</th><th>Lý do</th><th>Ngày tạo</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
                <?php if (empty($refunds)): ?>
                    <tr><td colspan="7"><div class="empty-state"><?= icon('bi-arrow-repeat') ?>Không có yêu cầu hoàn tiền</div></td></tr>
                <?php else: foreach ($refunds as $__r): ?>
                    <tr>
                        <td data-label="Đơn"><a href="<?= BASE_URL ?>/quan-tri/don-hang/xem/<?= e($__r['order_code']) ?>" class="fw-semibold text-decoration-none"><?= e($__r['order_code']) ?></a></td>
                        <td data-label="Khách hàng" class="small fw-semibold"><?= e($__r['customer_name']) ?></td>
                        <td data-label="Số tiền" class="money fw-bold text-danger"><?= format_money((float)$__r['amount']) ?></td>
                        <td data-label="Phương thức" class="small"><?= e($__methodLabel[$__r['method']] ?? strtoupper((string)$__r['method'])) ?></td>
                        <td data-label="Lý do" class="small"><?= e($__r['reason']) ?></td>
                        <td data-label="Ngày tạo" class="text-muted small"><?= format_date($__r['created_at']) ?></td>
                        <td data-label="Thao tác" class="text-end">
                            <form method="post" action="<?= BASE_URL ?>/quan-tri/duyet/hoan-tien/<?= (int)$__r['id'] ?>" class="d-inline">
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                <button class="btn btn-sm btn-outline-success" data-confirm="Xác nhận đã chuyển tiền hoàn cho khách?">Hoàn tất hoàn tiền</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $pager = $refundsPager; require BASE_PATH . '/includes/partials/pagination.php'; ?>