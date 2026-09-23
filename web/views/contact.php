<?php
/** Liên hệ */
declare(strict_types=1);
?>
<div class="container py-4">
    <div class="app-crumb"><a href="<?= BASE_URL ?>">Trang chủ</a><span class="sep">/</span><span>Liên hệ</span></div>
    <h1 class="app-section-title mb-4">Liên hệ với chúng tôi</h1>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card"><div class="card-body p-4">
                <h5 class="fw-bold mb-3">Gửi tin nhắn cho WoodCon</h5>
                <form method="post">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                    <div class="row g-3">
                        <div class="col-md-6"><input type="text" class="form-control" name="name" placeholder="Họ và tên" required></div>
                        <div class="col-md-6"><input type="tel" class="form-control" name="phone" placeholder="Số điện thoại" required></div>
                        <div class="col-md-6"><input type="email" class="form-control" name="email" placeholder="Email"></div>
                        <div class="col-md-6">
                            <select class="form-select" name="subject">
                                <option value="">-- Chọn chủ đề --</option>
                                <option value="Hỗ trợ hủy hoàn">Hỗ trợ hủy/hoàn</option>
                                <option value="Hỗ trợ đơn giao hàng">Hỗ trợ đơn giao hàng</option>
                                <option value="Liên hệ hợp tác">Liên hệ hợp tác</option>
                                <option value="Khác">Khác</option>
                            </select>
                        </div>
                        <div class="col-12"><textarea class="form-control" name="message" rows="5" placeholder="Nội dung cần hỗ trợ" required></textarea></div>
                        <div class="col-12"><button class="btn btn-primary">Gửi liên hệ</button></div>
                    </div>
                </form>
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card"><div class="card-body p-4">
                <h5 class="fw-bold mb-3">Thông tin liên hệ</h5>
                <div class="d-flex gap-3 mb-3"><?= icon('ms-call', 'style="font-size:28px;color:var(--wc-secondary)"') ?><div><div class="fw-semibold">Hotline</div><div class="small"><?= e(get_setting('hotline', '1900 0000')) ?></div></div></div>
                <div class="d-flex gap-3 mb-3"><?= icon('ms-mail', 'style="font-size:28px;color:var(--wc-secondary)"') ?><div><div class="fw-semibold">Email</div><div class="small"><?= e(get_setting('shop_email', 'hello@woodcon.vn')) ?></div></div></div>
                <div class="d-flex gap-3 mb-3"><?= icon('ms-location_on', 'style="font-size:28px;color:var(--wc-secondary)"') ?><div><div class="fw-semibold">Địa chỉ</div><div class="small"><?= e(get_setting('shop_address', '123 Nguyễn Huệ, Quận 1, TP. HCM')) ?></div></div></div>
                <div class="d-flex gap-3"><?= icon('ms-schedule', 'style="font-size:28px;color:var(--wc-secondary)"') ?><div><div class="fw-semibold">Giờ mở cửa</div><div class="small">8T00 - 22T00 (Thứ 2 - Chủ nhật)</div></div></div>
            </div></div>
        </div>
    </div>
</div>