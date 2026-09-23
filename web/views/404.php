<?php
/** 404 */
declare(strict_types=1);
$pageTitle = 'Không tìm thấy trang' . ' - WoodCon';
require BASE_PATH . '/web/includes/header.php';
?>
<div class="container py-5 text-center">
    <div class="display-1 fw-bold" style="color:var(--wc-secondary)">404</div>
    <h1 class="app-section-title">404 - Không tìm thấy trang</h1>
    <p class="text-muted">Trang bạn đang tìm không tồn tại hoặc đã được di chuyển.</p>
    <a href="<?= BASE_URL ?>" class="btn btn-primary">Về trang chủ</a>
</div>
<?php require BASE_PATH . '/web/includes/footer.php'; ?>