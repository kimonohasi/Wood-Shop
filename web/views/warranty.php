<?php
/** Tra cứu bảo hành (không cần đăng nhập) */
declare(strict_types=1);
$__whStatus = ['active' => 'Đang bảo hành', 'expired' => 'Hết hạn', 'used' => 'Đã sử dụng', 'rejected' => 'Từ chối'];
?>
<div class="container py-4" style="max-width:860px">
    <div class="app-crumb"><a href="<?= BASE_URL ?>">Trang chủ</a><span class="sep">/</span><span>Tra cứu bảo hành</span></div>
    <h1 class="app-section-title mb-4">Tra cứu bảo hành</h1>

    <form class="row g-2 mb-4" method="post">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
        <div class="col-md-5">
            <input type="text" class="form-control" name="serial" placeholder="Số serial sản phẩm" value="<?= e($_GET['serial'] ?? $_POST['serial'] ?? '') ?>" required>
        </div>
        <div class="col-md-5">
            <input type="tel" class="form-control" name="phone" placeholder="Số điện thoại">
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-primary">Tra cứu</button>
        </div>
    </form>

    <?php if ($found): ?>
        <div class="alert alert-warning"><?= e($found) ?></div>
    <?php endif; ?>

    <?php if ($item): ?>
        <div class="summary-box p-4 mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <div class="fw-bold fs-5"><?= e($item['product_name']) ?></div>
                    <div class="small text-muted">Serial: <span class="fw-semibold text-body"><?= e($item['serial_no']) ?></span></div>
                    <div class="small text-muted">Đơn hàng: <?= e($item['order_code']) ?> · Mua ngày <?= format_date($item['purchase_date'] . ' 00:00:00') ?></div>
                </div>
                <span class="status-badge status-<?= e($item['status']) ?>">
                    <?= $__whStatus[$item['status']] ?? e($item['status']) ?>
                </span>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="small text-muted">Hạn bảo hành</div>
                        <div class="fw-bold <?= $item['status'] === 'active' ? 'text-success' : 'text-danger' ?>"><?= format_date($item['warranty_end'] . ' 00:00:00') ?></div>
                        <div class="small text-muted">(<?= (int)$item['warranty_months'] ?> tháng kể từ ngày mua)</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="small text-muted">Khách hàng</div>
                        <div class="fw-semibold"><?= e($item['customer_name']) ?></div>
                        <div class="small text-muted"><?= e($item['customer_phone'] ?? '') ?></div>
                    </div>
                </div>
            </div>

            <?php if (!empty($item['history'])): ?>
                <hr class="divider-dash">
                <div class="fw-semibold mb-2">Lịch sử bảo hành</div>
                <?php foreach ($item['history'] as $__h): ?>
                    <div class="d-flex gap-3 py-2">
                        <?= icon('ms-build', 'text-muted mt-1') ?>
                        <div class="flex-grow-1">
                            <div class="small"><?= nl2br(e($__h['description'])) ?></div>
                            <div class="small text-muted"><?= format_date($__h['created_at']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>