<?php
/** Điểm thưởng & hạng thành viên */
declare(strict_types=1);
$__next = $nextTier;
?>
<div class="container py-4">
    <div class="row g-4">
        <aside class="col-lg-3">
            <div class="app-filter-card">
                <ul class="nav flex-column p-2 small">
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/tai-khoan">Thông tin</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/tai-khoan/don-hang">Đơn hàng</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/tai-khoan/yeu-thich">Yêu thích</a></li>
                    <li class="nav-item"><a class="nav-link active" href="<?= BASE_URL ?>/tai-khoan/diem-thuong">Điểm thưởng</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/tai-khoan/doi-mat-khau">Đổi mật khẩu</a></li>
                </ul>
            </div>
        </aside>

        <div class="col-lg-9">
            <h1 class="app-section-title mb-3">Điểm thưởng và hạng thành viên</h1>

            <div class="row g-3">
                <!-- Số dư -->
                <div class="col-md-4">
                    <div class="app-point-balance p-4 rounded">
                        <div class="small text-muted mb-1">Số dư điểm</div>
                        <div class="display-6 fw-bold" style="color:var(--wc-secondary)"><?= number_format($balance) ?></div>
                        <div class="small text-muted"><?= '1 điểm = ' . format_money($pointValue) ?></div>
                    </div>
                </div>
                <!-- Hạng hiện tại -->
                <div class="col-md-4">
                    <div class="app-filter-card p-4 h-100">
                        <div class="small text-muted mb-1">Hạng hiện tại</div>
                        <div class="h4 fw-bold mb-1"><?= e($tier['name'] ?? 'Thành viên') ?></div>
                        <?php if (!empty($tier['discount_percent'])): ?>
                            <div class="small text-success">Giảm <?= (float)$tier['discount_percent'] ?>% cho đơn hàng</div>
                        <?php else: ?>
                            <div class="small text-muted">Chưa có ưu đãi hạng</div>
                        <?php endif; ?>
                        <?php if (!empty($tier['membership_expired'])): ?>
                            <div class="small text-muted">Hạng có hiệu lực đến <?= date('d/m/Y', strtotime($tier['membership_expired'])) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Hạng kế tiếp -->
                <div class="col-md-4">
                    <div class="app-filter-card p-4 h-100">
                        <?php if ($__next): ?>
                            <div class="small text-muted mb-1">Hạng kế tiếp</div>
                            <div class="h4 fw-bold mb-1"><?= e($__next['name']) ?></div>
                            <div class="small text-muted">Cần chi tiêu thêm <?= format_money((int)$__next['min_total_spent']) ?> để lên hạng</div>
                            <div class="small text-muted">Tích điểm x<?= (float)$__next['points_multiplier'] ?></div>
                        <?php else: ?>
                            <div class="small text-muted mb-1">Hạng kế tiếp</div>
                            <div class="h4 fw-bold mb-1">Bạn đã đạt hạng cao nhất</div>
                            <div class="small text-muted">Chúc mừng! Bạn đang ở hạng tối đa</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Lịch sử điểm</h5>
                    <?php if (empty($history)): ?>
                        <div class="app-empty py-4">Chưa có lịch sử giao dịch điểm</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle small mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>Thời gian</th>
                                    <th>Nội dung</th>
                                    <th class="text-end">Điểm</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($history as $__h): ?>
                                    <tr>
                                        <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime($__h['created_at']))) ?></td>
                                        <td>
                                            <?= e($__h['note'] ?? '') ?>
                                            <?php if (!empty($__h['order_code'])): ?>
                                                <span class="text-muted">(#<?= e($__h['order_code']) ?>)</span>
                                            <?php endif; ?>
                                            <span class="badge bg-secondary-subtle text-secondary ms-1"><?= e($__h['type']) ?></span>
                                        </td>
                                        <td class="text-end fw-semibold <?= (int)$__h['points_change'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= (int)$__h['points_change'] >= 0 ? '+' : '' ?><?= number_format((int)$__h['points_change']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php require BASE_PATH . '/includes/partials/pagination.php'; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>