<?php
/** Hồ sơ tài khoản */
declare(strict_types=1);
?>
<div class="container py-4">
    <div class="row g-4">
        <aside class="col-lg-3">
            <div class="app-filter-card">
                <div class="p-3 border-bottom d-flex align-items-center gap-2">
                    <?= icon('ms-account_circle', 'style="font-size:2.2rem;color:var(--wc-secondary)"') ?>
                    <div><div class="fw-bold"><?= e($user['name']) ?></div><div class="small text-muted"><?= e($user['email']) ?></div></div>
                </div>
                <ul class="nav flex-column p-2 small">
                    <li class="nav-item"><a class="nav-link active" href="<?= BASE_URL ?>/tai-khoan">Thông tin</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/tai-khoan/don-hang">Đơn hàng</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/tai-khoan/yeu-thich">Yêu thích</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/tai-khoan/diem-thuong">Điểm thưởng</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/tai-khoan/doi-mat-khau">Đổi mật khẩu</a></li>
                </ul>
            </div>
        </aside>

        <div class="col-lg-9">
            <div class="card">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Hồ sơ của tôi</h5>
                    <form method="post">
                        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small">Họ và tên</label>
                                <input type="text" class="form-control" name="name" value="<?= e($user['name']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Giới tính</label>
                                <select class="form-select" name="gender">
                                    <option value="male" <?= $user['gender'] === 'male' ? 'selected' : '' ?>>Nam</option>
                                    <option value="female" <?= $user['gender'] === 'female' ? 'selected' : '' ?>>Nữ</option>
                                    <option value="other" <?= $user['gender'] === 'other' ? 'selected' : '' ?>>Khác</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Ngày sinh</label>
                                <input type="date" class="form-control" name="birthday" value="<?= e($user['birthday'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Địa chỉ</label>
                                <input type="text" class="form-control" name="address" value="<?= e($user['address'] ?? '') ?>">
                            </div>
                            <div class="col-md-4"><input type="text" class="form-control" name="ward" placeholder="Phường/Xã" value="<?= e($user['ward'] ?? '') ?>"></div>
                            <div class="col-md-4"><input type="text" class="form-control" name="district" placeholder="Quận/Huyện" value="<?= e($user['district'] ?? '') ?>"></div>
                            <div class="col-md-4"><input type="text" class="form-control" name="city" placeholder="Tỉnh/Thành phố" value="<?= e($user['city'] ?? '') ?>"></div>
                            <div class="col-12">
                                <button class="btn btn-primary">Lưu thay đổi</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>