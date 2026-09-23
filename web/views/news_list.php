<?php
/** Danh sách tin tức */
declare(strict_types=1);
?>
<div class="container py-4">
    <div class="app-crumb"><a href="<?= BASE_URL ?>">Trang chủ</a><span class="sep">/</span><span>Tin tức</span></div>
    <h1 class="app-section-title mb-4">Tin tức & cẩm nang</h1>

    <?php if (empty($posts)): ?>
        <div class="app-empty"><?= icon('ms-newspaper', 'd-block mb-2', 'style="font-size:3rem;color:var(--wc-outline)"') ?>Chưa có bài viết nào</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($posts as $__n): ?>
                <div class="col-md-4">
                    <a class="app-news-card" href="<?= BASE_URL ?>/tin/<?= e($__n['slug']) ?>">
                        <div class="media">
                            <img loading="lazy" src="<?= e(image_url($__n['image'] ?? '')) ?>" alt="<?= e($__n['title']) ?>">
                        </div>
                        <div class="p-3">
                            <span class="date"><?= format_date($__n['created_at'], 'd/m/Y') ?></span>
                            <h3 class="title"><?= e($__n['title']) ?></h3>
                            <div class="small text-muted mt-1"><?= e(mb_substr(strip_tags($__n['summary'] ?? $__n['content'] ?? ''), 0, 120, 'UTF-8')) ?>...</div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <?php require BASE_PATH . '/includes/partials/pagination.php'; ?>

    <?php endif; ?>
</div>
