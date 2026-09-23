<?php
/** Quản lý mã khuyến mãi admin - nút Thêm/Sửa mở Modal */
declare(strict_types=1);
$edit = $edit;
$grouped = $grouped ?? [];
$typeCount = $typeCount ?? ['discount' => 0, 'freeship' => 0];
$statusCount = $statusCount ?? ['active' => 0, 'expired' => 0, 'out' => 0, 'turned_off' => 0];
$__tierById = [];
foreach (($tiers ?? []) as $__t) { $__tierById[(int)$__t['id']] = $__t['name']; }
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0">Khuyến mãi</h1>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#voucherModal" data-mode="create"><?= icon('bi-plus-lg', 'me-1') ?>Thêm mã</button>
</div>

<!-- Thống kê nhanh -->
<div class="row g-3 mt-1 mb-3">
    <div class="col-6 col-lg-3"><div class="admin-stat"><div class="stat-label">Đang chạy</div><div class="h5 mb-0 mt-1 text-success"><?= $statusCount['active'] ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="admin-stat"><div class="stat-label">Hết hạn</div><div class="h5 mb-0 mt-1 text-muted"><?= $statusCount['expired'] ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="admin-stat"><div class="stat-label">Giảm giá</div><div class="h5 mb-0 mt-1"><?= $typeCount['discount'] ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="admin-stat"><div class="stat-label">Freeship</div><div class="h5 mb-0 mt-1"><?= $typeCount['freeship'] ?></div></div></div>
</div>

<div class="admin-card mb-3">
    <div class="card-body">
        <form class="row g-2" method="get">
            <div class="col-md-6">
                <input type="text" class="form-control form-control-sm" name="q" placeholder="Tìm theo mã hoặc tên khuyến mãi" value="<?= e($q ?? '') ?>">
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-outline-primary btn-sm">Tìm</button></div>
            <?php if (!empty($q)): ?>
                <div class="col-12 small text-muted">Hiển thị kết quả cho "<strong><?= e($q) ?></strong>" — <a href="<?= BASE_URL ?>/quan-tri/voucher" class="text-decoration-none">Xóa lọc</a></div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="card-head d-flex align-items-center gap-2 flex-wrap">
        <ul class="nav nav-pills nav-sm voucher-tabs mb-0" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-discount" type="button" role="tab">
                    Giảm giá <span class="vtab-count"><?= $typeCount['discount'] ?></span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-freeship" type="button" role="tab">
                    Freeship <span class="vtab-count"><?= $typeCount['freeship'] ?></span>
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body p-0">
        <div class="tab-content">
            <?php foreach (['discount' => 'tab-discount', 'freeship' => 'tab-freeship'] as $__type => $__tabId): ?>
                <div class="tab-pane <?= $__type === 'discount' ? 'active' : '' ?>" id="<?= $__tabId ?>" role="tabpanel">
                    <?php if (empty($grouped[$__type]) || empty(array_filter($grouped[$__type]))): ?>
                        <?php if (!empty($q)): ?>
                            <div class="empty-state p-4"><?= icon('bi-search') ?>Không tìm thấy mã <?= $__type === 'discount' ? 'giảm giá' : 'freeship' ?> khớp khóa tìm kiếm</div>
                        <?php else: ?>
                            <div class="empty-state p-4"><?= icon('bi-ticket') ?>Chưa có mã <?= $__type === 'discount' ? 'giảm giá' : 'freeship' ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php foreach ($grouped[$__type] as $__monthKey => $__list): ?>
                            <?php $__label = $__list[0][1]; $__open = false; $__accId = 'vmonth-' . $__type . '-' . str_replace('|','-',$__monthKey); ?>
                            <div class="vmonth">
                                <button type="button" class="vmonth-head vacc-toggle" data-target="#<?= $__accId ?>" aria-expanded="<?= $__open ? 'true' : 'false' ?>">
                                    <span class="vacc-pm"><?= $__open ? '−' : '+' ?></span>
                                    <?= icon('bi-calendar3', 'me-1') ?>Tháng <?= e($__label) ?>
                                    <span class="vmonth-count"><?= count($__list) ?> mã</span>
                                </button>
                                <div class="vacc-body <?= $__open ? '' : 'd-none' ?>" id="<?= $__accId ?>">
                                <table class="table admin-table mb-0 vtable">
                                    <tbody>
                                        <?php foreach ($__list as $__pair): ?>
                                            <?php $__v = $__pair[0]; ?>
                                            <tr>
                                                <td data-label="Mã" class="vcode-cell">
                                                    <?php if ($__type === 'discount'): ?>
                                                        <span class="vcode-plain"><?= e($__v['code']) ?></span>
                                                    <?php else: ?>
                                                        <span class="vcode-freeship"><?= e($__v['code']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td data-label="Tên" class="vname-cell">
                                                    <div class="fw-semibold small"><?= e($__v['name']) ?></div>
                                                    <?php if (!empty($__v['description'])): ?><div class="text-muted small"><?= e($__v['description']) ?></div><?php endif; ?>
                                                    <?php if ((int)($__v['required_tier_id'] ?? 0) > 0 && isset($__tierById[(int)$__v['required_tier_id']])): ?>
                                                        <div class="mt-1"><span class="badge v-tier-badge"><?= icon('bi-award', 'me-1') ?>Hội viên <?= e($__tierById[(int)$__v['required_tier_id']]) ?> trở lên</span></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td data-label="Giá trị" class="vvalue-cell text-nowrap text-center small">
                                                    <?php if ($__type === 'discount'): ?>
                                                        <?php if ($__v['discount_type'] === 'fixed'): ?>
                                                            Giảm <?= format_money((int)$__v['discount_value']) ?>
                                                        <?php else: ?>
                                                            Giảm <?= (float)$__v['discount_value'] ?>%
                                                        <?php endif; ?>
                                                        <?php if ((float)$__v['max_discount'] > 0): ?><div class="text-muted">tối đa <?= format_money((int)$__v['max_discount']) ?></div><?php endif; ?>
                                                    <?php else: ?>
                                                        Miễn phí ship
                                                    <?php endif; ?>
                                                </td>
                                                <td data-label="Đã dùng/Tổng" class="text-center small"><?= (int)$__v['used_quantity'] ?>/<?= (int)$__v['total_quantity'] ?></td>
                                                <td data-label="Hạn" class="text-center small text-nowrap"><?= $__v['end_date'] ? format_date($__v['end_date'], 'd/m/Y') : '—' ?></td>
                                                <td data-label="Trạng thái" class="text-center">
                                                    <?php $__st = $__v['_status'] ?? ''; $__stMap = ['active' => ['text-bg-success', 'Hoạt động'], 'expired' => ['text-bg-secondary', 'Hết hạn'], 'out' => ['text-bg-warning text-dark', 'Hết lượt'], 'turned_off' => ['text-bg-light border text-muted', 'Tắt']]; $__sb = $__stMap[$__st] ?? ['text-bg-light border text-muted', 'Tắt']; ?>
                                                    <span class="badge <?= $__sb[0] ?>"><?= $__sb[1] ?></span>
                                                </td>
                                                <td data-label="Thao tác" class="text-end text-nowrap">
                                                    <button type="button" class="btn btn-sm btn-light btn-edit-voucher" data-bs-toggle="modal" data-bs-target="#voucherModal" data-mode="edit"
                                                        data-id="<?= (int)$__v['id'] ?>" data-code="<?= e($__v['code']) ?>" data-name="<?= e($__v['name']) ?>" data-description="<?= e($__v['description'] ?? '') ?>"
                                                        data-type="<?= e($__v['type']) ?>" data-discount_type="<?= e($__v['discount_type']) ?>" data-discount_value="<?= e($__v['discount_value']) ?>"
                                                        data-max_discount="<?= e($__v['max_discount'] ?? '') ?>" data-min_order_value="<?= e($__v['min_order_value']) ?>" data-total_quantity="<?= (int)$__v['total_quantity'] ?>"
                                                        data-per_user_limit="<?= (int)$__v['per_user_limit'] ?>" data-start_date="<?= e(substr((string)$__v['start_date'], 0, 10)) ?>" data-end_date="<?= e(substr((string)$__v['end_date'], 0, 10)) ?>" data-status="<?= (int)$__v['status'] ?>" data-required_tier="<?= (int)($__v['required_tier_id'] ?? 0) ?>"><?= icon('bi-pencil') ?></button>
                                                    <a href="<?= BASE_URL ?>/quan-tri/voucher/xoa/<?= (int)$__v['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Xóa mã này?"><?= icon('bi-trash') ?></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ============ MODAL THÊM / SỬA MÃ ============ -->
<div class="modal fade" id="voucherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post" action="<?= BASE_URL ?>/quan-tri/voucher/luu">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" id="vf_id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="voucherModalTitle"><?= icon('bi-ticket-fill', 'me-1') ?>Thêm mã mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <?php require __DIR__ . '/voucher_form.php'; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button class="btn btn-primary"><?= icon('bi-check2', 'me-1') ?>Lưu mã</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.voucher-tabs .nav-link{ font-size:.85rem; padding:.35rem .8rem; }
.voucher-tabs .nav-link.active{ background:var(--wc-primary,#8b5a2b); color:#fff; }
.vtab-count{ display:inline-block;min-width:1.2rem;text-align:center;background:color-mix(in srgb,currentColor 18%,transparent);border-radius:999px;font-size:.7rem;margin-left:.3rem;padding:0 .3rem; }
.voucher-tabs .nav-link.active .vtab-count{ background:rgba(255,255,255,.25); }
.vmonth{ border:1px solid var(--wc-border,#e5e5e5);border-radius:.7rem;margin:.6rem .75rem;overflow:hidden; }
.vmonth:first-child{ margin-top:.75rem; } .vmonth:last-child{ margin-bottom:.75rem; }
.vmonth-head{ display:flex;align-items:center;gap:.5rem;font-weight:600;font-size:.82rem;padding:.55rem .85rem;width:100%;
  background:color-mix(in srgb,var(--wc-primary,#8b5a2b) 5%,transparent);text-transform:uppercase;letter-spacing:.02em;color:var(--wc-muted);
  border:0;cursor:pointer;text-align:left;transition:background .15s ease; }
.vmonth-head:hover{ background:color-mix(in srgb,var(--wc-primary,#8b5a2b) 10%,transparent); }
.vacc-pm{ display:inline-flex;align-items:center;justify-content:center;width:1.15rem;height:1.15rem;flex:0 0 1.15rem;
  border-radius:.3rem;background:var(--wc-primary,#8b5a2b);color:#fff;font-weight:700;font-size:.8rem;line-height:1; }
.vmonth-count{ margin-left:auto;font-weight:500;text-transform:none;letter-spacing:0; }
.vacc-body{ border-top:1px solid var(--wc-border,#e5e5e5); }
.vtable tbody tr{ border-bottom:1px solid var(--wc-border,#e5e5e5); } .vtable tbody tr:last-child{ border-bottom:0; }
.vcode-cell{ width:180px; } .vname-cell{ min-width:140px; }
.vcode-plain{ font-family:monospace;font-weight:600;font-size:.95rem;color:#212529;letter-spacing:.02em;text-transform:uppercase; }
.vcode-freeship{ font-family:monospace;font-weight:600;font-size:.85rem;color:#0a7136;text-transform:uppercase; }
.vvalue-cell{ color:#d63384;font-weight:600; }
.v-tier-badge{ background:color-mix(in srgb,var(--wc-primary,#8b5a2b) 12%,transparent);color:#6b4f24;font-weight:600;font-size:.68rem; }
@media (max-width:900px){ .vcode-cell{ width:130px; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('voucherModal');
  const title = document.getElementById('voucherModalTitle');
  const form = modal.querySelector('form');

  function setVal(id, v) { const el = document.getElementById('vf_' + id); if (el) el.value = v ?? ''; }
  const setStatus = v => { const c = document.getElementById('vf_status'); if (c) c.checked = !!Number(v); };

  function syncVoucherType(type) {
    const isFree = type === 'freeship';
    const valWrap = document.getElementById('vf_value_wrap');
    const typeWrap = document.getElementById('vf_discount_type_wrap');
    const extras = document.getElementById('vf_extras');
    if (valWrap) valWrap.style.display = isFree ? 'none' : '';
    if (typeWrap) typeWrap.style.display = isFree ? 'none' : '';
    if (extras) extras.style.display = isFree ? 'none' : '';
    const label = document.getElementById('vf_value_label');
    const dt = document.getElementById('vf_discount_type');
    if (label) label.textContent = (dt && dt.value === 'fixed') ? 'Số tiền giảm (đ)' : '% giảm';
  }

  // Nút THÊM: reset form về trống
  modal.addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    if (btn && btn.dataset.mode === 'edit') return; // chế độ sửa xử lý riêng bên dưới
    // create mode
    title.innerHTML = '<?= icon('bi-ticket-fill', 'me-1') ?>Thêm mã mới';
    form.reset();
    setVal('id', '0');
    setVal('start_date', '<?= date('Y-m-d') ?>');
    setVal('type', 'discount');
    setVal('discount_type', 'percent');
    setVal('per_user_limit', '1');
    setVal('total_quantity', '0');
    setVal('min_order_value', '0');
    setVal('required_tier', '0');
    setStatus(1);
    syncVoucherType('discount');
  });

  // Nút SỬA: điền dữ liệu mã vào form
  document.querySelectorAll('.btn-edit-voucher').forEach(btn => {
    btn.addEventListener('click', function () {
      title.innerHTML = '<?= icon('bi-pencil', 'me-1') ?>Sửa mã ' + this.dataset.code;
      setVal('id', this.dataset.id);
      setVal('code', this.dataset.code);
      setVal('name', this.dataset.name);
      setVal('description', this.dataset.description);
      setVal('type', this.dataset.type);
      setVal('discount_type', this.dataset.discount_type);
      setVal('discount_value', this.dataset.discount_value);
      setVal('max_discount', this.dataset.max_discount);
      setVal('min_order_value', this.dataset.min_order_value);
      setVal('total_quantity', this.dataset.total_quantity);
      setVal('per_user_limit', this.dataset.per_user_limit);
      setVal('start_date', this.dataset.start_date);
      setVal('end_date', this.dataset.end_date);
      setVal('required_tier', this.dataset.required_tier);
      setStatus(this.dataset.status);
      syncVoucherType(this.dataset.type);
    });
  });

  // Đổi loại mã tức thì + label kiểu giảm
  document.getElementById('vf_type').addEventListener('change', function () { syncVoucherType(this.value); });
  document.getElementById('vf_discount_type').addEventListener('change', function () {
    document.getElementById('vf_value_label').textContent = this.value === 'fixed' ? 'Số tiền giảm (đ)' : '% giảm';
  });

  // Accordion: bấm header để thu gọn / xổ danh sách mã theo tháng
  document.querySelectorAll('.vacc-toggle').forEach(toggle => {
    toggle.addEventListener('click', function () {
      const body = document.querySelector(this.dataset.target);
      const pm = this.querySelector('.vacc-pm');
      const open = body.classList.toggle('d-none') === false;
      this.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (pm) pm.textContent = open ? '−' : '+';
    });
  });
});
</script>
