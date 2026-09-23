<?php
/** Quản lý thuế VAT admin */
declare(strict_types=1);
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0">Thuế (VAT)</h1>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#taxModal" data-mode="create"><?= icon('bi-plus-lg', 'me-1') ?>Thêm mức thuế</button>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="admin-stat">
            <div class="stat-label">Mức thuế đang dùng</div>
            <div class="h5 mb-0 mt-1"><?= count($rates) ?> mức</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="admin-stat">
            <div class="stat-label">Mức mặc định</div>
            <div class="h5 mb-0 mt-1 text-primary"><?= $default ? rtrim(rtrim((string)$default['rate'], '0'), '.') . '%' : '—' ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="admin-stat">
            <div class="stat-label">Sản phẩm chưa gán thuế</div>
            <div class="h5 mb-0 mt-1 <?= $unassigned > 0 ? 'text-danger' : '' ?>"><?= $unassigned ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="admin-stat">
            <div class="stat-label">VAT đã thu (báo cáo)</div>
            <div class="h5 mb-0 mt-1 text-success"><?= format_money((float)$reportTotals['vat']) ?></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12">
        <div class="admin-card mb-3">
            <div class="card-head">Danh sách mức thuế</div>
            <div class="card-body p-0">
                <table class="table admin-table mb-0">
                    <thead><tr><th>Tên</th><th class="text-center">Thuế suất</th><th class="text-center">Loại</th><th class="text-center">Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
                    <tbody>
                        <?php if (empty($rates)): ?>
                            <tr><td colspan="5"><div class="empty-state"><?= icon('bi-percent') ?>Chưa có mức thuế</div></td></tr>
                        <?php else: foreach ($rates as $__t): ?>
                            <tr>
                                <td data-label="Tên" class="fw-semibold"><?= e($__t['name']) ?></td>
                                <td data-label="Thuế suất" class="text-center"><span class="badge text-bg-primary"><?= rtrim(rtrim((string)$__t['rate'], '0'), '.') ?>%</span></td>
                                <td data-label="Loại" class="text-center">
                                    <?php if ((int)($default['id'] ?? 0) === (int)$__t['id']): ?>
                                        <span class="badge text-bg-warning">Mặc định</span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Trạng thái" class="text-center">
                                    <a href="<?= BASE_URL ?>/quan-tri/thue/trang-thai/<?= (int)$__t['id'] ?>" class="badge text-decoration-none <?= $__t['status'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $__t['status'] ? 'Hoạt động' : 'Ẩn' ?></a>
                                </td>
                                <td data-label="Thao tác" class="text-end text-nowrap">
                                    <a href="<?= BASE_URL ?>/quan-tri/thue/sua/<?= (int)$__t['id'] ?>" class="btn btn-sm btn-outline-primary btn-edit-tax"
                                       data-bs-toggle="modal" data-bs-target="#taxModal" data-mode="edit"
                                       data-id="<?= (int)$__t['id'] ?>" data-name="<?= e($__t['name'] ?? '') ?>" data-rate="<?= e($__t['rate'] ?? '') ?>"
                                       data-description="<?= e($__t['description'] ?? '') ?>" data-status="<?= (int)$__t['status'] ?>" title="Sửa"><?= icon('bi-pencil') ?></a>
                                    <?php if ((int)($default['id'] ?? 0) !== (int)$__t['id']): ?>
                                        <form method="post" action="<?= BASE_URL ?>/quan-tri/thue/mac-dinh/<?= (int)$__t['id'] ?>" class="d-inline">
                                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                            <button class="btn btn-sm btn-outline-warning" title="Đặt làm mặc định"><?= icon('bi-star') ?></button>
                                        </form>
                                        <a href="<?= BASE_URL ?>/quan-tri/thue/xoa/<?= (int)$__t['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Xóa mức thuế này?"><?= icon('bi-trash') ?></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-card">
            <div class="card-head">Gán thuế hàng loạt cho sản phẩm</div>
            <div class="card-body">
                <form method="post" action="<?= BASE_URL ?>/quan-tri/thue/gan/bulk">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <div class="mb-3">
                        <label class="form-label small text-muted">Mức thuế áp dụng</label>
                        <select class="form-select" name="rate_id" required>
                            <option value="">-- Chọn mức thuế --</option>
                            <?php foreach ($rates as $__t): if (!$__t['status']) continue; ?>
                                <option value="<?= (int)$__t['id'] ?>"><?= e($__t['name']) ?> (<?= rtrim(rtrim((string)$__t['rate'], '0'), '.') ?>%)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Áp dụng cho</label>
                        <select class="form-select" name="apply_to">
                            <option value="empty">Chỉ sản phẩm chưa gán thuế (<?= $unassigned ?> SP)</option>
                            <option value="all">Tất cả sản phẩm</option>
                        </select>
                    </div>
                    <button class="btn btn-primary w-100">Áp dụng</button>
                </form>
            </div>
        </div>

        <div class="admin-card">
            <div class="card-head">Thuế áp dụng cho Vận chuyển & Lắp đặt</div>
            <div class="card-body">
                <?php
                // Mức thuế được phép chọn = các mức thuế đang HOẠT ĐỘNG (5%/8%/10%...).
                $__svcRates = array_values(array_filter($rates, fn($r) => (bool)($r['status'] ?? 0)));
                $__svcShipOk  = array_filter($__svcRates, fn($r) => abs((float)$r['rate'] - (float)($taxShipRate ?? 5)) < 0.0001);
                $__svcInstOk  = array_filter($__svcRates, fn($r) => abs((float)$r['rate'] - (float)($taxInstallRate ?? 5)) < 0.0001);
                ?>
                <form method="post" action="<?= BASE_URL ?>/quan-tri/thue/dich-vu">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Mức thuế cho Phí vận chuyển</label>
                            <select class="form-select" name="tax_shipping_rate">
                                <?php if (!$__svcShipOk): ?>
                                    <option value="<?= (float)$taxShipRate ?>" selected>Đang lưu: <?= rtrim(rtrim((string)(float)$taxShipRate, '0'), '.') ?>%</option>
                                <?php endif; ?>
                                <?php $__svcShipSel = !empty($__svcShipOk) ? (float)$taxShipRate : null; ?>
                                <?php foreach ($__svcRates as $__rt): ?>
                                    <option value="<?= (float)$__rt['rate'] ?>" <?= $__svcShipSel !== null && (float)$__rt['rate'] === $__svcShipSel ? 'selected' : '' ?>><?= e($__rt['name']) ?> (<?= rtrim(rtrim((string)$__rt['rate'], '0'), '.') ?>%)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Mức thuế cho Phí lắp đặt</label>
                            <select class="form-select" name="tax_install_rate">
                                <?php if (!$__svcInstOk): ?>
                                    <option value="<?= (float)$taxInstallRate ?>" selected>Đang lưu: <?= rtrim(rtrim((string)(float)$taxInstallRate, '0'), '.') ?>%</option>
                                <?php endif; ?>
                                <?php $__svcInstSel = !empty($__svcInstOk) ? (float)$taxInstallRate : null; ?>
                                <?php foreach ($__svcRates as $__rt): ?>
                                    <option value="<?= (float)$__rt['rate'] ?>" <?= $__svcInstSel !== null && (float)$__rt['rate'] === $__svcInstSel ? 'selected' : '' ?>><?= e($__rt['name']) ?> (<?= rtrim(rtrim((string)$__rt['rate'], '0'), '.') ?>%)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary w-100"><?= icon('bi-check2', 'me-1') ?>Lưu mức thuế dịch vụ</button>
                        </div>
                    </div>
                    <div class="small text-muted mt-2"><?= icon('bi-info-circle', 'me-1') ?>Hai khoản này có thể chịu mức thuế khác với thuế sản phẩm và khác nhau giữa vận chuyển/lắp đặt, tùy theo quy định thuế áp dụng cho từng loại hình dịch vụ.</div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="admin-card mt-3">
    <div class="card-head d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span>Báo cáo VAT theo mức thuế (từ đơn hàng)</span>
        <form class="d-flex gap-2 align-items-end" method="get">
            <input type="hidden" name="route" value="quan-tri/thue">
            <div>
                <label class="form-label small text-muted mb-0">Từ</label>
                <input type="date" class="form-control form-control-sm" name="from" value="<?= e($from) ?>">
            </div>
            <div>
                <label class="form-label small text-muted mb-0">Đến</label>
                <input type="date" class="form-control form-control-sm" name="to" value="<?= e($to) ?>">
            </div>
            <button class="btn btn-outline-primary btn-sm">Xem</button>
        </form>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table admin-table mb-0">
            <thead><tr><th>Mức thuế</th><th class="text-center">Đơn</th><th class="money">Giá trị hàng</th><th class="money">VAT đã thu</th></tr></thead>
            <tbody>
                <?php if (empty($reportRows)): ?>
                    <tr><td colspan="4"><div class="empty-state"><?= icon('bi-percent') ?>Không có dữ liệu VAT trong khoảng này</div></td></tr>
                <?php else: foreach ($reportRows as $__rr): ?>
                    <tr>
                        <td data-label="Mức thuế" class="fw-semibold">Thuế <?= rtrim(rtrim((string)$__rr['vat_rate'], '0'), '.') ?>%</td>
                        <td data-label="Đơn" class="text-center"><?= (int)$__rr['orders'] ?></td>
                        <td data-label="Giá trị hàng" class="money"><?= format_money((float)$__rr['goods_value']) ?></td>
                        <td data-label="VAT đã thu" class="money text-success"><?= format_money((float)$__rr['vat']) ?></td>
                    </tr>
                <?php endforeach; ?>
                    <tr class="table-light">
                        <td data-label="Tổng" class="fw-bold">Tổng</td>
                        <td data-label="" class="text-center fw-bold"><?= (int)$reportTotals['orders'] ?></td>
                        <td data-label="" class="money fw-bold"><?= format_money((float)$reportTotals['goods_value']) ?></td>
                        <td data-label="" class="money fw-bold text-success"><?= format_money((float)$reportTotals['vat']) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="small text-muted p-2 border-top">Báo cáo VAT theo kỳ và theo mức thuế.</div>
    </div>
</div>

<!-- ============ MODAL THÊM / SỬA MỨC THUẾ ============ -->
<div class="modal fade" id="taxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="<?= BASE_URL ?>/quan-tri/thue/luu">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" id="txf_id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="taxModalTitle"><?= icon('bi-percent', 'me-1') ?>Thêm mức thuế</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-muted">Tên mức thuế</label>
                        <input type="text" class="form-control" name="name" id="txf_name" placeholder="VD: Thuế GTGT 8%">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Thuế suất (%) *</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control" name="rate" id="txf_rate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Mô tả</label>
                        <textarea class="form-control" name="description" id="txf_description" rows="2"></textarea>
                    </div>
                    <div class="form-check form-switch toggle-row mb-3">
                        <input class="form-check-input" type="checkbox" name="status" id="txf_status" checked>
                        <label class="form-check-label small" for="txf_status">Hoạt động (dùng được)</label>
                    </div>
                    <div class="form-check form-switch toggle-row">
                        <input class="form-check-input" type="checkbox" name="make_default" id="txf_make_default">
                        <label class="form-check-label small" for="txf_make_default">Đặt làm mức thuế mặc định</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button class="btn btn-primary"><?= icon('bi-check2', 'me-1') ?>Lưu mức thuế</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('taxModal');
    const title = document.getElementById('taxModalTitle');
    const setVal = (id, v) => { const el = document.getElementById('txf_' + id); if (el) el.value = v ?? ''; };
    const setStatus = v => { const c = document.getElementById('txf_status'); if (c) c.checked = !!Number(v); };
    const setDefault = v => { const c = document.getElementById('txf_make_default'); if (c) c.checked = !!Number(v); };

    modal.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        if (btn && btn.dataset.mode === 'edit') return;
        title.innerHTML = '<?= icon('bi-percent', 'me-1') ?>Thêm mức thuế';
        setVal('id', '0');
        setVal('name', '');
        setVal('rate', '');
        setVal('description', '');
        setStatus(1);
        setDefault(0);
    });

    document.querySelectorAll('.btn-edit-tax').forEach(btn => {
        btn.addEventListener('click', function () {
            title.innerHTML = '<?= icon('bi-pencil', 'me-1') ?>Sửa mức thuế';
            setVal('id', this.dataset.id);
            setVal('name', this.dataset.name);
            setVal('rate', this.dataset.rate);
            setVal('description', this.dataset.description);
            setStatus(this.dataset.status);
            setDefault(0);
        });
    });
});
</script>
