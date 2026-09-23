<?php
/** Danh sách đơn hàng admin */
declare(strict_types=1);
use WoodCon\Order;
$colors = ['pending'=>'warning','manual_verifying'=>'danger','confirmed'=>'info','preparing'=>'secondary','shipping'=>'primary','delivery_failed'=>'dark','delivered'=>'success','returned'=>'danger','cancelled'=>'secondary'];
$icons  = [
    'pending'          => 'bi-clock-history',
    'manual_verifying' => 'bi-shield-exclamation',
    'confirmed'        => 'bi-check2-circle',
    'preparing'        => 'bi-box-seam',
    'shipping'         => 'bi-truck',
    'delivery_failed'  => 'bi-exclamation-triangle',
    'delivered'        => 'bi-check-circle',
    'returned'         => 'bi-arrow-counterclockwise',
    'cancelled'        => 'bi-x-circle',
];
$orderStats ??= ['total' => 0, 'status' => [], 'cod' => 0, 'transfer' => 0];
$iconText = [
    'warning' => 'text-warning', 'danger' => 'text-danger', 'info' => 'text-info',
    'secondary' => 'text-secondary', 'primary' => 'text-primary', 'dark' => 'text-dark', 'success' => 'text-success',
];
?>			
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0">Đơn hàng</h1>
    </div>
    <?php $exportQ = http_build_query(array_filter([
        'q' => $f['q'], 'status' => $f['status'], 'payment' => $f['payment'], 'payment_status' => $f['payment_status'],
    ])); ?>
    <div class="dropdown">
        <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"><?= icon('bi-download', 'me-1') ?>Xuất dữ liệu</button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/quan-tri/don-hang/xuat?<?= e($exportQ) ?>&export=csv"><?= icon('bi-filetype-csv', 'me-1') ?>CSV</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/quan-tri/don-hang/xuat?<?= e($exportQ) ?>&export=xlsx"><?= icon('bi-file-earmark-excel', 'me-1') ?>Excel (.xlsx)</a></li>
        </ul>
    </div>
</div>

<!-- ===== STAT CARDS ===== -->
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Tổng đơn hàng</div>
                    <div class="stat-value mt-1"><?= (int)$orderStats['total'] ?></div>
                </div>
                <?= icon('bi-receipt', 'stat-icon') ?>
            </div>
        </div>
    </div>
    <?php foreach (Order::STATUS_LABEL as $__st => $__lb): ?>
        <div class="col-6 col-xl-3">
            <div class="admin-stat">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="stat-label"><?= e($__lb) ?></div>
                        <div class="stat-value mt-1"><?= (int)($orderStats['status'][$__st] ?? 0) ?></div>
                    </div>
                    <?= icon($icons[$__st] ?? 'bi-dot', 'stat-icon ' . ($iconText[$colors[$__st]] ?? '')) ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">COD</div>
                    <div class="stat-value mt-1 text-warning"><?= (int)$orderStats['cod'] ?></div>
                </div>
                <?= icon('bi-cash-coin', 'stat-icon text-warning') ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="stat-label">Chuyển khoản</div>
                    <div class="stat-value mt-1 text-primary"><?= (int)$orderStats['transfer'] ?></div>
                </div>
                <?= icon('bi-bank', 'stat-icon text-primary') ?>
            </div>
        </div>
    </div>
</div>

<div class="admin-card mb-3">
    <div class="card-body">
        <form class="row g-2" method="get">
            <div class="col-md-4">
                <input type="text" class="form-control form-control-sm" name="q" placeholder="Mã đơn, tên, SĐT khách" value="<?= e($f['q']) ?>">
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="status">
                    <option value="">Tất cả trạng thái</option>
                    <?php foreach (Order::STATUS_LABEL as $__st => $__lb): ?>
                        <option value="<?= e($__st) ?>" <?= $f['status'] === $__st ? 'selected' : '' ?>><?= e($__lb) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="payment">
                    <option value="">Phương thức</option>
                    <option value="cod" <?= $f['payment']==='cod'?'selected':'' ?>>COD</option>
                    <option value="bank" <?= $f['payment']==='bank'?'selected':'' ?>>Bank chuyển khoản</option>
                    <option value="qr" <?= $f['payment']==='qr'?'selected':'' ?>>QR</option>
                    <option value="wallet" <?= $f['payment']==='wallet'?'selected':'' ?>>Ví</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="payment_status">
                    <option value="">Thanh toán</option>
                    <option value="unpaid" <?= $f['payment_status']==='unpaid'?'selected':'' ?>>Chưa thanh toán</option>
                    <option value="paid" <?= $f['payment_status']==='paid'?'selected':'' ?>>Đã thanh toán</option>
                    <option value="refunded" <?= $f['payment_status']==='refunded'?'selected':'' ?>>Đã hoàn tiền</option>
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-outline-primary btn-sm">Lọc</button></div>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th>Mã đơn</th><th>Khách hàng</th><th>Phương thức</th><th class="money">Tổng</th>
                    <th class="text-center">COD Trust</th><th>Trạng thái</th><th>Thời gian</th><th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8"><div class="empty-state"><?= icon('bi-receipt') ?>Không có đơn hàng</div></td></tr>
                <?php else: foreach ($rows as $__o): ?>
                    <tr>
                        <td data-label="Mã đơn"><a href="<?= BASE_URL ?>/quan-tri/don-hang/xem/<?= e($__o['order_code']) ?>" class="fw-semibold text-decoration-none"><?= e($__o['order_code']) ?></a>
                            <?php if ($__o['risk_flag']): ?><?= icon('bi-shield-exclamation', 'text-danger', 'title="Cờ rủi ro"') ?><?php endif; ?>
                        </td>
                        <td data-label="Khách hàng">
                            <div class="fw-semibold small"><?= e($__o['customer_name']) ?></div>
                            <div class="text-muted small"><?= e($__o['customer_phone']) ?></div>
                        </td>
                        <td data-label="Phương thức"><span class="badge text-bg-light"><?= strtoupper($__o['payment_method']) ?></span></td>
                        <td data-label="Tổng" class="money fw-semibold"><?= format_money((int)$__o['total_amount']) ?></td>
                        <td data-label="COD Trust" class="text-center">
                            <?php $__t = $__o['trust_level_at_order'];
                            if ($__t === 'green'): ?><span class="badge text-bg-success">Xanh</span>
                            <?php elseif ($__t === 'yellow'): ?><span class="badge text-bg-warning">Vàng</span>
                            <?php elseif ($__t === 'red'): ?><span class="badge text-bg-danger">Đỏ</span>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                        <td data-label="Trạng thái"><span class="badge text-bg-<?= $colors[$__o['order_status']] ?? 'secondary' ?>"><?= e(Order::STATUS_LABEL[$__o['order_status']] ?? $__o['order_status']) ?></span></td>
                        <td data-label="Thời gian" class="text-muted small"><?= format_date($__o['created_at']) ?></td>
                        <td data-label="Thao tác" class="text-end text-nowrap">
                            <a href="<?= BASE_URL ?>/quan-tri/don-hang/xem/<?= e($__o['order_code']) ?>" class="btn btn-sm btn-light"><?= icon('bi-eye') ?></a>
                            <?php if (\WoodCon\Permission::isSuper() && $__o['order_status'] === 'cancelled'): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" title="Xóa cứng đơn đã hủy (Chủ hệ thống)" data-hard-delete="<?= e($__o['order_code']) ?>"><?= icon('bi-trash3') ?></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/includes/partials/pagination.php'; ?>

<?php if (\WoodCon\Permission::isSuper()): ?>
<div class="modal fade" id="hardDeleteModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><?= icon('bi-exclamation-triangle', 'me-1') ?>Xóa cứng đơn đã hủy</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Bạn sắp <strong>XÓA HOÀN TOÀN</strong> đơn <strong id="hdCode" class="text-danger">—</strong> và toàn bộ dữ liệu liên quan (sản phẩm, bảo hành, hoàn tiền).</p>
                <p class="small text-muted mb-3">Thao tác này <strong>không thể hoàn tác</strong>. Chỉ đơn ở trạng thái <span class="badge text-bg-secondary">Đã hủy</span> và chưa phát sinh hóa đơn mới được xóa.</p>
                <label class="form-label small text-muted">Nhập <strong>mật khẩu Chủ hệ thống</strong> để xác nhận *</label>
                <input type="password" id="hdPassword" class="form-control" autocomplete="off" placeholder="Mật khẩu của bạn">
                <input type="hidden" id="hdToken" value="<?= csrf_token() ?>">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="hdConfirm" disabled><?= icon('bi-trash3', 'me-1') ?>Xóa hoàn toàn</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const modalEl = document.getElementById('hardDeleteModal');
  const passEl = document.getElementById('hdPassword');
  const codeEl = document.getElementById('hdCode');
  const tokenEl = document.getElementById('hdToken');
  const confirmBtn = document.getElementById('hdConfirm');
  const modal = new bootstrap.Modal(modalEl);
  let currentCode = null;

  document.querySelectorAll('[data-hard-delete]').forEach(btn => {
    btn.addEventListener('click', function () {
      currentCode = this.dataset.hardDelete;
      codeEl.textContent = currentCode;
      passEl.value = '';
      confirmBtn.disabled = true;
      modal.show();
    });
  });

  passEl.addEventListener('input', () => (confirmBtn.disabled = passEl.value.trim().length < 6));

  confirmBtn.addEventListener('click', function () {
    const pw = passEl.value.trim();
    if (pw.length < 6) return;
    this.disabled = true; this.textContent = 'Đang xóa...';
    const fd = new FormData();
    fd.append('_token', tokenEl.value);
    fd.append('super_password', pw);
    fetch(<?= json_encode(BASE_URL) ?> + '/quan-tri/don-hang/xoa-huy/' + encodeURIComponent(currentCode), {
      method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => {
      alert(res.message || 'Xong');
      if (res.ok) { modal.hide(); location.reload(); }
      else if (res.status === 403) { confirmBtn.disabled = false; confirmBtn.textContent = 'Xóa hoàn toàn'; }
    })
    .catch(() => { alert('Lỗi kết nối. Vui lòng thử lại.'); confirmBtn.disabled = false; confirmBtn.textContent = 'Xóa hoàn toàn'; });
  });
});
</script>
<?php endif; ?>