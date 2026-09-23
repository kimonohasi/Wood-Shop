<?php
/** Đổi mật khẩu */
declare(strict_types=1);
?>
<div class="container py-4" style="max-width:560px">
    <h1 class="app-section-title mb-4">Đổi mật khẩu</h1>

    <div class="card"><div class="card-body p-4">
        <form method="post">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="mb-3"><label class="form-label small">Mật khẩu hiện tại</label>
                <input type="password" class="form-control" name="old_password" required></div>
            <div class="mb-3"><label class="form-label small">Mật khẩu mới</label>
                <input type="password" class="form-control" name="password" minlength="6" required></div>
            <button class="btn btn-primary">Cập nhật mật khẩu</button>
        </form>
    </div></div>
</div>