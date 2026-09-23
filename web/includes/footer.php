<?php
/**
 * WoodCon - FOOTER chung (BỘ PHẬN DUY NHẤT toàn trang web - chuẩn Mộc An)
 * Luôn đi kèm header.php, đóng thẻ <main>, nạp JS dùng chung.
 */

declare(strict_types=1);

use WoodCon\Category;
?>
</main>

<!-- Footer -->
<footer class="app-footer">
    <div class="container py-5">
        <div class="row g-5">
            <div class="col-lg-5">
                <span class="app-brand-text text-white">Wood<span>Con</span></span>
                <p class="mt-3 mb-2">Nội thất gỗ cao cấp — Mộc An tinh hoa, bền bỉ cùng thời gian.</p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="social-btn" aria-label="Facebook"><?= icon('bi-facebook') ?></a>
                    <a href="#" class="social-btn" aria-label="Instagram"><?= icon('bi-instagram') ?></a>
                    <a href="#" class="social-btn" aria-label="Youtube"><?= icon('bi-youtube') ?></a>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-title mb-4">Về WoodCon</div>
                <ul class="list-unstyled small d-grid gap-2 mb-0">
                    <li><a class="text-decoration-none" href="<?= BASE_URL ?>/tin-tuc">Tin tức</a></li>
                    <li><a class="text-decoration-none" href="<?= BASE_URL ?>/lien-he">Liên hệ</a></li>
                    <li><a class="text-decoration-none" href="<?= BASE_URL ?>/khuyen-mai">Khuyến mãi</a></li>
                    <li><a class="text-decoration-none" href="<?= BASE_URL ?>/quyen-loi">Quyền lợi</a></li>
                    <li><a class="text-decoration-none" href="<?= BASE_URL ?>/tim-kiem">Sản phẩm</a></li>
                    <li><a class="text-decoration-none" href="<?= BASE_URL ?>/tra-cuu-bao-hanh">Tra cứu bảo hành</a></li>
                    <li><a class="text-decoration-none" href="<?= BASE_URL ?>/tra-cuu-don-hang">Tra cứu đơn hàng</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-title mb-4">Danh mục</div>
                <ul class="list-unstyled small d-grid gap-2 mb-0">
                    <?php foreach (Category::getTree() as $__fc): ?>
                        <li><a class="text-decoration-none" href="<?= BASE_URL ?>/danh-muc/<?= e($__fc['slug']) ?>"><?= e($__fc['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-lg-3">
                <div class="footer-title mb-4">Hỗ trợ</div>
                <ul class="list-unstyled small d-grid gap-2 mb-0">
                    <li>Hotline: <a class="text-white text-decoration-none" href="tel:<?= e(get_setting('hotline', '1900 0000')) ?>"><?= e(get_setting('hotline', '1900 0000')) ?></a></li>
                    <li>Email: <?= e(get_setting('shop_email', 'hello@woodcon.vn')) ?></li>
                    <li>Giờ mở cửa: 8h - 22h (T2 - CN)</li>
                    <li>Giao hàng toàn quốc</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-bottom text-center py-3">
        &copy; <?= date('Y') ?> WoodCon. All rights reserved.
    </div>
</footer>

<!-- Toast + global JS -->
<div class="toast-container position-fixed top-0 end-0" id="toast-container"></div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script src="<?= BASE_URL ?>/assets/js/toast.js?v=11"></script>
<script src="<?= BASE_URL ?>/assets/js/theme-view-transition.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/auth-modal.js?v=1"></script>
<script src="<?= BASE_URL ?>/assets/js/pagination.js"></script>
<script src="<?= BASE_URL ?>/assets/js/password-toggle.js"></script>
<?php require_once BASE_PATH . '/web/views/partials/floating-widgets.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/floating-widgets.js"></script>
<script src="<?= BASE_URL ?>/assets/js/goong-autocomplete.js?v=1"></script>
<?php render_flash_toasts(); ?>
</body>
</html>