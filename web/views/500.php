<?php
/** 500 */
declare(strict_types=1);
$pageTitle = 'Lỗi máy chủ' . ' - WoodCon';
require BASE_PATH . '/web/includes/header.php';
?>
<div class="container py-5 text-center">
    <div class="display-1 fw-bold" style="color:var(--wc-danger)">500</div>
    <h1 class="app-section-title">500 - Có lỗi xảy ra</h1>
    <p class="text-muted">Hệ thống đang gặp sự cố. Vui lòng thử lại sau ít phút.</p>
    <a href="<?= BASE_URL ?>" class="btn btn-primary">Về trang chủ</a>
</div>
<?php require BASE_PATH . '/web/includes/footer.php'; ?>