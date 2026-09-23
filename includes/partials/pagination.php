<?php
/**
 * WoodCon - Phân trang chuẩn 2026 (dùng chung web + admin)
 *
 * Biến cần: $pager (mảng từ hàm paginate())
 *   - total, per_page, current_page, total_pages, offset, pages
 *
 * Tính năng:
 *   - Nút prev/next
 *   - Các pill số trang (window quanh trang hiện tại)
 *   - Ô nhập số trang trực tiếp kiểu "5 / 10" — enter hoặc click để nhảy
 *
 * Yêu cầu CSS class: .wc-pagination (xem assets/css/components.css)
 */
if (empty($pager) || (int)($pager['total_pages'] ?? 0) <= 1) return;

$__cur = (int)$pager['current_page'];
$__totalPgs = (int)$pager['total_pages'];
$__prev = $__cur > 1 ? $__cur - 1 : null;
$__next = $__cur < $__totalPgs ? $__cur + 1 : null;
$__id = uniqid('wcp', false);
// Base path cho link: ưu tiên $pagerBase (đặt trước khi include partial), mặc định = URI hiện tại.
$__base = $pagerBase ?? null;
?>
<nav class="wc-pagination" aria-label="Phân trang">
    <div class="wc-pagination-inner">
        <!-- Nút trước -->
        <?php if ($__prev): ?>
            <a class="wc-page-arrow" href="<?= e(paginate_url($__prev, $__base)) ?>" rel="prev" aria-label="Trang trước" title="Trang trước">
                <?= icon('bi-chevron-left') ?>
            </a>
        <?php else: ?>
            <span class="wc-page-arrow is-disabled" aria-disabled="true" title="Trang trước">
                <?= icon('bi-chevron-left') ?>
            </span>
        <?php endif; ?>

        <!-- Dãy số trang -->
        <div class="wc-page-numbers">
            <?php foreach ($pager['pages'] as $__p): ?>
                <?php if ($__p === '...'): ?>
                    <span class="wc-page-ellipsis">…</span>
                <?php elseif ((int)$__p === $__cur): ?>
                    <span class="wc-page-pill is-active" aria-current="page"><?= (int)$__p ?></span>
                <?php else: ?>
                    <a class="wc-page-pill" href="<?= e(paginate_url((int)$__p, $__base)) ?>"><?= (int)$__p ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Nút sau -->
        <?php if ($__next): ?>
            <a class="wc-page-arrow" href="<?= e(paginate_url($__next, $__base)) ?>" rel="next" aria-label="Trang sau" title="Trang sau">
                <?= icon('bi-chevron-right') ?>
            </a>
        <?php else: ?>
            <span class="wc-page-arrow is-disabled" aria-disabled="true" title="Trang sau">
                <?= icon('bi-chevron-right') ?>
            </span>
        <?php endif; ?>

        <!-- Ô nhảy tới trang bất kỳ kiểu "5 / 10" -->
        <form class="wc-page-jump" data-wc-pager-form method="get" action="<?= e($__base ?: strtok($_SERVER['REQUEST_URI'] ?? '/', '?')) ?>">
            <?php
            // Giữ nguyên toàn bộ query string hiện có trong form ẩn
            foreach ($_GET as $__k => $__v) {
                if ($__k === 'route' || $__k === 'page') continue;
                if (is_array($__v)) {
                    foreach ($__v as $__vv) echo '<input type="hidden" name="' . e($__k) . '[]" value="' . e($__vv) . '">';
                } else {
                    echo '<input type="hidden" name="' . e($__k) . '" value="' . e($__v) . '">';
                }
            }
            ?>
            <label class="wc-page-jump-label" for="wcpage-<?= $__id ?>">
                <span class="wc-page-jump-cur"><?= (int)$__cur ?></span>
                <span class="wc-page-jump-sep">/</span>
                <span class="wc-page-jump-total"><?= (int)$__totalPgs ?></span>
                <input id="wcpage-<?= $__id ?>" class="wc-page-jump-input" type="number" min="1" max="<?= $__totalPgs ?>" inputmode="numeric" placeholder="..." aria-label="Nhập số trang">
            </label>
            <button class="wc-page-jump-go" type="submit" aria-label="Đi tới trang">GO</button>
        </form>
    </div>
    <div class="wc-pagination-count">
        Trang <strong><?= (int)$__cur ?></strong> / <?= (int)$__totalPgs ?> · Hiển thị <?= (int)$pager['total'] ?> mục
    </div>
</nav>
