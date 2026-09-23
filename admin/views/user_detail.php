<?php
/** Chi tiết khách hàng admin */
declare(strict_types=1);
use WoodCon\Order;
$colors = ['pending'=>'warning','manual_verifying'=>'danger','confirmed'=>'info','preparing'=>'secondary','shipping'=>'primary','delivery_failed'=>'dark','delivered'=>'success','returned'=>'danger','cancelled'=>'secondary'];
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 admin-page-head">
    <div>
        <h1 class="h4 admin-page-title mb-0"><?= e($user['name']) ?></h1>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/quan-tri/khach-hang" class="btn btn-sm btn-light"><?= icon('bi-arrow-left', 'me-1') ?>Quay lại</a>
        <a href="<?= BASE_URL ?>/quan-tri/khach-hang/khoa/<?= (int)$user['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Khóa/mở khóa tài khoản này?">
            <?= $user['status'] ? 'Khóa tài khoản' : 'Mở khóa' ?>
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="admin-card mb-3">
            <div class="card-head">Thông tin tài khoản</div>
            <div class="card-body small">
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Họ tên</span><span class="fw-semibold"><?= e($user['name']) ?></span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Email</span><span><?= e($user['email']) ?></span></div>
                <?php if ($user['phone']): ?><div class="d-flex justify-content-between py-1"><span class="text-muted">SĐT</span><span><?= e($user['phone']) ?></span></div><?php endif; ?>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Điểm tích lũy</span><span class="fw-bold"><?= (int)$user['points'] ?></span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Trạng thái</span>
                    <span class="badge <?= $user['status'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $user['status'] ? 'Hoạt động' : 'Khóa' ?></span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">OTP đang hiệu lực</span><span><?= !empty($user['otp_expired']) && strtotime($user['otp_expired']) > time() ? 'Có' : 'Không' ?></span></div>
            </div>
        </div>
        <?php if (!empty($user['note'])): ?>
            <div class="admin-card"><div class="card-head">Ghi chú nội bộ</div><div class="card-body small"><?= e($user['note']) ?></div></div>
        <?php endif; ?>
    </div>

    <div class="col-lg-8">
        <div class="admin-card">
            <div class="card-head">Đơn hàng (<?= count($orders) ?>)</div>
            <div class="card-body p-0">
                <table class="table admin-table mb-0">
                    <thead><tr><th>Mã đơn</th><th class="money">Tổng</th><th>Trạng thái</th><th>Thanh toán</th><th class="text-end">Thời gian</th></tr></thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="5"><div class="empty-state"><?= icon('bi-receipt') ?>Khách chưa có đơn hàng</div></td></tr>
                        <?php else: foreach ($orders as $__o): ?>
                            <tr>
                                <td><a href="<?= BASE_URL ?>/quan-tri/don-hang/xem/<?= e($__o['order_code']) ?>" class="fw-semibold text-decoration-none"><?= e($__o['order_code']) ?></a></td>
                                <td class="money"><?= format_money((int)$__o['total_amount']) ?></td>
                                <td><span class="badge text-bg-<?= $colors[$__o['order_status']] ?? 'secondary' ?>"><?= e(Order::STATUS_LABEL[$__o['order_status']] ?? $__o['order_status']) ?></span></td>
                                <td><small><?= strtoupper((string)$__o['payment_method']) ?>
                                    <span class="badge text-bg-<?= $__o['payment_status']==='paid'?'success':'warning' ?>"><?= e($__o['payment_status']) ?></span></small></td>
                                <td class="text-end text-muted small"><?= format_date($__o['created_at']) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>