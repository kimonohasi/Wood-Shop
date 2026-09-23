<?php
/** Admin 404 */
declare(strict_types=1);
?>
<div class="text-center py-5">
    <div style="font-size:5rem;font-weight:800;color:var(--wc-secondary)">404</div>
    <h4 class="fw-bold mt-2">Không tìm thấy trang quản trị</h4>
    <p class="text-muted small">Đường dẫn bạn truy cập không tồn tại trong khu vực quản trị.</p>
    <a href="<?= BASE_URL ?>/quan-tri" class="btn btn-primary btn-sm"><?= icon('bi-grid', 'me-1') ?>Về Dashboard</a>
</div>