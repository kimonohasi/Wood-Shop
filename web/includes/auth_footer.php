<?php
/**
 * WoodCon - AUTH FOOTER tối giản (1 dòng bản quyền)
 * Đi kèm auth_header.php. Giữ toast để hiển thị lỗi/ok flash (hành vi validate hiện có),
 * bỏ 3 cột footer + floating widgets (Zalo/Messenger/phone).
 */

declare(strict_types=1);
?>
</main>

<footer class="auth-footer">
    © <?= date('Y') ?> WoodCon. All rights reserved.
</footer>

<div class="toast-container position-fixed top-0 end-0" id="toast-container"></div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script src="<?= BASE_URL ?>/assets/js/toast.js?v=11"></script>
<?php render_flash_toasts(); ?>
</body>
</html>