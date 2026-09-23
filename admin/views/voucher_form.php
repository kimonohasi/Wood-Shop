<?php
/** Form voucher (dùng trong modal Thêm/Sửa) - chỉ render các field, không có <form>/nút submit */
declare(strict_types=1);
$v = $edit;
$isFree = ($v['type'] ?? 'discount') === 'freeship';
?>
<div class="row g-2">
    <div class="col-md-6 mb-3">
        <label class="form-label small text-muted">Mã code *</label>
        <input type="text" class="form-control text-uppercase" name="code" id="vf_code" required value="<?= e($v['code'] ?? '') ?>" placeholder="VD: WCD10">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label small text-muted">Tên *</label>
        <input type="text" class="form-control" name="name" id="vf_name" required value="<?= e($v['name'] ?? '') ?>">
    </div>
</div>
<div class="mb-3">
    <label class="form-label small text-muted">Mô tả</label>
    <input type="text" class="form-control" name="description" id="vf_description" value="<?= e($v['description'] ?? '') ?>">
</div>
<div class="row g-2">
    <div class="col-md-6 mb-3">
        <label class="form-label small text-muted">Loại mã</label>
        <select class="form-select" name="type" id="vf_type">
            <option value="discount" <?= !$isFree ? 'selected' : '' ?>>Giảm giá</option>
            <option value="freeship" <?= $isFree ? 'selected' : '' ?>>Miễn phí vận chuyển</option>
        </select>
    </div>
    <div class="col-md-6 mb-3" id="vf_discount_type_wrap">
        <label class="form-label small text-muted">Kiểu giảm</label>
        <select class="form-select" name="discount_type" id="vf_discount_type">
            <option value="percent" <?= ($v['discount_type'] ?? 'percent') === 'percent' ? 'selected' : '' ?>>Theo %</option>
            <option value="fixed" <?= ($v['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Số tiền cố định (đ)</option>
        </select>
    </div>
</div>
<div class="row g-2">
    <div class="col-md-6 mb-3" id="vf_value_wrap">
        <label class="form-label small text-muted" id="vf_value_label">% giảm</label>
        <input type="number" step="0.01" min="0" max="100" class="form-control" name="discount_value" id="vf_discount_value" value="<?= e($v['discount_value'] ?? '') ?>">
        <div class="form-text small text-danger">Không giảm quá 50% giá niêm yết.</div>
    </div>
    <div class="col-md-6 mb-3" id="vf_extras">
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label small text-muted">Giảm tối đa</label>
                <input type="number" step="0.01" min="0" class="form-control" name="max_discount" id="vf_max_discount" value="<?= e($v['max_discount'] ?? '') ?>">
            </div>
            <div class="col-6">
                <label class="form-label small text-muted">Đơn tối thiểu</label>
                <input type="number" step="0.01" min="0" class="form-control" name="min_order_value" id="vf_min_order_value" value="<?= e($v['min_order_value'] ?? '0') ?>">
            </div>
        </div>
    </div>
</div>
<div class="row g-2">
    <div class="col-md-6 mb-3">
        <label class="form-label small text-muted">Tổng số lượt (0 = không giới hạn)</label>
        <input type="number" min="0" class="form-control" name="total_quantity" id="vf_total_quantity" value="<?= (int)($v['total_quantity'] ?? 0) ?>">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label small text-muted">Giới hạn/user</label>
        <input type="number" min="1" class="form-control" name="per_user_limit" id="vf_per_user_limit" value="<?= (int)($v['per_user_limit'] ?? 1) ?>">
    </div>
</div>
<div class="row g-2">
    <div class="col-md-6 mb-3">
        <label class="form-label small text-muted">Hiệu lực từ</label>
        <input type="date" class="form-control" name="start_date" id="vf_start_date" value="<?= e($v['start_date'] ?? date('Y-m-d')) ?>">
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label small text-muted">Đến hết</label>
        <input type="date" class="form-control" name="end_date" id="vf_end_date" value="<?= e($v ? ($v['end_date'] ? substr((string)$v['end_date'], 0, 10) : '') : '') ?>">
    </div>
</div>
<div class="form-check form-switch toggle-row mb-1">
    <input class="form-check-input" type="checkbox" name="status" id="vf_status" <?= ($v['status'] ?? 1) ? 'checked' : '' ?>>
    <label class="form-check-label small" for="vf_status">Hoạt động</label>
</div>
<div class="mb-2">
    <label class="form-label small text-muted">Yêu cầu hội viên (đúng cấp trở lên)</label>
    <?php $__tiers = $tiers ?? []; $__reqTier = (int)($v['required_tier_id'] ?? 0); ?>
    <select class="form-select form-select-sm" name="required_tier_id" id="vf_required_tier">
        <option value="0" <?= $__reqTier === 0 ? 'selected' : '' ?>>Mọi hội viên (không giới hạn)</option>
        <?php foreach ($__tiers as $__t): if ((float)$__t['min_total_spent'] <= 0) continue; ?>
            <option value="<?= (int)$__t['id'] ?>" <?= $__reqTier === (int)$__t['id'] ? 'selected' : '' ?>>Chỉ <?= e($__t['name']) ?> (từ <?= format_money((int)$__t['min_total_spent']) ?>)</option>
        <?php endforeach; ?>
    </select>
    <div class="form-text small text-muted">Để trống là mọi hội viên dùng được. Người đặt hàng phải có cấp bậc từ cấp được chọn trở lên.</div>
</div>
