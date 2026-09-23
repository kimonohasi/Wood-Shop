<?php
/** Chi tiết sản phẩm - chuẩn Mộc An */
declare(strict_types=1);
use WoodCon\Product;
$__price = Product::effectivePrice($product);
$__old = ($product['sale_price'] ?? 0) > 0 && $product['sale_price'] < $product['price'] ? (float)$product['price'] : 0;
$__cover = image_url($product['cover_image'] ?? '');
$__gallery = array_map('image_url', array_column($images, 'image'));
// Ảnh riêng theo biến thể — đưa vào gallery (không trùng) để khách xem được
$__variantImgs = [];
foreach ($variants as $__v) {
    if (!empty($__v['image_url'])) {
        $__vi = image_url($__v['image_url']);
        if ($__vi !== $__cover && !in_array($__vi, $__gallery, true) && !in_array($__vi, $__variantImgs, true)) {
            $__variantImgs[] = $__vi;
        }
    }
}
$__imgList = array_values(array_unique(array_merge([$__cover], $__gallery, $__variantImgs)));
if ($__imgList === []) { $__imgList[] = default_image(); }
// Dữ liệu biến thể gửi qua JS: id => {img, price, stock, label}
$__variantJson = [];
foreach ($variants as $__v) {
    $__variantJson[(int)$__v['id']] = [
        'img'   => !empty($__v['image_url']) ? image_url($__v['image_url']) : $__cover,
        'price' => (float)$__price + (float)($__v['price_adjust'] ?? 0),
        'stock' => (int)$__v['stock'],
        'label' => ($__v['name'] ?? '') . ': ' . ($__v['value'] ?? ''),
    ];
}
?>
<div class="app-container pb-4">

    <div class="app-crumb">
        <a href="<?= BASE_URL ?>">Trang chủ</a><span class="sep">/</span>
        <a href="<?= BASE_URL ?>/danh-muc/<?= e($product['category_slug'] ?? '') ?>"><?= e($product['category_name'] ?? 'Sản phẩm') ?></a><span class="sep">/</span>
        <span class="cur"><?= e($product['name']) ?></span>
    </div>

    <div class="row g-4 g-lg-5">
        <!-- Gallery -->
        <div class="col-lg-7">
            <img id="mainImg" src="<?= e($__imgList[0]) ?>" alt="<?= e($product['name']) ?>" class="app-gallery-main">
            <?php if (count($__imgList) > 1): ?>
                <div class="app-gallery-thumbs">
                    <?php foreach ($__imgList as $__i => $__img): ?>
                        <img src="<?= e($__img) ?>" class="thumb <?= $__i === 0 ? 'active' : '' ?>" data-src="<?= e($__img) ?>" alt="<?= e($product['name']) ?>">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="col-lg-5">
            <span class="app-eyebrow mb-2 d-block"><?= e($product['category_name'] ?? 'Nội thất') ?></span>
            <h1 class="mb-2" style="font-size:clamp(1.5rem,3.5vw,2rem);font-weight:400"><?= e($product['name']) ?></h1>
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge text-bg-secondary"><?= e($product['brand_name'] ?? 'WoodCon') ?></span>
                <span class="small text-muted" id="skuDisplay">SKU: <?= e($product['sku']) ?></span>
            </div>

            <div class="d-flex align-items-baseline gap-3 mb-1">
                <span class="app-price-big" id="priceDisplay"><?= format_money((int)$__price) ?></span>
                <?php if ($__old > 0): ?><span class="price-old fs-5" id="oldPriceDisplay" style="text-decoration:line-through;color:var(--wc-outline)"><?= format_money((int)$__old) ?></span><?php endif; ?>
            </div>
            <div class="small text-muted mb-3">Giá đã bao gồm VAT</div>

            <?php if (!empty($product['summary'])): ?>
                <p class="text-muted mb-4"><?= e($product['summary']) ?></p>
            <?php endif; ?>

            <?php if (!empty($variants)): ?>
                <div class="mb-4">
                    <span class="app-eyebrow d-block mb-2">Phiên bản</span>
                    <select class="form-select" id="variantSelect">
                        <option value="">Chọn phiên bản</option>
                        <?php foreach ($variants as $__v): ?>
                            <option value="<?= (int)$__v['id'] ?>"
                                    data-img="<?= e(!empty($__v['image_url']) ? image_url($__v['image_url']) : $__cover) ?>"
                                    data-price="<?= (float)$__price + (float)($__v['price_adjust'] ?? 0) ?>"
                                    data-stock="<?= (int)$__v['stock'] ?>"
                                    <?= !empty($__v['sku']) ? 'data-sku="' . e($__v['sku']) . '"' : '' ?>>
                                <?= e($__v['name']) ?> - <?= e($__v['value']) ?><?= !empty($__v['sku']) ? ' (' . e($__v['sku']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <!-- Số lượng -->
            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="app-eyebrow">Số lượng</span>
                <div class="app-qty">
                    <button type="button" id="qtyMinus" aria-label="Giảm">–</button>
                    <input type="number" id="qtyInput" value="1" min="1" max="<?= (int)$product['quantity'] ?>">
                    <button type="button" id="qtyPlus" aria-label="Tăng">+</button>
                </div>
                <span class="small text-muted" id="stockText">Còn <?= (int)$product['quantity'] ?> sản phẩm</span>
            </div>

            <!-- Actions -->
            <div class="d-flex gap-3 mb-4">
                <button class="btn btn-lg btn-outline-primary flex-fill" id="btnAddCart">Thêm vào giỏ</button>
                <button class="btn btn-lg btn-primary flex-fill" id="btnBuyNow">Mua ngay</button>
            </div>

            <!-- Dịch vụ -->
            <div class="d-flex align-items-center gap-3 mb-4 text-muted">
                <?= icon('ms-local_shipping', 'style="color:var(--wc-secondary)"') ?>
                <span>Miễn phí vận chuyển &amp; lắp đặt toàn quốc</span>
            </div>

            <!-- Bảo hành -->
            <div class="p-3" style="background:var(--wc-surface-low);border:1px solid var(--wc-outline-variant);border-radius:var(--wc-radius-sm)">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <?= icon('ms-verified', 'style="font-size:20px;color:var(--wc-secondary)"') ?>
                    <strong>Bảo hành <?= (int)$product['warranty_months'] ?> tháng</strong>
                </div>
                <div class="small text-muted">Mỗi sản phẩm WoodCon được bảo hành chính hãng. Quý khách nhận phiếu bảo hành điện tử sau khi nhận hàng.</div>
            </div>
        </div>
    </div>

    <!-- Tabs: Mô tả / Thông số -->
    <div class="mt-5">
        <ul class="nav app-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#paneDesc" type="button" role="tab">Mô tả sản phẩm</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#paneSpec" type="button" role="tab">Thông số kỹ thuật</button>
            </li>
        </ul>
        <div class="tab-content pt-4">
            <div class="tab-pane fade show active" id="paneDesc" role="tabpanel">
                <div class="text-muted" style="max-width:860px"><?= nl2br(e($product['description'])) ?></div>
            </div>
            <div class="tab-pane fade" id="paneSpec" role="tabpanel">
                <table class="table table-borderless app-spec-table" style="max-width:720px">
                    <tbody>
                        <tr><td width="35%">Chất liệu</td><td><?= e($product['material']) ?></td></tr>
                        <tr><td>Kích thước</td><td><?= e($product['dimension']) ?></td></tr>
                        <tr><td>Bảo hành</td><td><?= (int)$product['warranty_months'] ?> tháng</td></tr>
                        <tr><td>Khối lượng</td><td><?= e($product['weight_kg']) ?> kg</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Đánh giá sản phẩm -->
    <?php if (!empty($reviews)): ?>
        <section class="app-section">
            <div class="app-section-head d-flex align-items-end justify-content-between flex-wrap gap-2">
                <h2 class="app-section-title">Đánh giá sản phẩm</h2>
                <div class="text-muted fs-5" style="color: var(--wc-warning)">
                    <?php for ($i = 1; $i <= 5; $i++): ?><?= icon($i <= round($reviewStats['avg']) ? 'bi-star-fill' : 'bi-star', '', 'style="font-size:18px;vertical-align:-2px"') ?><?php endfor; ?>
                    <span class="ms-1 text-body"><?= number_format((float)$reviewStats['avg'], 1, ',', '.') ?>/5</span>
                    <span class="ms-1 small">(<?= (int)$reviewStats['total'] ?> lượt)</span>
                </div>
            </div>
            <div class="row g-4">
                <?php foreach ($reviews as $rv): ?>
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100 app-card-hover">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong><?= e($rv['user_name']) ?></strong>
                                <span class="text-muted small"><?= $rv['created_at'] ?></span>
                            </div>
                            <div class="mb-1" style="color: var(--wc-warning)">
                                <?php for ($i = 1; $i <= 5; $i++): ?><?= icon($i <= (int)$rv['rating'] ? 'bi-star-fill' : 'bi-star', '', 'style="font-size:18px;vertical-align:-2px"') ?><?php endfor; ?>
                            </div>
                            <p class="mb-0 text-body"><?= nl2br(e($rv['content'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (current_user() && $canReview && !$hasReviewed): ?>
        <section class="app-section">
            <div class="app-section-head">
                <h2 class="app-section-title">Viết đánh giá của bạn</h2>
            </div>
            <form method="post" action="<?= BASE_URL . '/san-pham/' . $product['slug'] ?>">
                <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="rating" id="review-rating" value="5">
                <div class="mb-3">
                    <label class="form-label">Chất lượng sản phẩm</label>
                    <div class="review-stars fs-3" style="color: var(--wc-warning)">
                        <?php for ($i = 1; $i <= 5; $i++): ?><?= icon('bi-star-fill', 'review-star', 'data-value="' . $i . '" style="cursor:pointer"') ?><?php endfor; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="review-title">Tiêu đề đánh giá</label>
                    <input type="text" name="title" id="review-title" class="form-control" maxlength="150">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="review-content">Nội dung đánh giá</label>
                    <textarea class="form-control" name="content" id="review-content" rows="3" required minlength="10"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Gửi đánh giá</button>
                <div class="form-text mt-2">Đánh giá sẽ được hiển thị sau khi được kiểm duyệt.</div>
            </form>
        </section>
    <?php elseif (current_user() && $hasReviewed): ?>
        <section class="app-section">
            <div class="alert alert-success mb-0"><?= icon('ms-check_circle', 'me-2', 'style="font-size:20px;vertical-align:-4px"') ?>Cảm ơn bạn! Đánh giá của bạn đã được gửi và đang chờ kiểm duyệt.</div>
        </section>
    <?php elseif ($canReview === false && current_user()): ?>
        <section class="app-section">
            <div class="alert alert-secondary mb-0"><?= icon('ms-info', 'me-2', 'style="font-size:20px;vertical-align:-4px"') ?>Bạn chỉ có thể đánh giá sản phẩm sau khi đã nhận được đơn hàng.</div>
        </section>
    <?php endif; ?>

    <!-- Sản phẩm liên quan -->
    <?php if (!empty($related)): ?>
        <section class="app-section">
            <div class="app-section-head">
                <h2 class="app-section-title">Sản phẩm liên quan</h2>
            </div>
            <div class="row row-cols-2 row-cols-md-4 g-4">
                <?php foreach ($related as $__p): ?><?php require BASE_PATH . '/web/views/partials/product_card.php'; ?><?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
(function () {
    const productId = <?= (int)$product['id'] ?>;
    const basePrice = <?= (int)$__price ?>;
    const baseStock = <?= (int)$product['quantity'] ?>;
    const baseSku = <?= json_encode($product['sku']) ?>;
    const coverImg = <?= json_encode($__cover) ?>;
    const galleryAll = <?= json_encode(array_values($__imgList)) ?>; // cover + ảnh phụ + ảnh biến thể (đã unique)
    const fmtVND = (n) => new Intl.NumberFormat('vi-VN').format(n) + 'đ';

    const mainImg = document.getElementById('mainImg');
    const qty = document.getElementById('qtyInput');
    const priceEl = document.getElementById('priceDisplay');
    const stockEl = document.getElementById('stockText');
    const skuEl = document.getElementById('skuDisplay');
    const btnAdd = document.getElementById('btnAddCart');
    const btnBuy = document.getElementById('btnBuyNow');
    const thumbsBox = document.querySelector('.app-gallery-thumbs');
    let maxStock = baseStock;

    // Dựng lại thumbnail (cho phép xếp ảnh biến thể lên đầu)
    function renderThumbs(activeSrc) {
        if (!thumbsBox) return;
        thumbsBox.innerHTML = '';
        galleryAll.forEach(function (src) {
            const im = document.createElement('img');
            im.className = 'thumb' + (src === activeSrc ? ' active' : '');
            im.src = src; im.dataset.src = src;
            im.alt = '';
            im.onclick = function () {
                mainImg.src = im.dataset.src;
                thumbsBox.querySelectorAll('.thumb').forEach(function (x) { x.classList.remove('active'); });
                im.classList.add('active');
            };
            thumbsBox.appendChild(im);
        });
    }

    function setPrice(p) { priceEl.textContent = fmtVND(p); }

    function setStock(avail) {
        maxStock = Math.max(0, avail);
        qty.value = Math.min(Math.max(1, +qty.value || 1), Math.max(1, maxStock));
        qty.max = Math.max(1, maxStock);
        stockEl.textContent = maxStock > 0 ? 'Còn ' + maxStock + ' sản phẩm' : 'Hết hàng';
        btnAdd.disabled = btnBuy.disabled = maxStock <= 0;
    }

    function applyVariant(opt) {
        // Fallback về ảnh đại diện + giá/tồn kho gốc khi không chọn/không ảnh
        if (!opt || !opt.dataset.price) {
            setPrice(basePrice);
            skuEl.textContent = 'SKU: ' + baseSku;
            setStock(baseStock);
            mainImg.src = coverImg;
            renderThumbs(coverImg);
            return;
        }
        setPrice(+opt.dataset.price);
        skuEl.textContent = 'SKU: ' + (opt.dataset.sku || baseSku);
        setStock(+opt.dataset.stock);
        const vImg = opt.dataset.img || coverImg;
        mainImg.src = vImg;
        // Ưu tiên ảnh biến thể đứng đầu gallery
        const rest = galleryAll.filter(function (s) { return s !== vImg; });
        const ordered = [vImg].concat(rest);
        galleryAll.length = 0; galleryAll.push.apply(galleryAll, ordered);
        renderThumbs(vImg);
    }

    const vsel = document.getElementById('variantSelect');
    if (vsel) {
        vsel.addEventListener('change', function () {
            const opt = vsel.options[vsel.selectedIndex];
            applyVariant(opt && opt.value ? opt : null);
        });
    }

    renderThumbs(coverImg);

    // qty
    document.getElementById('qtyMinus').onclick = () => qty.value = Math.max(1, +qty.value - 1);
    document.getElementById('qtyPlus').onclick = () => qty.value = Math.min(maxStock, +qty.value + 1);

    const addCart = () => {
        if (+qty.value < 1 || maxStock <= 0) return;
        const data = new FormData();
        data.append('_token', window.WOODCON_CSRF);
        data.append('action', 'add');
        data.append('product_id', productId);
        data.append('quantity', qty.value);
        if (vsel) data.append('variant_id', vsel.value);
        fetch(window.WOODCON_BASE_URL + '/gio-hang', {method: 'POST', body: data, credentials: 'same-origin'})
            .then(r => r.json()).then(res => {
                if (res.ok) {
                    document.getElementById('cartCount').textContent = res.count;
                    window.WoodConToast && WoodConToast('Đã thêm vào giỏ hàng', 'success');
                } else {
                    window.WoodConToast && WoodConToast(res.message || 'Có lỗi xảy ra', 'danger');
                }
            });
    };
    document.getElementById('btnAddCart').onclick = addCart;
    document.getElementById('btnBuyNow').onclick = () => {
        addCart();
        setTimeout(() => window.location = window.WOODCON_BASE_URL + '/gio-hang', 500);
    };

    // Star rating
    const ratingInput = document.getElementById('review-rating');
    const stars = document.querySelectorAll('.review-star');
    if (ratingInput && stars.length) {
        const paint = (n) => {
            const base = (window.WOODCON_BASE_URL || '').replace(/\/$/, '');
            stars.forEach(s => {
                const fill = +s.dataset.value <= n;
                s.querySelector('use').setAttribute('href', base + '/assets/icons/sprite.svg#' + (fill ? 'bi-star-fill' : 'bi-star'));
            });
        };
        stars.forEach(s => {
            s.onmouseenter = () => paint(+s.dataset.value);
            s.onclick = () => { ratingInput.value = s.dataset.value; };
        });
        document.querySelector('.review-stars').onmouseleave = () => paint(+ratingInput.value);
    }
})();
</script>