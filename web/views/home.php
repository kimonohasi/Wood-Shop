<?php
/** Trang chủ WoodCon - cấu trúc theo chuẩn thiết kế Mộc An */
declare(strict_types=1);
?>
<div class="app-container">

    <!-- Hero slider -->
    <?php if (!empty($sliders)):
        $__heroInterval = 6000;
        foreach ($sliders as $__h) {
            if (!empty($__h['interval_ms']) && (int)$__h['interval_ms'] >= 1000) {
                $__heroInterval = (int)$__h['interval_ms'];
                break;
            }
        }
    ?>
        <section class="app-hero carousel slide" id="heroSlider" data-bs-ride="carousel" data-bs-interval="<?= $__heroInterval ?>">
            <div class="carousel-inner">
                <?php foreach ($sliders as $__i => $__s): ?>
                    <div class="carousel-item <?= $__i === 0 ? 'active' : '' ?>">
                        <a href="<?= BASE_URL ?>/<?= e($__s['link'] ?? '') ?>" class="hero-slide" aria-label="<?= e($__s['title']) ?>" title="<?= e($__s['title']) ?>">
                            <img src="<?= e(image_url($__s['image'] ?? '')) ?>" alt="" loading="eager">
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($sliders) > 1): ?>
                <div class="carousel-indicators">
                    <?php foreach ($sliders as $__i => $__s): ?>
                        <button type="button" data-bs-target="#heroSlider" data-bs-slide-to="<?= $__i ?>" <?= $__i === 0 ? 'class="active" aria-current="true"' : '' ?> aria-label="Slide :n"></button>
                    <?php endforeach; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#heroSlider" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                <button class="carousel-control-next" type="button" data-bs-target="#heroSlider" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <!-- Features band -->
    <section class="app-features">
        <div class="app-container">
            <div class="row text-center g-2">
                <div class="col-6 col-md-3"><div class="feat"><?= icon('ms-local_shipping') ?><b>Miễn phí vận chuyển</b><small>Nội tỉnh TP.HCM</small></div></div>
                <div class="col-6 col-md-3"><div class="feat"><?= icon('ms-verified') ?><b>Bảo hành chính hãng</b><small>Lên đến 24 tháng</small></div></div>
                <div class="col-6 col-md-3"><div class="feat"><?= icon('ms-published_with_changes') ?><b>Đổi trả 7 ngày</b><small>Lỗi từ nhà sản xuất</small></div></div>
                <div class="col-6 col-md-3"><div class="feat"><?= icon('ms-forest') ?><b>Gỗ tự nhiên</b><small>100% nguồn gốc rõ ràng</small></div></div>
            </div>
        </div>
    </section>

    <!-- Danh mục nổi bật (tròn) -->
    <section class="app-section">
        <div class="app-section-head">
            <h2 class="app-section-title">Danh mục nổi bật</h2>
            <a class="app-section-link" href="<?= BASE_URL ?>/tim-kiem">Xem tất cả</a>
        </div>
        <div class="row row-cols-2 row-cols-md-4 g-4">
            <?php foreach (array_slice($categories, 0, 8) as $__c): ?>
                <div class="col">
                    <a class="app-cat-tile" href="<?= BASE_URL ?>/danh-muc/<?= e($__c['slug']) ?>">
                        <div class="media">
                            <img loading="lazy" src="<?= e(image_url($__c['image'] ?? '')) ?>" alt="<?= e($__c['name']) ?>">
                        </div>
                        <h3><?= e($__c['name']) ?></h3>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Sản phẩm nổi bật -->
    <?php if (!empty($featured)): ?>
        <section class="app-section pt-0">
            <div class="app-section-head">
                <h2 class="app-section-title">Sản phẩm nổi bật</h2>
                <a class="app-section-link" href="<?= BASE_URL ?>/tim-kiem?sort=best">Xem tất cả</a>
            </div>
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($featured as $__p): ?><?php require BASE_PATH . '/web/views/partials/product_card.php'; ?><?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Mid banner (banner phụ đầu tiên) -->
    <?php if (!empty($banners)): $__mb = $banners[0]; ?>
        <section class="app-section pt-0">
            <a href="<?= BASE_URL ?>/<?= e($__mb['link']) ?>" class="app-banner-mid d-block">
                <img class="bg" src="<?= e(image_url($__mb['image'] ?? '')) ?>" alt="<?= e($__mb['title']) ?>">
                <div class="tint"></div>
                <div class="content">
                    <h2><?= e($__mb['title']) ?></h2>
                    <span class="btn-mid">Khám phá</span>
                </div>
            </a>
        </section>
    <?php endif; ?>

    <!-- Bán chạy nhất -->
    <?php if (!empty($bestSellers)): ?>
        <section class="app-section pt-0">
            <div class="app-section-head">
                <h2 class="app-section-title">Bán chạy nhất</h2>
                <a class="app-section-link" href="<?= BASE_URL ?>/tim-kiem?sort=best">Xem tất cả</a>
            </div>
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($bestSellers as $__p): ?><?php require BASE_PATH . '/web/views/partials/product_card.php'; ?><?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Hàng mới về -->
    <?php if (!empty($newest)): ?>
        <section class="app-section pt-0">
            <div class="app-section-head">
                <h2 class="app-section-title">Mới về</h2>
                <a class="app-section-link" href="<?= BASE_URL ?>/tim-kiem?sort=new">Xem tất cả</a>
            </div>
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($newest as $__p): ?><?php require BASE_PATH . '/web/views/partials/product_card.php'; ?><?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Tin tức & Cẩm nang -->
    <?php if (!empty($latestNews)): ?>
        <section class="app-section pt-0">
            <div class="app-section-head">
                <h2 class="app-section-title">Tin tức & cẩm nang</h2>
                <a class="app-section-link" href="<?= BASE_URL ?>/tin-tuc">Xem tất cả</a>
            </div>
            <div class="row g-4">
                <?php foreach ($latestNews as $__n): ?>
                    <div class="col-md-4">
                        <a class="app-news-card" href="<?= BASE_URL ?>/tin/<?= e($__n['slug']) ?>">
                            <div class="media">
                                <img loading="lazy" src="<?= e(image_url($__n['image'] ?? '')) ?>" alt="<?= e($__n['title']) ?>">
                            </div>
                            <div class="p-3">
                                <span class="date"><?= format_date($__n['created_at'] ?? null, 'd/m/Y') ?></span>
                                <h3 class="title"><?= e($__n['title']) ?></h3>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>