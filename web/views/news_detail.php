<?php
/** Chi tiết tin tức */
declare(strict_types=1);
?>
<div class="container py-4">
    <div class="app-crumb"><a href="<?= BASE_URL ?>">Trang chủ</a><span class="sep">/</span><a href="<?= BASE_URL ?>/tin-tuc">Tin tức</a><span class="sep">/</span><span><?= e($post['title']) ?></span></div>

    <div class="row g-4">
        <div class="col-lg-8">
            <h1 class="fw-bold"><?= e($post['title']) ?></h1>
            <div class="text-muted small mb-3"><?= format_date($post['created_at'], 'd/m/Y') ?></div>
            <?php if ($post['image']): ?>
                <img src="<?= e(image_url($post['image'] ?? '')) ?>" class="w-100" style="border-radius:var(--wc-radius);max-height:420px;object-fit:cover" alt="">
            <?php endif; ?>
            <div class="mt-4 text-muted lh-lg"><?= nl2br(e($post['content'])) ?></div>
        </div>
        <div class="col-lg-4">
            <div class="app-filter-card p-3">
                <div class="fw-bold mb-2">Bài viết liên quan</div>
                <?php foreach ($related as $__n): ?>
                    <a class="d-block py-2 border-bottom small" href="<?= BASE_URL ?>/tin/<?= e($__n['slug']) ?>"><?= e($__n['title']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
