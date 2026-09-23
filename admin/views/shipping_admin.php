<?php
/** Quản lý vận chuyển 2 loại (Loại 1: giao & lắp đặt / Loại 2: đơn vị vận chuyển) — chỉ super admin */
declare(strict_types=1);
use WoodCon\Admin;
$isSuper = Admin::isSuper();

function __fm(int|float|null $v): string { return number_format((float)$v, 0, ',', '.'); }
$__th = (float)get_setting('free_ship_threshold', 2000000); // ngưỡng chung (fallback)
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title">Vận chuyển</h1>
    </div>
    <?php if (!$isSuper): ?>
        <span class="badge text-bg-secondary">Chế độ chỉ đọc</span>
    <?php endif; ?>
</div>

<?php if (!$isSuper): ?>
    <div class="alert alert-warning">Chỉ Chủ hệ thống mới được chỉnh sửa phần này.</div>
<?php endif; ?>

<div class="accordion" id="shippingAccordion">

    <!-- ============ NHÓM 1: CÀI ĐẶT CHUNG ============ -->
    <div class="accordion-item admin-card border-0 mb-2">
        <h2 class="accordion-header" id="head-chung">
            <button class="accordion-button collapsed px-3 py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-chung" aria-expanded="false" aria-controls="collapse-chung">
                <span class="fw-semibold"><?= icon('bi-gear', 'me-2', 'style="color:var(--color-primary)"') ?>Cài đặt chung</span>
            </button>
        </h2>
        <div id="collapse-chung" class="accordion-collapse collapse" data-bs-parent="#shippingAccordion" aria-labelledby="head-chung">
            <div class="accordion-body px-3 py-3">
                <?php if ($isSuper): ?>
                    <form method="post" action="<?= BASE_URL ?>/quan-tri/van-chuyen">
                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="sub" value="options">
                <?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label mb-1">Thành phố kho (điểm xuất phát)</label>
                        <input type="text" name="setting_ship_warehouse_city" class="form-control form-control-sm" value="<?= e((string)get_setting('ship_warehouse_city', 'Hồ Chí Minh')) ?>" <?= $isSuper ? '' : 'disabled' ?>>
                        <div class="form-text">Nơi xe hàng khởi hành, dùng làm mốc tính khoảng cách cho Loại 1.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label mb-1">Địa chỉ kho (hiển thị tham khảo)</label>
                        <input type="text" name="setting_ship_warehouse_address" class="form-control form-control-sm" value="<?= e((string)get_setting('ship_warehouse_address', '')) ?>" <?= $isSuper ? '' : 'disabled' ?>>
                        <div class="form-text">Chỉ hiển thị tham khảo, không ảnh hưởng tính phí.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1">Phí ship tối thiểu (VNĐ)</label>
                        <input type="number" min="0" step="1000" name="setting_ship_min_fee" class="form-control form-control-sm" value="<?= (int)get_setting('ship_min_fee', 20000) ?>" <?= $isSuper ? '' : 'disabled' ?>>
                        <div class="form-text">Giá trị dự phòng khi một vùng của Loại 2 chưa nhập cước cơ bản.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1">Ngưỡng miễn phí vận chuyển CHUNG (VNĐ)</label>
                        <input type="number" min="0" step="1000" name="setting_free_ship_threshold" class="form-control form-control-sm" value="<?= (int)$__th ?>" <?= $isSuper ? '' : 'disabled' ?>>
                        <div class="form-text">Giá trị dự phòng: bậc/vùng nào chưa đặt ngưỡng riêng sẽ dùng con số này.</div>
                    </div>
                </div>
                <?php if ($isSuper): ?>
                        <div class="d-flex justify-content-end mt-3">
                            <button class="btn btn-primary px-4 btn-sm" type="submit"><?= icon('bi-check2', 'me-1') ?>Lưu cài đặt chung</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ============ NHÓM 2: LOẠI 1 ============ -->
    <div class="accordion-item admin-card border-0 mb-2">
        <h2 class="accordion-header" id="head-loai1">
            <button class="accordion-button collapsed px-3 py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-loai1" aria-expanded="false" aria-controls="collapse-loai1">
                <span class="fw-semibold"><?= icon('bi-house-check', 'me-2', 'style="color:var(--color-primary)"') ?>Loại 1 — Giao hàng &amp; lắp đặt tại nhà</span>
            </button>
        </h2>
        <div id="collapse-loai1" class="accordion-collapse collapse" data-bs-parent="#shippingAccordion" aria-labelledby="head-loai1">
            <div class="accordion-body px-3 py-3">
                <div id="loai1Area">
                    <style>
                        #loai1Area .loai1-field { display: flex; flex-direction: column; min-width: 0; }
                        #loai1Area .loai1-field .form-label { margin-bottom: .5rem; }

                        #loai1Area .loai1-field .form-control,
                        #loai1Area .loai1-field .btn,
                        #loai1Area .loai1-inline .form-control,
                        #loai1Area .loai1-inline .btn { height: 42px; }

                        #loai1Area .loai1-inline { display: flex; gap: .75rem; align-items: flex-end; }
                        #loai1Area .loai1-inline .field-input { flex: 1 1 auto; min-width: 0; }
                        #loai1Area .loai1-inline .field-btn { flex: 0 0 auto; white-space: nowrap; }

                        #loai1Area .loai1-divider { border: 0; border-top: 1px solid var(--border-default, #93A1A1); opacity: .45; margin: 1.5rem 0 1rem; }

                        #loai1Area .loai1-toggle {
                            display: inline-flex; align-items: center; gap: .4rem;
                            color: var(--color-primary, #268BD2);
                            background: var(--color-primary-focus, rgba(38, 139, 210, .12));
                            border: 1px solid transparent; border-radius: var(--radius-sm, .6rem);
                            padding: .35rem .8rem; font-size: .85rem; font-weight: 500; cursor: pointer;
                        }
                        #loai1Area .loai1-toggle:hover { background: rgba(38, 139, 210, .18); }
                        #loai1Area .loai1-toggle .chev { transition: transform .2s ease; }
                        #loai1Area .loai1-toggle.collapsed .chev { transform: rotate(-90deg); }
                        #loai1Area .loai1-prov-collapse { margin-top: 1rem; }

                        #loai1Area .loai1-prov-tbl,
                        #loai1Area .loai1-brk-tbl { table-layout: fixed; margin: 0; }

                        #loai1Area .loai1-prov-tbl th,
                        #loai1Area .loai1-prov-tbl td,
                        #loai1Area .loai1-brk-tbl th,
                        #loai1Area .loai1-brk-tbl td { vertical-align: middle; }

                        #loai1Area .loai1-prov-tbl th:nth-child(1), #loai1Area .loai1-prov-tbl td:nth-child(1) { width: 40%; }
                        #loai1Area .loai1-prov-tbl th:nth-child(2), #loai1Area .loai1-prov-tbl td:nth-child(2) { width: 40%; }
                        #loai1Area .loai1-prov-tbl th:nth-child(3), #loai1Area .loai1-prov-tbl td:nth-child(3) { width: 20%; text-align: center; }
                        #loai1Area .loai1-prov-tbl td:nth-child(3) form { display: inline-block; margin-bottom: 0; }

                        #loai1Area .loai1-brk-tbl th:nth-child(1), #loai1Area .loai1-brk-tbl td:nth-child(1) { width: 24%; }
                        #loai1Area .loai1-brk-tbl th:nth-child(2), #loai1Area .loai1-brk-tbl td:nth-child(2) { width: 28%; }
                        #loai1Area .loai1-brk-tbl th:nth-child(3), #loai1Area .loai1-brk-tbl td:nth-child(3) { width: 28%; }
                        #loai1Area .loai1-brk-tbl th:nth-child(4), #loai1Area .loai1-brk-tbl td:nth-child(4) { width: 20%; }

                        #loai1Area .loai1-prov-tbl thead th,
                        #loai1Area .loai1-brk-tbl thead th {
                            background: var(--color-primary-focus, rgba(38, 139, 210, .12));
                            color: var(--text-heading, #586E75);
                            font-weight: 600; white-space: nowrap;
                        }

                        #loai1Area .loai1-prov-tbl > :not(caption) > * > *,
                        #loai1Area .loai1-brk-tbl > :not(caption) > * > * {
                            border-color: var(--border-default, #93A1A1);
                            border-top-width: 1px;
                        }
                        #loai1Area .loai1-prov-tbl th,
                        #loai1Area .loai1-brk-tbl th { border-bottom-width: 1px; }

                        #loai1Area .loai1-prov-tbl td { padding: .55rem .6rem; }
                        #loai1Area .loai1-brk-tbl td { padding: .6rem .5rem; }
                        #loai1Area .loai1-brk-tbl .form-control { height: 42px; }

                        #loai1Area .loai1-actions { display: flex; align-items: center; justify-content: center; gap: .55rem; }
                        #loai1Area .loai1-actions-btn {
                            width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;
                            border: 0; background: transparent; border-radius: var(--radius-sm, .6rem);
                            color: var(--text-muted, #93A1A1); line-height: 1;
                        }
                        #loai1Area .loai1-actions-btn.save { color: var(--color-primary, #268BD2); }
                        #loai1Area .loai1-actions-btn.danger { color: #DC322F; }
                        #loai1Area .loai1-actions-btn:hover { background: var(--color-primary-focus, rgba(38, 139, 210, .12)); }

                        #loai1Area .loai1-add-row { display: grid; gap: .75rem; align-items: end; }
                        #loai1Area .loai1-add-prov { grid-template-columns: 40% 40% 20%; }
                        #loai1Area .loai1-add-brk { grid-template-columns: 12% 12% 28% 28% 20%; }
                        #loai1Area .loai1-add-row .btn { white-space: nowrap; }
                        @media (max-width: 991.98px) {
                            #loai1Area .loai1-add-brk { grid-template-columns: repeat(2, 1fr); }
                            #loai1Area .loai1-add-prov { grid-template-columns: repeat(2, 1fr); }
                            #loai1Area .loai1-add-row .loai1-field:last-child { grid-column: 1 / -1; }
                        }
                    </style>
                    <?php if ($isSuper): ?>
                    <form method="post" action="<?= BASE_URL ?>/quan-tri/van-chuyen" class="mb-4">
                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="sub" value="options">
                        <div class="loai1-inline">
                            <div class="loai1-field field-input">
                                <label class="form-label mb-1">Tên hiển thị Loại 1</label>
                                <input type="text" name="setting_ship_label_type1" class="form-control" value="<?= e($typeLabels[WoodCon\Shipping::TYPE_SHOP] ?? '') ?>">
                            </div>
                            <div class="field-btn">
                                <button class="btn btn-outline-primary" type="submit"><?= icon('bi-check2', 'me-1') ?>Lưu nhãn</button>
                            </div>
                        </div>
                        <div class="form-text mt-1">Tên loại vận chuyển hiện trên trang thanh toán.</div>
                    </form>
                <?php endif; ?>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h6 class="fw-semibold mb-1">Khoảng cách 63 Tỉnh/Thành từ kho (km)</h6>
                        <p class="small text-muted mb-0">Dùng để chọn đúng bậc phí theo khoảng cách. Có thể thêm tỉnh mới khi mở bảng.</p>
                    </div>
                    <button type="button" class="loai1-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#loai1Prov" aria-expanded="false" aria-controls="loai1Prov">
                        <?= icon('bi-chevron-down', 'chev') ?><span>Xem danh sách 63 Tỉnh/Thành</span>
                    </button>
                </div>
                <div class="collapse loai1-prov-collapse" id="loai1Prov">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle admin-table loai1-prov-tbl">
                            <thead>
                                <tr><th>Tỉnh/Thành</th><th>Km</th><th></th></tr>
                            </thead>
                            <tbody>
                                <?php foreach (($provinces ?? []) as $row): ?>
                                    <tr>
                                        <td data-label="Tỉnh/Thành"><span class="fw-semibold small"><?= e($row['province']) ?></span></td>
                                        <td data-label="Km" class="small text-muted"><?= __fm((float)$row['distance_km']) ?> km</td>
                                        <td data-label="Thao tác" class="small">
                                            <?php if ($isSuper): ?>
                                                <form method="post" action="<?= BASE_URL ?>/quan-tri/van-chuyen" onsubmit="return confirm('Xóa tỉnh <?= e($row['province']) ?>?')">
                                                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                    <input type="hidden" name="sub" value="province_del">
                                                    <input type="hidden" name="province" value="<?= e($row['province']) ?>">
                                                    <button type="submit" class="loai1-actions-btn danger" title="Xóa"><?= icon('bi-x-circle') ?></button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($isSuper): ?>
                        <form method="post" action="<?= BASE_URL ?>/quan-tri/van-chuyen" class="loai1-add-row loai1-add-prov border-top pt-3">
                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="sub" value="province">
                            <div class="loai1-field">
                                <label class="form-label mb-1 small">Thêm tỉnh</label>
                                <input type="text" name="province" class="form-control" required>
                            </div>
                            <div class="loai1-field">
                                <label class="form-label mb-1 small">Km từ kho</label>
                                <input type="number" min="0" step="0.1" name="distance_km" class="form-control" required>
                            </div>
                            <div class="loai1-field">
                                <button class="btn btn-primary" type="submit"><?= icon('bi-plus-lg', 'me-1') ?>Thêm</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <hr class="loai1-divider">
                <h6 class="fw-semibold mb-1">Bậc mức phí &amp; ngưỡng miễn phí vận chuyển</h6>
                <p class="small text-muted mb-3">Mỗi bậc có phí riêng và ngưỡng miễn phí riêng. Đơn đạt giá trị hàng ≥ ngưỡng của đúng bậc đang giao sẽ được miễn phí vận chuyển. Để trống ô ngưỡng = dùng ngưỡng chung ở nhóm Cài đặt chung.</p>
                <div class="table-responsive">
                    <table class="table align-middle admin-table loai1-brk-tbl">
                        <thead><tr><th>Khoảng cách</th><th>Phí (VNĐ)</th><th>Ngưỡng miễn phí (VNĐ)</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach (($brackets ?? []) as $b): ?>
                            <?php if ($isSuper): ?>
                                <tr>
                                    <td data-label="Khoảng cách"><span class="fw-semibold small"><?= (int)$b['min_km'] ?> — <?= $b['max_km'] === null ? '∞' : (int)$b['max_km'] ?> km</span></td>
                                    <td data-label="Phí (VNĐ)"><input type="number" min="0" step="1000" name="fee" form="upd-<?= (int)$b['id'] ?>" class="form-control" value="<?= (int)$b['fee'] ?>" title="Phí bậc (VNĐ)"></td>
                                    <td data-label="Ngưỡng miễn phí (VNĐ)"><input type="number" min="0" step="1000" name="free_ship_threshold" form="upd-<?= (int)$b['id'] ?>" class="form-control" value="<?= isset($b['free_ship_threshold']) && $b['free_ship_threshold'] !== null ? (int)$b['free_ship_threshold'] : '' ?>" placeholder="trống = ngưỡng chung" title="Ngưỡng miễn phí riêng bậc này (trống = dùng ngưỡng chung)"></td>
                                    <td data-label="Thao tác">
                                        <div class="loai1-actions">
                                            <button type="submit" form="upd-<?= (int)$b['id'] ?>" class="loai1-actions-btn save" title="Lưu bậc"><?= icon('bi-check2', 'fs-6') ?></button>
                                            <button type="submit" form="del-<?= (int)$b['id'] ?>" class="loai1-actions-btn danger" title="Xóa" onclick="return confirm('Xóa bậc phí này?')"><?= icon('bi-x-circle', 'fs-6') ?></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td class="small"><?= (int)$b['min_km'] ?> — <?= $b['max_km'] === null ? '∞' : (int)$b['max_km'] ?> km</td>
                                    <td class="small fw-semibold"><?= __fm((float)$b['fee']) ?> đ</td>
                                    <td class="small text-muted"><?= isset($b['free_ship_threshold']) && $b['free_ship_threshold'] !== null ? __fm((float)$b['free_ship_threshold']) . ' đ' : '— (ngưỡng chung)' ?></td>
                                    <td></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($isSuper): foreach (($brackets ?? []) as $b): ?>
                        <form id="upd-<?= (int)$b['id'] ?>" method="post" action="<?= BASE_URL ?>/quan-tri/van-chuyen" class="d-none">
                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="sub" value="bracket_update">
                            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                        </form>
                        <form id="del-<?= (int)$b['id'] ?>" method="post" action="<?= BASE_URL ?>/quan-tri/van-chuyen" class="d-none">
                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="sub" value="bracket_del">
                            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                        </form>
                    <?php endforeach; endif; ?>
                </div>
                <?php if ($isSuper): ?>
                    <form method="post" action="<?= BASE_URL ?>/quan-tri/van-chuyen" class="loai1-add-row loai1-add-brk border-top pt-3 mt-4">
                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="sub" value="bracket">
                        <div class="loai1-field">
                            <label class="form-label mb-1 small">Từ (km)</label>
                            <input type="number" min="0" name="min_km" class="form-control" required>
                        </div>
                        <div class="loai1-field">
                            <label class="form-label mb-1 small">Đến (km)</label>
                            <input type="number" min="0" name="max_km" class="form-control" placeholder="trống = ∞">
                        </div>
                        <div class="loai1-field">
                            <label class="form-label mb-1 small">Phí (VNĐ)</label>
                            <input type="number" min="0" step="1000" name="fee" class="form-control" required>
                        </div>
                        <div class="loai1-field">
                            <label class="form-label mb-1 small">Ngưỡng miễn phí (VNĐ)</label>
                            <input type="number" min="0" step="1000" name="free_ship_threshold" class="form-control" placeholder="trống = ngưỡng chung">
                        </div>
                        <div class="loai1-field">
                            <button class="btn btn-primary" type="submit"><?= icon('bi-plus-lg', 'me-1') ?>Thêm bậc</button>
                        </div>
                    </form>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ NHÓM 3: LOẠI 2 - VIETTEL POST ============ -->
    <?php
        $__vtpProvs = $vtpCatalog['provinces'] ?? [];
        $__vtpDists = $vtpSenderDistricts ?? [];
        $__vtpEnabled = (bool)($vtp['enabled'] ?? false);
        $__vtpReady = $__vtpEnabled
            && ($vtp['has_token'] ?? false)
            && (int)($vtp['sender']['province_id'] ?? 0) > 0
            && (int)($vtp['sender']['district_id'] ?? 0) > 0;
        $__statusIcon = $__vtpReady
            ? '<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="10" fill="#22c55e"/><path d="M6 10L9 13L14 7" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
            : '<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="10" fill="#ef4444"/><path d="M7 7L13 13M13 7L7 13" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/></svg>';
        $__svcMain = ['' => 'Tự động (rẻ nhất)', 'VCN' => 'Chuyển phát nhanh (VCN)', 'VTK' => 'Chuyển phát tiết kiệm (VTK)', 'SCN' => 'Chuyển phát nhanh tiêu chuẩn (SCN)', 'STK' => 'Chuyển phát tiêu chuẩn (STK)', 'SHT' => 'Chuyển phát hỏa tốc (SHT)', 'VHT' => 'Hỏa tốc (VHT)'];
        $__svcOther = ['LCOD' => 'TMĐT Tiết kiệm thỏa thuận', 'NCOD' => 'TMĐT Nhanh thỏa thuận', 'VH1' => 'Hàng nặng — nhóm 1', 'VH2' => 'Hàng nặng — nhóm 2', 'V6K' => 'Đồ chơi, nội thất', 'VTT' => 'Tổng hợp'];
        $__ptLabel = ['HH' => 'Hàng hóa', 'HD' => 'Thư / tài liệu'];
    ?>
    <div class="accordion-item admin-card border-0 mb-2">
        <h2 class="accordion-header" id="head-loai2">
            <button class="accordion-button collapsed px-3 py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-loai2" aria-expanded="false" aria-controls="collapse-loai2">
                <span class="fw-semibold"><?= icon('bi-truck', 'me-2', 'style="color:var(--color-primary)"') ?>Loại 2 — Vận chuyển qua Viettel Post</span>
                <span id="vtpHdrBadge" class="ms-2" style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;vertical-align:middle"><?= $__statusIcon ?></span>
            </button>
        </h2>
        <div id="collapse-loai2" class="accordion-collapse collapse" data-bs-parent="#shippingAccordion" aria-labelledby="head-loai2">
            <div class="accordion-body px-3 py-3">
                <?php if (!$curlOk): ?>
                    <div class="alert alert-danger small mb-3">PHP của máy chủ đang tắt tiện ích <strong>cURL</strong> — không thể gọi API Viettel Post.</div>
                <?php endif; ?>

                <?php if ($isSuper): ?>
                    <form method="post" action="<?= BASE_URL ?>/quan-tri/van-chuyen" id="vtpForm" class="mb-2">
                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="sub" value="vtp_save">

                        <!-- Khối 1 — Bật/tắt (luôn hiện) -->
                        <div class="form-check form-switch form-switch-lg toggle-row mb-1">
                            <input class="form-check-input" type="checkbox" name="setting_vtp_enabled" value="1" id="vtp_enabled" <?= $__vtpEnabled ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="vtp_enabled">Dùng Viettel Post để tính phí ship tự động</label>
                        </div>
                        <div class="small text-muted vtp-head-desc">Khi bật, phí ship sẽ tự tính theo địa chỉ khách hàng, không cần nhập bảng giá tay.</div>

                        <div class="vtp-warn <?= ($__vtpEnabled && !$__vtpReady) ? '' : 'd-none' ?>" id="vtpWarnIncomplete">
                            <?= icon('bi-exclamation-triangle', 'me-1') ?>Cần điền Token và Kho gửi bên dưới để hoạt động.
                        </div>

                        <!-- Bước 1 → 3 + nâng cao (ẩn khi tắt) -->
                        <div id="vtpSteps" class="<?= $__vtpEnabled ? '' : 'd-none' ?>">

                            <div class="vtp-cards">

                            <!-- Bước 1 — Kết nối tài khoản -->
                            <section class="vtp-step">
                                <div class="vtp-step-head"><span class="vtp-step-num">1</span><h6 class="vtp-step-title">Kết nối tài khoản Viettel Post</h6></div>
                                <div class="vtp-grid">
                                    <div class="vtp-field vtp-field-wide">
                                        <label class="form-label" for="vtp_token">Token <?= icon('bi-info-circle', 'vtp-info', 'title="Mã bí mật cấp cho tài khoản Viettel Post của bạn — dùng để máy chủ tự tính cước thay cho bạn. Chỉ lưu ở máy chủ, không bao giờ hiện cho khách hàng."') ?></label>
                                        <div class="vtp-inline">
                                            <div class="input-group">
                                                <input type="password" name="setting_vtp_token" id="vtp_token" class="form-control" autocomplete="off" placeholder="<?= ($vtp['has_token'] ?? false) ? 'Đã lưu — để trống nếu không đổi' : 'Dán Token từ tài khoản Viettel Post của bạn' ?>">
                                            </div>
                                            <button type="button" id="vtpTestBtn" class="btn btn-primary vtp-btn"><?= icon('bi-plug', 'me-1') ?>Kiểm tra kết nối</button>
                                        </div>
                                        <div class="vtp-help d-none" id="vtpTokenHelp">Cần nhập Token để kết nối.</div>
                                        <div class="vtp-note" id="vtpTestResult"><span class="text-muted">Bấm <strong>"Kiểm tra kết nối"</strong> sau khi dán Token để xác nhận.</span></div>
                                    </div>
                                </div>
                            </section>

                            <!-- Bước 2 — Chọn kho gửi -->
                            <section class="vtp-step">
                                <div class="vtp-step-head"><span class="vtp-step-num">2</span><h6 class="vtp-step-title">Chọn kho gửi hàng</h6></div>
                                <div class="vtp-grid">
                                    <div class="vtp-field">
                                        <label class="form-label" for="vtp_sender_province_id">Tỉnh/Thành <?= icon('bi-info-circle', 'vtp-info', 'title="Danh mục tải từ Viettel Post, tự làm mới mỗi 24 giờ."') ?></label>
                                        <select name="setting_vtp_sender_province_id" id="vtp_sender_province_id" class="form-select" <?= $__vtpProvs ? '' : 'disabled' ?>>
                                            <option value="">— chọn tỉnh/thành —</option>
                                            <?php foreach ($__vtpProvs as $p): ?>
                                                <option value="<?= (int)$p['id'] ?>" <?= (int)($vtp['sender']['province_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="setting_vtp_sender_province_name" id="vtp_sender_province_name" value="<?= e($vtp['sender']['province_name'] ?? '') ?>">
                                    </div>
                                    <div class="vtp-field">
                                        <label class="form-label" for="vtp_sender_district_id">Quận/Huyện <?= icon('bi-info-circle', 'vtp-info', 'title="Tự tải ngay khi bạn chọn Tỉnh/Thành."') ?></label>
                                        <select name="setting_vtp_sender_district_id" id="vtp_sender_district_id" class="form-select" <?= $__vtpDists ? '' : 'disabled' ?>>
                                            <option value="">— chọn quận/huyện —</option>
                                            <?php foreach ($__vtpDists as $d): ?>
                                                <option value="<?= (int)$d['id'] ?>" <?= (int)($vtp['sender']['district_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="setting_vtp_sender_district_name" id="vtp_sender_district_name" value="<?= e($vtp['sender']['district_name'] ?? '') ?>">
                                        <div class="vtp-help d-none" id="vtpSenderHelp">Cần chọn đủ Tỉnh/Thành và Quận/Huyện trước khi bật.</div>
                                        <div class="small text-danger d-none mt-1" id="vtpDistErr"></div>
                                    </div>
                                    <div class="vtp-note">Đây là nơi hàng xuất phát — chọn đúng địa chỉ kho / cửa hàng của bạn.</div>
                                </div>
                            </section>

                            <!-- Bước 3 — Cách tính phí cơ bản -->
                            <section class="vtp-step">
                                <div class="vtp-step-head"><span class="vtp-step-num">3</span><h6 class="vtp-step-title">Chọn cách tính phí cơ bản</h6></div>
                                <div class="vtp-grid">
                                    <div class="vtp-field">
                                        <label class="form-label" for="vtp_svc">Hình thức giao hàng <?= icon('bi-info-circle', 'vtp-info', 'title="Chọn theo tốc độ giao bạn muốn. Giá cụ thể sẽ do Viettel Post tự tính theo địa chỉ + khối lượng đơn."') ?></label>
                                        <select name="setting_vtp_service" id="vtp_svc" class="form-select">
                                            <optgroup label="Thường dùng">
                                                <?php foreach ($__svcMain as $code => $name): ?>
                                                    <option value="<?= e($code) ?>" <?= ($vtp['service'] ?? '') === $code ? 'selected' : '' ?>><?= e($name) ?></option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                            <optgroup label="Khác (chỉ khi cần)">
                                                <?php foreach ($__svcOther as $code => $name): ?>
                                                    <option value="<?= e($code) ?>" <?= ($vtp['service'] ?? '') === $code ? 'selected' : '' ?>><?= e($name) ?></option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        </select>
                                    </div>
                                    <div class="vtp-field">
                                        <label class="form-label" for="vtp_cod">Thu tiền khi giao (COD) <?= icon('bi-info-circle', 'vtp-info', 'title="Khi bật, người giao nhận hộ số tiền khách trả cho đơn hàng và chuyển về cho bạn."') ?></label>
                                        <div class="form-check form-switch vtp-switch toggle-row">
                                            <input class="form-check-input" type="checkbox" name="setting_vtp_cod_enabled" value="1" id="vtp_cod" <?= ($vtp['cod_enabled'] ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label small" for="vtp_cod">Cho phép thu hộ tiền khi giao hàng</label>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            </div>

                            <!-- 1 đường kẻ mảnh ngăn cách nhóm bắt buộc với nâng cao -->
                            <div class="vtp-divider" role="separator"></div>

                            <!-- Tùy chỉnh nâng cao (gấp mặc định) -->
                            <button class="vtp-adv-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#vtpAdvanced" aria-expanded="false" aria-controls="vtpAdvanced">
                                <?= icon('bi-chevron-down', 'me-1 chev') ?><span class="fw-semibold">Tùy chỉnh nâng cao</span> <span class="text-muted small fw-normal">— không cần chỉnh nếu bạn không rõ mục này dùng để làm gì</span>
                            </button>
                            <div class="collapse" id="vtpAdvanced">
                                <div class="vtp-adv">
                                    <div class="vtp-adv-row">
                                        <div class="vtp-field">
                                            <label class="form-label" for="vtp_adv_user">Tài khoản Viettel Post <?= icon('bi-info-circle', 'vtp-info', 'title="Chỉ dùng để tự lấy Token mới khi mã cũ hết hạn."') ?></label>
                                            <input type="text" name="vtp_login_username" id="vtp_adv_user" class="form-control" value="<?= e($vtp['username'] ?? '') ?>" autocomplete="off">
                                        </div>
                                        <div class="vtp-field">
                                            <label class="form-label" for="vtp_adv_pass">Mật khẩu đăng nhập <?= icon('bi-info-circle', 'vtp-info', 'title="Chỉ dùng để tự lấy Token mới khi mã cũ hết hạn."') ?></label>
                                            <input type="password" name="vtp_login_password" id="vtp_adv_pass" class="form-control" autocomplete="off" placeholder="<?= ($vtp['has_password'] ?? false) ? 'Đã lưu — để trống giữ nguyên' : '' ?>">
                                        </div>
                                        <div class="vtp-field vtp-field-action">
                                            <button class="btn btn-outline-primary vtp-btn" type="button" data-sub="vtp_token_fetch"><?= icon('bi-key', 'me-1') ?>Lấy Token tự động</button>
                                        </div>
                                    </div>
                                    <div class="vtp-note">Nhập xong bấm "Lấy Token tự động" — Token mới sẽ được lưu và hiện ở Bước 1.</div>
                                    <div class="vtp-grid vtp-grid-adv">
                                        <div class="vtp-field">
                                            <label class="form-label" for="vtp_adv_ptype">Loại hàng khi gửi <?= icon('bi-info-circle', 'vtp-info', 'title="Mặc định là Hàng hóa. Chọn \'Thư / tài liệu\' nếu bạn gửi giấy tờ, văn bản."') ?></label>
                                            <select name="setting_vtp_product_type" id="vtp_adv_ptype" class="form-select">
                                                <?php foreach ($__ptLabel as $code => $name): ?>
                                                    <option value="<?= e($code) ?>" <?= ($vtp['product_type'] ?? 'HH') === $code ? 'selected' : '' ?>><?= e($name) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="vtp-field">
                                            <label class="form-label" for="vtp_adv_label">Tên hiển thị Loại 2 <?= icon('bi-info-circle', 'vtp-info', 'title="Tên loại vận chuyển hiện trên trang thanh toán của khách."') ?></label>
                                            <input type="text" name="setting_ship_label_type2" id="vtp_adv_label" class="form-control" value="<?= e($vtp['label'] ?? 'Vận chuyển qua Viettel Post') ?>">
                                        </div>
                                        <div class="vtp-field">
                                            <label class="form-label" for="vtp_adv_vol">Hệ số quy đổi thể tích <?= icon('bi-info-circle', 'vtp-info', 'title="Chỉ cần đổi khi bán hàng cồng kềnh, nhẹ nhưng chiếm nhiều thể tích. VD: 6000 = 1m³ tính thành 167kg."') ?></label>
                                            <input type="number" min="1" name="setting_ship_volumetric_divisor" id="vtp_adv_vol" class="form-control" value="<?= (int)($vtp['volumetric_divisor'] ?? 6000) ?>">
                                        </div>
                                        <div class="vtp-field">
                                            <label class="form-label" for="vtp_adv_sur">Phụ thu thêm mỗi đơn (VNĐ) <?= icon('bi-info-circle', 'vtp-info', 'title="Cộng thêm vào phí ship mà Viettel Post báo về."') ?></label>
                                            <input type="number" min="0" step="1000" name="setting_vtp_surcharge" id="vtp_adv_sur" class="form-control" value="<?= (int)($vtp['surcharge'] ?? 0) ?>">
                                        </div>
                                        <div class="vtp-field vtp-grid-full">
                                            <label class="form-label" for="vtp_adv_free">Ngưỡng miễn phí vận chuyển riêng cho Loại 2 (VNĐ) <?= icon('bi-info-circle', 'vtp-info', 'title="Đơn từ mức này trở lên được miễn phí ship. Để 0 = dùng ngưỡng chung của cửa hàng (đang <?= __fm($__th) ?> đ)."') ?></label>
                                            <input type="number" min="0" step="1000" name="setting_vtp_freeship_threshold" id="vtp_adv_free" class="form-control" value="<?= (int)($vtp['freeship_threshold'] ?? 0) ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Khối 4 — Trạng thái gần nhất (1 dòng gọn) -->
                        <?php $__last = $vtp['last'] ?? []; ?>
                        <div class="small text-muted vtp-last">
                            <?php if (!empty($__last['time'])): ?>
                                Lần tính cước gần nhất: <span class="fw-semibold"><?= e((string)$__last['time']) ?></span> — <?= e((string)$__last['message']) ?>
                            <?php else: ?>
                                Chưa có lần kiểm tra kết nối nào.
                            <?php endif; ?>
                        </div>

                        <div class="vtp-save-row">
                            <button type="submit" id="vtpSaveBtn" class="btn btn-primary vtp-save-btn"><?= icon('bi-check2', 'me-1') ?>Lưu cấu hình</button>
                        </div>
                    </form>

                    <style>
                        #vtpHdrBadge { display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; vertical-align: middle; }

                        #vtpForm .form-switch-lg .form-check-input { width: 3rem; height: 1.5rem; margin-top: 0; }
                        #vtpForm .form-check-input:checked { background-color: var(--color-primary, #268BD2); border-color: var(--color-primary, #268BD2); }

                        /* Icon giải thích (i): cùng kích thước, cùng khoảng cách, canh baseline */
                        #vtpForm .vtp-info { cursor: help; margin-left: .25rem; font-size: .85em; vertical-align: -.08em; }

                        /* Cảnh báo vàng: 1 dòng compact, căn giữa, icon bên trái */
                        #vtpForm .vtp-warn {
                            display: flex; align-items: center; justify-content: center;
                            width: max-content; max-width: 100%; margin: 1.25rem auto 0;
                            gap: .5rem; padding: .45rem 1.1rem;
                            background: rgba(181, 137, 0, .12); color: #7c5c00;
                            border: 1px solid rgba(181, 137, 0, .35); border-radius: 999px;
                            font-size: .85rem;
                        }

                        /* Nhóm 3 bước: card nối nhau, cách đều 24px */
                        #vtpForm .vtp-cards { display: grid; gap: 1.5rem; margin-top: 1.5rem; }

                        /* Card mỗi bước — cùng padding, phẳng, viền mảnh 1px */
                        #vtpForm .vtp-step {
                            background: #FFFDF6; border: 1px solid #E5E0D0;
                            border-radius: var(--radius-sm, .6rem);
                            padding: 1.4rem 1.5rem;
                        }
                        #vtpForm .vtp-step-head { display: flex; align-items: center; gap: .7rem; margin-bottom: 1.25rem; }
                        #vtpForm .vtp-step-num {
                            flex: 0 0 auto; width: 1.75rem; height: 1.75rem; line-height: 1.75rem;
                            text-align: center; background: var(--color-primary, #268BD2); color: #fff;
                            border-radius: 50%; font-size: .8rem; font-weight: 700;
                        }
                        #vtpForm .vtp-step-title { margin: 0; font-size: 1rem; font-weight: 600; color: var(--text-heading, #586E75); }

                        /* Lưới 2 cột cân đối (≥1024px); dưới ngưỡng dồn 1 cột, căn trái nhất quán */
                        #vtpForm .vtp-grid { display: grid; grid-template-columns: 1fr; gap: 1rem 1.5rem; align-items: end; }
                        @media (min-width: 1024px) { #vtpForm .vtp-grid { grid-template-columns: 1fr 1fr; } }

                        #vtpForm .vtp-grid-adv { margin-top: .25rem; }
                        #vtpForm .vtp-grid-full { grid-column: 1 / -1; }

                        #vtpForm .vtp-field { display: flex; flex-direction: column; min-width: 0; }
                        #vtpForm .vtp-field .form-label { margin-bottom: .5rem; }
                        #vtpForm .vtp-field-wide { grid-column: 1 / -1; }

                        /* Đồng bộ chiều cao tuyệt đối: input = select = nút trong cùng lưới */
                        #vtpForm .vtp-field .form-control,
                        #vtpForm .vtp-field .form-select,
                        #vtpForm .vtp-field .btn { height: 42px; }
                        #vtpForm .vtp-field .form-select { padding-top: 0; padding-bottom: 0; }

                        /* Hàng inline (Token + nút Kiểm tra): cùng dòng, cùng chiều cao */
                        #vtpForm .vtp-inline { display: flex; gap: .75rem; align-items: stretch; }
                        #vtpForm .vtp-inline .input-group { flex: 1 1 auto; min-width: 0; }
                        #vtpForm .vtp-inline .vtp-btn { flex: 0 0 auto; white-space: nowrap; }

                        /* Cột hành động chỉ có nút — canh đáy với ô nhập bên cạnh */
                        #vtpForm .vtp-field-action { justify-content: flex-end; }
                        #vtpForm .vtp-adv-row { display: flex; flex-wrap: wrap; gap: 1rem 1.25rem; align-items: end; }
                        #vtpForm .vtp-adv-row .vtp-field { flex: 1 1 200px; }

                        /* Toggle COD: canh giữa theo chiều cao ô input bên cạnh */
                        #vtpForm .vtp-switch { display: flex; align-items: center; min-height: 42px; margin: 0; }

                        /* Ghi chú dưới control */
                        #vtpForm .vtp-note { grid-column: 1 / -1; font-size: .85rem; color: var(--text-muted, #93A1A1); margin-top: .1rem; }

                        /* Nâng cao: card riêng, cách nhóm bắt buộc bằng 1 đường kẻ mảnh */
                        #vtpForm .vtp-divider { border: 0; border-top: 1px solid var(--border-default, #93A1A1); opacity: .45; margin: 1.75rem 0 1rem; }
                        #vtpForm .vtp-adv-toggle {
                            display: flex; align-items: center; gap: .25rem; width: 100%;
                            background: transparent; border: 0; border-radius: var(--radius-sm, .5rem);
                            padding: .6rem .5rem; text-align: left; color: var(--text-heading, #586E75);
                        }
                        #vtpForm .vtp-adv-toggle:hover { background: var(--color-primary-focus, rgba(38, 139, 210, .12)); }
                        #vtpForm .vtp-adv-toggle .chev { transition: transform .2s ease; }
                        #vtpForm .vtp-adv-toggle.collapsed .chev { transform: rotate(-90deg); }
                        #vtpForm .vtp-adv { background: #FFFDF6; border: 1px solid #E5E0D0; border-radius: var(--radius-sm, .6rem); padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 1rem; }

                        /* Lỗi chỉ tô đỏ sau khi đã rời ô / sau một lần bấm Lưu thất bại */
                        #vtpForm .is-invalid-boundary { border-color: #DC322F !important; box-shadow: 0 0 0 .2rem rgba(220, 50, 47, .12); background: #fff6f5; }
                        #vtpForm .vtp-help { color: #DC322F; font-size: .8rem; margin-top: .35rem; }

                        /* Khối 4 + nút Lưu: đặt giữa cho cân với toggle ở đầu */
                        #vtpForm .vtp-last { margin-top: 1.25rem; }
                        #vtpForm .vtp-save-row { display: flex; justify-content: center; margin-top: 1.25rem; }
                        #vtpForm .vtp-save-btn { min-width: 240px; }
                    </style>

                    <script>
                    (function () {
                        const form = document.getElementById('vtpForm');
                        if (!form) return;
                        const csrf = form.querySelector('[name=_token]').value;
                        const enabled = document.getElementById('vtp_enabled');
                        const steps = document.getElementById('vtpSteps');
                        const warnBox = document.getElementById('vtpWarnIncomplete');
                        const hdrBadge = document.getElementById('vtpHdrBadge');
                        const tokenInput = document.getElementById('vtp_token');
                        const provSel = document.getElementById('vtp_sender_province_id');
                        const distSel = document.getElementById('vtp_sender_district_id');
                        const provName = document.getElementById('vtp_sender_province_name');
                        const distName = document.getElementById('vtp_sender_district_name');
                        const testBtn = document.getElementById('vtpTestBtn');
                        const testRes = document.getElementById('vtpTestResult');
                        const saveBtn = document.getElementById('vtpSaveBtn');
                        const distErr = document.getElementById('vtpDistErr');
                        const tokenHelp = document.getElementById('vtpTokenHelp');
                        const senderHelp = document.getElementById('vtpSenderHelp');
                        const hasTokenSaved = <?= ($vtp['has_token'] ?? false) ? 'true' : 'false' ?>;
                        const savedProvince = <?= (int)($vtp['sender']['province_id'] ?? 0) ?>;
                        const savedDistrict = <?= (int)($vtp['sender']['district_id'] ?? 0) ?>;

                        // Chỉ tô đỏ sau khi người dùng đã rời ô (blur) hoặc sau một lần bấm Lưu thất bại
                        const touched = { token: false, sender: false };

                        function tokenEntered() { return tokenInput.value.trim().length > 0 || hasTokenSaved; }
                        function senderReady() {
                            const pv = parseInt(provSel.value, 10) || 0;
                            const dv = parseInt(distSel.value, 10) || 0;
                            return pv > 0 && dv > 0;
                        }
                        const GREEN_ICON = `<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="10" fill="#22c55e"/><path d="M6 10L9 13L14 7" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
                        const RED_ICON = `<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="10" fill="#ef4444"/><path d="M7 7L13 13M13 7L7 13" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/></svg>`;
                        function setBadge(iconHtml) { hdrBadge.innerHTML = iconHtml; }
                        function setDistNote(msg) { distErr.textContent = msg || ''; distErr.classList.toggle('d-none', !msg); }
                        function markMissing() {
                            if (!enabled.checked) {
                                tokenInput.classList.remove('is-invalid-boundary');
                                provSel.classList.remove('is-invalid-boundary');
                                distSel.classList.remove('is-invalid-boundary');
                                tokenHelp.classList.add('d-none');
                                senderHelp.classList.add('d-none');
                                return;
                            }
                            const showTok = touched.token && !tokenEntered();
                            const showSnd = touched.sender && !senderReady();
                            tokenInput.classList.toggle('is-invalid-boundary', showTok);
                            provSel.classList.toggle('is-invalid-boundary', showSnd);
                            distSel.classList.toggle('is-invalid-boundary', showSnd);
                            tokenHelp.classList.toggle('d-none', !showTok);
                            senderHelp.classList.toggle('d-none', !showSnd);
                        }
                        function markTouched(key) { touched[key] = true; markMissing(); }
                        function refreshStatus() {
                            const on = enabled.checked;
                            steps.classList.toggle('d-none', !on);
                            if (on) {
                                const ok = setupReady();
                                warnBox.classList.toggle('d-none', ok);
                                setBadge(ok ? GREEN_ICON : RED_ICON);
                                markMissing();
                            } else {
                                setBadge(RED_ICON);
                            }
                        }
                        function setupReady() { return tokenEntered() && senderReady() && !(provSel.disabled); }

                        function postForm(extra, isForm) {
                            const fd = isForm ? new FormData(form) : new FormData();
                            Object.keys(extra || {}).forEach(function (k) { fd.set(k, extra[k]); });
                            return fetch(form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Phản hồi không hợp lệ.' }; }); });
                        }

                        function loadProvinces() {
                            return postForm({ _token: csrf, _ajax: '1', sub: 'vtp_provinces' }).then(function (data) {
                                if (!data || !data.ok || !Array.isArray(data.provinces)) return;
                                const cur = provSel.value;
                                provSel.innerHTML = '<option value="">— chọn tỉnh/thành —</option>';
                                data.provinces.forEach(function (p) {
                                    const o = new Option(p.name, String(p.id));
                                    if (String(p.id) === cur) o.selected = true;
                                    provSel.add(o);
                                });
                                provSel.disabled = data.provinces.length === 0;
                                provName.value = provSel.selectedOptions[0] && provSel.value !== '' ? provSel.selectedOptions[0].text : '';
                                markMissing();
                            });
                        }

                        function loadDistricts(pid) {
                            const choose = '<option value="">— chọn quận/huyện —</option>';
                            setDistNote('');
                            if (!pid) {
                                distSel.innerHTML = choose;
                                distSel.disabled = true;
                                distName.value = '';
                                return;
                            }
                            distSel.innerHTML = '<option value="">Đang tải…</option>';
                            distSel.disabled = true;
                            distName.value = '';
                            postForm({ _token: csrf, _ajax: '1', sub: 'vtp_districts', province_id: String(pid) }).then(function (data) {
                                distSel.innerHTML = choose;
                                if (!data || !data.ok) {
                                    setDistNote((data && data.message) || 'Không tải được danh sách Quận/Huyện.');
                                } else {
                                    (data.districts || []).forEach(function (d) {
                                        const o = new Option(d.name, String(d.id));
                                        if (Number(pid) === savedProvince && Number(d.id) === savedDistrict) o.selected = true;
                                        distSel.add(o);
                                    });
                                    distSel.disabled = (data.districts || []).length === 0;
                                    distName.value = distSel.selectedOptions[0] && distSel.value !== '' ? distSel.selectedOptions[0].text : '';
                                }
                                markMissing();
                            }).catch(function () {
                                distSel.innerHTML = choose;
                                distSel.disabled = true;
                                setDistNote('Lỗi mạng, không tải được Quận/Huyện.');
                            });
                        }

                        function runTest() {
                            testRes.innerHTML = '<span class="text-muted"><span class="spinner-border spinner-border-sm me-1"></span>Đang kiểm tra kết nối…</span>';
                            postForm({ sub: 'vtp_test', _ajax: '1' }, true).then(function (data) {
                                const ok = !!(data && data.ok);
                                const msg = (data && data.message) || (ok ? '' : 'Token sai hoặc hết hạn, vui lòng kiểm tra lại.');
                                testRes.innerHTML = ok
                                    ? '<span class="text-success"><?= icon('bi-check-circle', 'me-1') ?>Kết nối thành công.</span>'
                                    : '<span class="text-danger"><?= icon('bi-x-circle', 'me-1') ?>' + msg.replace(/</g, '&lt;') + '</span>';
                                if (ok) loadProvinces();
                                refreshStatus();
                            }).catch(function () {
                                testRes.innerHTML = '<span class="text-danger"><?= icon('bi-x-circle', 'me-1') ?>Lỗi mạng, không kết nối được máy chủ.</span>';
                            });
                        }

                        enabled.addEventListener('change', refreshStatus);
                        testBtn.addEventListener('click', runTest);

                        tokenInput.addEventListener('blur', function () { markTouched('token'); });
                        provSel.addEventListener('blur', function () { markTouched('sender'); });
                        distSel.addEventListener('blur', function () { markTouched('sender'); });

                        provSel.addEventListener('change', function () {
                            provName.value = provSel.selectedOptions[0] && provSel.value !== '' ? provSel.selectedOptions[0].text : '';
                            loadDistricts(provSel.value ? provSel.value : '0');
                            markMissing();
                        });
                        distSel.addEventListener('change', function () {
                            distName.value = distSel.selectedOptions[0] && distSel.value !== '' ? distSel.selectedOptions[0].text : '';
                            markMissing();
                        });

                        form.querySelectorAll('[data-sub]').forEach(function (btn) {
                            btn.addEventListener('click', function () {
                                form.querySelector('[name=sub]').value = btn.dataset.sub;
                                form.submit();
                            });
                        });

                        form.addEventListener('submit', function (ev) {
                            if (!enabled.checked) return;
                            if (!setupReady()) {
                                ev.preventDefault();
                                warnBox.classList.remove('d-none');
                                touched.token = touched.sender = true;
                                markMissing();
                                steps.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                return;
                            }
                            tokenInput.classList.remove('is-invalid-boundary');
                            provSel.classList.remove('is-invalid-boundary');
                            distSel.classList.remove('is-invalid-boundary');
                        });

                        // Khởi tạo lần đầu
                        if (!provSel.options.length && hasTokenSaved) loadProvinces();
                        refreshStatus();
                    })();
                    </script>
                <?php else: ?>
                    <div class="small">
                        <div><strong>Trạng thái:</strong> <?= $__vtpReady ? '<span style="display:inline-flex;align-items:center;gap:.35rem"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="10" fill="#22c55e"/><path d="M6 10L9 13L14 7" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg> Đang hoạt động</span>' : ($__vtpEnabled ? '<span style="display:inline-flex;align-items:center;gap:.35rem"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="10" fill="#ef4444"/><path d="M7 7L13 13M13 7L7 13" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/></svg> Chưa cấu hình xong</span>' : '<span style="display:inline-flex;align-items:center;gap:.35rem"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="10" fill="#ef4444"/><path d="M7 7L13 13M13 7L7 13" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/></svg> Đang tắt</span>') ?></div>
                        <div><strong>Token:</strong> <?= ($vtp['has_token'] ?? false) ? 'Đã lưu' : 'Chưa nhập' ?></div>
                        <div><strong>Kho gửi:</strong> <?= e($vtp['sender']['province_name'] ?? '') ?> / <?= e($vtp['sender']['district_name'] ?? '') ?></div>
                        <div><strong>Hình thức giao:</strong> <?= e($__svcMain[$vtp['service'] ?? ''] ?? $__svcOther[$vtp['service'] ?? ''] ?? 'Tự động (rẻ nhất)') ?> — Loại hàng: <?= e($__ptLabel[$vtp['product_type'] ?? 'HH'] ?? 'Hàng hóa') ?> <?= ($vtp['cod_enabled'] ?? false) ? '/ COD' : '' ?></div>
                        <div><strong>Phụ thu:</strong> <?= __fm((float)($vtp['surcharge'] ?? 0)) ?> đ — <strong>Ngưỡng riêng:</strong> <?= (float)($vtp['freeship_threshold'] ?? 0) > 0 ? __fm((float)$vtp['freeship_threshold']) . ' đ' : 'dùng ngưỡng chung (' . __fm($__th) . ' đ)' ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ============ NHÓM 4: PHÍ LẮP ĐẶT ============ -->
    <div class="accordion-item admin-card border-0 mb-2">
        <h2 class="accordion-header" id="head-lapdat">
            <button class="accordion-button collapsed px-3 py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-lapdat" aria-expanded="false" aria-controls="collapse-lapdat">
                <span class="fw-semibold"><?= icon('bi-wrench-adjustable', 'me-2', 'style="color:var(--color-primary)"') ?>Phí lắp đặt</span>
            </button>
        </h2>
        <div id="collapse-lapdat" class="accordion-collapse collapse" data-bs-parent="#shippingAccordion" aria-labelledby="head-lapdat">
            <div class="accordion-body px-3 py-3">
                <style>
                    #collapse-lapdat .install-fee-label { display: block; line-height: 1.5; min-height: calc(3em + .5rem); }
                    #collapse-lapdat .install-fee-foot { min-height: 2.2em; }
                </style>
                <p class="small text-muted mb-3">Phí lắp đặt ưu tiên tìm theo sản phẩm → danh mục → mặc định cửa hàng (đặt ở đây). Để trống = không áp dụng phí mặc định. Mục này cũng cho phép đặt tên hiển thị cho ô tích chọn trên trang thanh toán.</p>
                <?php if ($isSuper): ?>
                    <form method="post" action="<?= BASE_URL ?>/quan-tri/van-chuyen">
                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="sub" value="options">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label mb-1 install-fee-label">Nhãn tick khi chọn lắp đặt</label>
                                <input type="text" name="setting_ship_label_install" class="form-control form-control-sm" value="<?= e((string)get_setting('ship_label_install', 'Lắp đặt tại nhà')) ?>">
                                <div class="form-text install-fee-foot">Ví dụ: "Lắp đặt tại nhà" — hiện kèm phí khi khách chọn Loại 1.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1 install-fee-label">Phí lắp đặt mặc định (VNĐ)</label>
                                <input type="number" min="0" step="1000" name="setting_ship_default_install_fee" class="form-control form-control-sm" value="<?= (float)get_setting('ship_default_install_fee', 0) > 0 ? e((string)(float)get_setting('ship_default_install_fee', 0)) : '' ?>" placeholder="0 = không áp dụng">
                                <div class="form-text install-fee-foot">Dùng khi sản phẩm/danh mục chưa nhập phí riêng.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1 install-fee-label">&nbsp;</label>
                                <button class="btn btn-primary btn-sm" type="submit"><?= icon('bi-check2', 'me-1') ?>Lưu cài đặt lắp đặt</button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="small mb-1"><strong>Nhãn tick hiện tại:</strong> <?= e((string)get_setting('ship_label_install', 'Lắp đặt tại nhà')) ?></div>
                    <div class="small"><strong>Phí lắp đặt mặc định:</strong> <?= (float)get_setting('ship_default_install_fee', 0) > 0 ? __fm((float)get_setting('ship_default_install_fee', 0)) . ' đ' : 'Chưa đặt (0)' ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>
