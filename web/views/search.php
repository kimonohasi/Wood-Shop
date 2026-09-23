<?php
/**
 * WoodCon - Tìm kiếm / Sản phẩm
 * SIDEBAR bên trái + AUTO-SUBMIT (AJAX, không reload trang).
 */
declare(strict_types=1);

$__f = $f ?? [];

$__activeCount = 0;
if (!empty($__f['cat_ids']) && is_array($__f['cat_ids'])) $__activeCount += count($__f['cat_ids']);
$__selBrands = $__f['brand_ids'] ?? [];
if (!is_array($__selBrands)) $__selBrands = !empty($__f['brand']) ? [(int)$__f['brand']] : [];
if (!empty($__selBrands)) $__activeCount += count($__selBrands);
if (!empty($__f['min_price']) || !empty($__f['max_price'])) $__activeCount++;
if (!empty($__f['sale_only']))      $__activeCount++;
if (!empty($__f['is_new']))         $__activeCount++;
if (!empty($__f['is_best_seller'])) $__activeCount++;

$__clearUrl  = BASE_URL . '/tim-kiem' . ($__f['q'] !== '' ? '?q=' . rawurlencode($__f['q']) : '');

// Build query hiện tại để JS đọc khi cần
$__currentQuery = $_GET;
?>
<div class="container py-3">
    <div class="app-crumb"><a href="<?= BASE_URL ?>">Trang chủ</a><span class="sep">/</span><span>Sản phẩm</span></div>

    <div class="d-flex align-items-end justify-content-between mb-3 flex-wrap gap-2">
        <h1 class="app-section-title mb-0">
            <?= $__f['q'] !== '' ? 'Kết quả cho &ldquo;' . e($__f['q']) . '&rdquo;' : 'Tất cả sản phẩm' ?>
        </h1>
        <span class="text-muted small"><span id="headerCount"><?= (int)$total ?></span> sản phẩm</span>
    </div>

    <!-- Search bar ngang đầu trang: gõ là lọc + gợi ý realtime -->
    <div class="wc-search-wrap">
        <div class="wc-searchbar" role="search">
            <input id="topSearchInput" type="search" class="form-control" placeholder="Tìm tên sản phẩm, mã SKU…" value="<?= e($__f['q']) ?>" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" aria-label="Tìm kiếm sản phẩm">
        </div>
        <div class="wc-suggest" id="suggestBox" hidden></div>
    </div>

    <?php if ($__f['q'] === '' && !empty($categories)): ?>
        <?php $__chips = $categories; $__activeSlug = ''; ?>
        <?php require BASE_PATH . '/web/views/partials/cat_chips.php'; ?>
    <?php endif; ?>

    <!-- Mobile: nút mở filter + sort -->
    <div class="app-filter-topbar d-lg-none">
        <button type="button" class="app-filter-toggle" id="openFilter" aria-controls="filterDrawer" aria-expanded="false">
            <?= icon('ms-tune') ?>
            <span>Bộ lọc</span>
            <?php if ($__activeCount > 0): ?>
                <span class="app-filter-badge" id="filterBadgeMobile"><?= $__activeCount ?></span>
            <?php else: ?>
                <span class="app-filter-badge" id="filterBadgeMobile" hidden>0</span>
            <?php endif; ?>
        </button>
        <div class="ms-auto">
            <select class="form-select form-select-sm" id="sortSelectMobile" data-sort>
                <option value="best"       <?= $__f['sort'] === 'best' ? 'selected' : '' ?>>Bán chạy</option>
                <option value="new"        <?= $__f['sort'] === 'new' ? 'selected' : '' ?>>Mới nhất</option>
                <option value="price_asc"  <?= $__f['sort'] === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                <option value="price_desc" <?= $__f['sort'] === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
            </select>
        </div>
    </div>

    <div class="app-layout-with-filter">
        <!-- ============================================================
             SIDEBAR BỘ LỌC
             ============================================================ -->
        <aside class="app-fsidebar" id="filterDrawer" aria-label="Bộ lọc sản phẩm">
            <div class="app-fsidebar-backdrop d-lg-none" data-close></div>

            <form id="filterForm" class="app-fsidebar-card" onsubmit="return false;">
                <?php if ($__f['q'] !== ''): ?>
                    <input type="hidden" name="q" value="<?= e($__f['q']) ?>">
                <?php endif; ?>

                <header class="app-fsidebar-head">
                    <h2 class="h6 mb-0">
                        <?= icon('ms-tune') ?> Bộ lọc
                        <span class="app-fsidebar-count" id="filterBadge"><?= $__activeCount ?></span>
                    </h2>
                    <button type="button" class="app-fsidebar-close d-lg-none" data-close aria-label="Đóng">
                        <?= icon('ms-close') ?>
                    </button>
                </header>

                <div class="app-fsidebar-body">

                    <!-- Loại -->
                    <details class="app-fgroup" open>
                        <summary>
                            <?= icon('ms-category') ?> Loại sản phẩm
                        </summary>
                        <div class="app-fgroup-body">
                            <?php
                            // Tập ID đang được tick (cả cha lẫn con)
                            $__selSubIds = !empty($__f['cat_ids']) && is_array($__f['cat_ids'])
                                ? array_map('intval', $__f['cat_ids'])
                                : (is_array($__f['cat'] ?? null) ? array_map('intval', $__f['cat']) : []);
                            ?>
                            <?php foreach ($categories as $__cc):
                                $__sel = in_array((int)$__cc['id'], $__selSubIds, true);
                                $__hasChild = !empty($__cc['children']); ?>
                                <label class="app-frow app-frow-cat-main">
                                    <input type="checkbox" name="cat_ids[]" value="<?= (int)$__cc['id'] ?>" <?= $__sel ? 'checked' : '' ?> data-filter data-cat-parent>
                                    <span class="app-fcheck"></span>
                                    <span class="app-flabel"><?= e($__cc['name']) ?></span>
                                </label>
                                <?php if ($__hasChild): foreach ($__cc['children'] as $__sub):
                                    $__subSel = in_array((int)$__sub['id'], $__selSubIds, true); ?>
                                    <label class="app-frow app-frow-cat-sub">
                                        <input type="checkbox" name="cat_ids[]" value="<?= (int)$__sub['id'] ?>" <?= $__subSel ? 'checked' : '' ?> data-filter data-cat-child>
                                        <span class="app-fcheck"></span>
                                        <span class="app-flabel"><?= e($__sub['name']) ?></span>
                                    </label>
                                <?php endforeach; endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </details>

                    <!-- Thương hiệu -->
                    <details class="app-fgroup" open>
                        <summary>
                            <?= icon('ms-storefront') ?> Thương hiệu
                        </summary>
                        <div class="app-fgroup-body">
                            <?php if (empty($brands)): ?>
                                <div class="small text-muted px-2">Chưa có thương hiệu nào</div>
                            <?php else: ?>
                                <?php
                                $__selBrands = $__f['brand_ids'] ?? [];
                                if (!is_array($__selBrands)) {
                                    $__selBrands = !empty($__f['brand']) ? [(int)$__f['brand']] : [];
                                }
                                foreach ($brands as $__bb):
                                    $__selB = in_array((int)$__bb['id'], array_map('intval', $__selBrands), true); ?>
                                    <label class="app-frow">
                                        <input type="checkbox" name="brand[]" value="<?= (int)$__bb['id'] ?>" <?= $__selB ? 'checked' : '' ?> data-filter>
                                        <span class="app-fcheck"></span>
                                        <span class="app-flabel"><?= e($__bb['name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </details>

                    <!-- Giá -->
                    <details class="app-fgroup" open>
                        <summary>
                            <?= icon('ms-payments') ?> Khoảng giá
                        </summary>
                        <div class="app-fgroup-body">
                            <?php
                            $__boundMax = (int)($priceMax ?? 60000000);
                            $__curMin   = (int)($__f['min_price'] ?? 0);
                            $__curMax   = (int)($__f['max_price'] ?? 0);
                            // Nếu chưa set max, mặc định = boundMax (không lọc trên)
                            if ($__curMax <= 0) $__curMax = $__boundMax;
                            // Đảm bảo curMin <= curMax
                            if ($__curMin > $__curMax) $__curMin = 0;
                            ?>
                            <div class="app-range-display">
                                <div class="app-range-val">
                                    <span class="small text-muted">Từ</span>
                                    <span class="app-range-num" id="minVal"><?= format_money($__curMin) ?></span>
                                </div>
                                <div class="app-range-val text-end">
                                    <span class="small text-muted">Đến</span>
                                    <span class="app-range-num" id="maxVal"><?= $__curMax >= $__boundMax ? 'Không giới hạn' : format_money($__curMax) ?></span>
                                </div>
                            </div>

                            <div class="app-range" id="priceRange" data-min="0" data-max="<?= (int)$__boundMax ?>" data-step="<?= max(100000, (int)ceil($__boundMax / 200)) ?>" data-cur-min="<?= (int)$__curMin ?>" data-cur-max="<?= (int)$__curMax ?>">
                                <div class="app-range-track"></div>
                                <div class="app-range-fill" id="rangeFill"></div>
                                <input type="range" min="0" max="<?= (int)$__boundMax ?>" step="<?= max(100000, (int)ceil($__boundMax / 200)) ?>" value="<?= (int)$__curMin ?>" id="minRange" class="app-range-input" aria-label="Giá thấp nhất">
                                <input type="range" min="0" max="<?= (int)$__boundMax ?>" step="<?= max(100000, (int)ceil($__boundMax / 200)) ?>" value="<?= (int)$__curMax ?>" id="maxRange" class="app-range-input" aria-label="Giá cao nhất">
                            </div>

                            <input type="hidden" name="min_price" id="minPrice" value="<?= (int)$__curMin ?>">
                            <input type="hidden" name="max_price" id="maxPrice" value="<?= $__curMax >= $__boundMax ? '' : (int)$__curMax ?>">
                            <div class="app-range-bounds small text-muted">
                                <span><?= format_money(0) ?></span>
                                <span><?= format_money($__boundMax) ?></span>
                            </div>

                            <div class="app-price-chips">
                                <button type="button" class="app-pchip" data-min="0"        data-max="0">Tất cả</button>
                                <button type="button" class="app-pchip" data-min="0"        data-max="5000000">&lt; 5tr</button>
                                <button type="button" class="app-pchip" data-min="5000000"  data-max="15000000">5–15tr</button>
                                <button type="button" class="app-pchip" data-min="15000000" data-max="30000000">15–30tr</button>
                                <button type="button" class="app-pchip" data-min="30000000" data-max="0">&gt; 30tr</button>
                            </div>
                        </div>
                    </details>

                    <!-- Tuỳ chọn -->
                    <details class="app-fgroup">
                        <summary>
                            <?= icon('ms-settings') ?> Tuỳ chọn
                        </summary>
                        <div class="app-fgroup-body">
                            <label class="app-frow app-frow-toggle">
                                <input type="checkbox" name="new" value="1" <?= !empty($__f['is_new']) ? 'checked' : '' ?> data-filter>
                                <span class="app-fswitch"></span>
                                <span class="app-flabel">
                                    <?= icon('ms-auto_awesome') ?> Mới nhất
                                </span>
                            </label>
                            <label class="app-frow app-frow-toggle">
                                <input type="checkbox" name="best" value="1" <?= !empty($__f['is_best_seller']) ? 'checked' : '' ?> data-filter>
                                <span class="app-fswitch"></span>
                                <span class="app-flabel">
                                    <?= icon('ms-local_fire_department') ?> Bán chạy
                                </span>
                            </label>
                            <label class="app-frow app-frow-toggle">
                                <input type="checkbox" name="sale" value="1" <?= !empty($__f['sale_only']) ? 'checked' : '' ?> data-filter>
                                <span class="app-fswitch"></span>
                                <span class="app-flabel">
                                    <?= icon('ms-redeem') ?> Đang khuyến mãi
                                </span>
                            </label>
                        </div>
                    </details>

                </div>

                <footer class="app-fsidebar-foot">
                    <a class="app-fbtn app-fbtn-ghost" id="resetFilter" href="<?= e($__clearUrl) ?>" data-ajax-link>Đặt lại</a>
                </footer>
            </form>
        </aside>

        <!-- ============================================================
             KẾT QUẢ
             ============================================================ -->
        <main class="app-results">
            <!-- Loading overlay -->
            <div class="app-ajax-loading" id="ajaxLoading" aria-hidden="true">
                <span class="app-spinner"></span>
            </div>

            <!-- Sort bar (desktop) -->
            <div class="app-sortbar d-none d-lg-flex" id="sortBarSlot">
                <label class="small text-muted me-1">Sắp xếp:</label>
                <select class="form-select form-select-sm" id="sortSelect" data-sort>
                    <option value="best"       <?= $__f['sort'] === 'best' ? 'selected' : '' ?>>Bán chạy</option>
                    <option value="new"        <?= $__f['sort'] === 'new' ? 'selected' : '' ?>>Mới nhất</option>
                    <option value="price_asc"  <?= $__f['sort'] === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                    <option value="price_desc" <?= $__f['sort'] === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                </select>
                <span class="text-muted small ms-auto"><span id="resultCount"><?= (int)$total ?></span> kết quả</span>
            </div>

            <!-- Active chips (slot reload) -->
            <div id="activeChipsSlot">
                <?php if ($__activeCount > 0):
                    // Chuẩn bị data chips
                    $__catLabel = ''; $__brandLabel = '';
                    if (!empty($__f['cat_ids']) && is_array($__f['cat_ids'])) {
                        $__catNames = [];
                        foreach ($categories as $__cc) {
                            if (in_array((int)$__cc['id'], $__f['cat_ids'], true)) $__catNames[] = $__cc['name'];
                            if (!empty($__cc['children'])) {
                                foreach ($__cc['children'] as $__sub) {
                                    if (in_array((int)$__sub['id'], $__f['cat_ids'], true)) $__catNames[] = $__sub['name'];
                                }
                            }
                        }
                        $__catLabel = !empty($__catNames)
                            ? implode(', ', array_slice($__catNames, 0, 3)) . (count($__catNames) > 3 ? '…' : '')
                            : '';
                    }
                    // Danh sách thương hiệu đang chọn → chip riêng từng hãng
                    $__brandNames = [];
                    foreach ($brands as $__bb) {
                        if (in_array((int)$__bb['id'], array_map('intval', $__selBrands), true)) {
                            $__brandNames[(int)$__bb['id']] = $__bb['name'];
                        }
                    }
                ?>
                    <div class="app-active-summary">
                        <?php if (!empty($__f['cat_ids'])): ?>
                            <span class="app-mini-chip">Danh mục: <?= e($__catLabel ?: '...') ?>
                                <a href="<?= BASE_URL ?>/tim-kiem?<?= http_build_query(array_diff_key($__currentQuery, ['cat_ids'=>''])) ?>" data-ajax-link class="app-mini-x"><?= icon('ms-close') ?></a>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($__brandNames)): foreach ($__brandNames as $__bid => $__bName): ?>
                            <?php
                            // URL loại bỏ riêng thương hiệu này khỏi brand[] (giữ brand còn lại + query khác)
                            $__bq = $_GET;
                            if (isset($__bq['brand']) && is_array($__bq['brand'])) {
                                $__bq['brand'] = array_values(array_filter($__bq['brand'], fn($v) => (int)$v !== (int)$__bid));
                            } else {
                                unset($__bq['brand']);
                            }
                            $__bq['route'] = 'tim-kiem';
                            ?>
                            <span class="app-mini-chip">Thương hiệu: <?= e($__bName) ?>
                                <a href="<?= BASE_URL ?>/tim-kiem?<?= http_build_query($__bq) ?>" data-ajax-link class="app-mini-x"><?= icon('ms-close') ?></a>
                            </span>
                        <?php endforeach; endif; ?>
                        <?php if (!empty($__f['min_price']) || !empty($__f['max_price'])): ?>
                            <span class="app-mini-chip"><?= format_money((int)($__f['min_price'] ?: 0)) ?> – <?= format_money((int)($__f['max_price'] ?: 0)) ?>
                                <a href="<?= BASE_URL ?>/tim-kiem?<?= http_build_query(array_diff_key($__currentQuery, ['min_price'=>'','max_price'=>''])) ?>" data-ajax-link class="app-mini-x"><?= icon('ms-close') ?></a>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($__f['is_new'])): ?>
                            <span class="app-mini-chip">Mới nhất
                                <a href="<?= BASE_URL ?>/tim-kiem?<?= http_build_query(array_diff_key($__currentQuery, ['new'=>''])) ?>" data-ajax-link class="app-mini-x"><?= icon('ms-close') ?></a>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($__f['is_best_seller'])): ?>
                            <span class="app-mini-chip">Bán chạy
                                <a href="<?= BASE_URL ?>/tim-kiem?<?= http_build_query(array_diff_key($__currentQuery, ['best'=>''])) ?>" data-ajax-link class="app-mini-x"><?= icon('ms-close') ?></a>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($__f['sale_only'])): ?>
                            <span class="app-mini-chip">Khuyến mãi
                                <a href="<?= BASE_URL ?>/tim-kiem?<?= http_build_query(array_diff_key($__currentQuery, ['sale'=>''])) ?>" data-ajax-link class="app-mini-x"><?= icon('ms-close') ?></a>
                            </span>
                        <?php endif; ?>
                        <a class="app-clear-link" href="<?= e($__clearUrl) ?>" data-ajax-link>Xóa tất cả</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product grid (slot reload) -->
            <div id="productGridSlot">
                <?php if (empty($products)): ?>
                    <div class="app-empty">
                        <?= icon('ms-search_off', 'd-block mb-2', 'style="font-size:3rem;color:var(--wc-outline)"') ?>
                        Không tìm thấy sản phẩm phù hợp. Vui lòng thử từ khóa khác hoặc <a href="<?= e($__clearUrl) ?>" data-ajax-link>xem tất cả</a>.
                    </div>
                <?php else: ?>
                    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3">
                        <?php foreach ($products as $__p): ?><?php require BASE_PATH . '/web/views/partials/product_card.php'; ?><?php endforeach; ?>
                    </div>
                    <?php require BASE_PATH . '/includes/partials/pagination.php'; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script>
(function () {
    const form    = document.getElementById('filterForm');
    const drawer  = document.getElementById('filterDrawer');
    const opener  = document.getElementById('openFilter');
    const loading = document.getElementById('ajaxLoading');
    const grid    = document.getElementById('productGridSlot');
    const chips   = document.getElementById('activeChipsSlot');
    const sortBar = document.getElementById('sortBarSlot');
    const badge   = document.getElementById('filterBadge');
    const badgeM  = document.getElementById('filterBadgeMobile');
    if (!form || !grid) return;

    const AJAX_URL = '<?= BASE_URL ?>/tim-kiem-ket-qua';

    // ---- Gợi ý tìm kiếm nhanh (dropdown) ----
    (function initTopSuggest() {
        const inp  = document.getElementById('topSearchInput');
        const box  = document.getElementById('suggestBox');
        if (!inp || !box) return;

        const SUGGEST_URL = '<?= BASE_URL ?>/goi-y-tim-kiem';
        let timer = null, seq = 0;

        const close = () => { box.hidden = true; box.innerHTML = ''; };

        function render(items) {
            if (!items || !items.length) { close(); return; }
            let html = '';
            items.forEach(it => {
                const img = it.image
                    ? '<img src="' + it.image + '" alt="" loading="lazy">'
                    : '<?= icon('ms-image_not_supported', 'style="font-size:1.5rem"') ?>';
                let priceHtml = '';
                if (it.price > 0) {
                    if (it.orig > it.price) {
                        priceHtml = '<span class="wc-s-price">' + it.price_txt + '</span> <s class="wc-s-price-old">' + it.orig_txt + '</s>';
                    } else {
                        priceHtml = '<span class="wc-s-price">' + it.price_txt + '</span>';
                    }
                }
                html += '<a class="wc-s-item" href="' + it.url + '">'
                    + '<span class="wc-s-thumb">' + img + '</span>'
                    + '<span class="wc-s-info"><span class="wc-s-name">' + escapeHtml(it.name) + '</span>'
                    + '<span class="wc-s-price-row">' + priceHtml + '</span></span></a>';
            });
            box.innerHTML = html;
            box.hidden = false;
        }

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function fetchSuggest(q) {
            const my = ++seq;
            fetch(SUGGEST_URL + '?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(d => { if (seq === my) render(d.items); })
                .catch(() => { if (seq === my) close(); });
        }

        inp.addEventListener('input', () => {
            const q = inp.value.trim();
            if (q.length < 1) { close(); return; }
            clearTimeout(timer);
            timer = setTimeout(() => fetchSuggest(q), 200);
        });

        inp.addEventListener('search', close);
        inp.addEventListener('blur', () => setTimeout(close, 120));
    })();

    // ---- Lấy state hiện tại từ URL (giữ khi qua lại) ----
    const baseParams = (() => {
        const u = new URL(window.location.href);
        const p = new URLSearchParams();
        ['q','sort','min_price','max_price','new','best','sale'].forEach(k => {
            const v = u.searchParams.get(k);
            if (v !== null && v !== '' && v !== '0') p.set(k, v);
        });
        // cat_ids[] (multi — cả cha lẫn con)
        u.searchParams.getAll('cat_ids[]').forEach(v => {
            if (v && v !== '0') p.append('cat_ids[]', v);
        });
        // brand[] (multi)
        u.searchParams.getAll('brand[]').forEach(v => {
            if (v && v !== '0') p.append('brand[]', v);
        });
        return p;
    })();

    // ---- Tính số filter active ----
    function countActive(p) {
        let n = 0;
        n += p.getAll('cat_ids[]').filter(v => v && v !== '0').length;
        n += p.getAll('brand[]').filter(v => v && v !== '0').length;
        if (p.get('min_price') || p.get('max_price')) n++;
        if (p.get('new') === '1') n++;
        if (p.get('best') === '1') n++;
        if (p.get('sale') === '1') n++;
        return n;
    }

    // ---- Debounce ----
    function debounce(fn, ms) {
        let t; return function (...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), ms);
        };
    }

    // ---- Build params từ form ----
    function buildParams(extra = {}) {
        const p = new URLSearchParams();
        // Giữ q (search query) từ URL gốc hoặc ô search đầu trang
        const orig = new URL(window.location.href);
        let q = orig.searchParams.get('q');
        const topQ = document.getElementById('topSearchInput');
        if (topQ && topQ.value.trim() !== '') q = topQ.value.trim();
        if (q) p.set('q', q);

        // Đọc form filter
        form.querySelectorAll('input[data-filter]').forEach(el => {
            if (el.type === 'checkbox') {
                if (el.checked) {
                    if (el.name.endsWith('[]')) {
                        p.append(el.name, el.value);
                    } else {
                        p.set(el.name, el.value);
                    }
                }
            } else if (el.type === 'radio') {
                if (el.checked && el.value !== '0') p.set(el.name, el.value);
            }
        });

        // Đọc input giá (nằm ngoài form, dùng getElementById)
        const minP = document.getElementById('minPrice')?.value;
        const maxP = document.getElementById('maxPrice')?.value;
        if (minP && parseInt(minP, 10) > 0) p.set('min_price', minP);
        if (maxP && parseInt(maxP, 10) > 0) p.set('max_price', maxP);

        // Sort
        const sDesktop = document.getElementById('sortSelect');
        const sMobile  = document.getElementById('sortSelectMobile');
        const sortEl   = (sDesktop && sDesktop.offsetParent !== null) ? sDesktop : sMobile;
        const sort     = sortEl?.value || orig.searchParams.get('sort') || 'best';
        p.set('sort', sort);

        return p;
    }

    // ---- Update URL (không reload) ----
    function syncURL(p) {
        const url = new URL(window.location.href);
        // Xoá các key cũ
        ['q','sort','cat_ids[]','brand','brand[]','min_price','max_price','new','best','sale'].forEach(k => {
            url.searchParams.delete(k);
        });
        // Ghi key mới (URLSearchParams hỗ trợ multi-value tự động)
        p.forEach((v, k) => {
            if (v !== '' && v !== '0') url.searchParams.append(k, v);
        });
        history.replaceState({path: url.toString()}, '', url.toString());
    }

    // ---- AJAX fetch + swap DOM ----
    let inFlight = null;
    function fetchResults(p, opts = {}) {
        if (loading) loading.classList.add('show');
        const url = AJAX_URL + '?' + p.toString();
        const req = fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(html => {
                // Parse partial — tách 3 slot
                const tmp = document.createElement('div');
                tmp.innerHTML = html;

                const newChips = tmp.querySelector('#activeChipsSlot');
                const newGrid  = tmp.querySelector('#productGridSlot');
                const newSort  = tmp.querySelector('#sortBarSlot');

                if (newChips && chips) chips.innerHTML = newChips.innerHTML;
                if (newGrid  && grid)  grid.innerHTML  = newGrid.innerHTML;
                if (newSort  && sortBar && opts.replaceSort !== false) sortBar.innerHTML = newSort.innerHTML;

                // Re-bind pagination ô nhảy trang trong grid mới (AJAX thay DOM)
                if (window.wcPaginationInit && newGrid && grid) {
                    window.wcPaginationInit(grid);
                    document.dispatchEvent(new CustomEvent('wc:pager:refresh', { detail: { root: grid } }));
                }

                // Cập nhật count ở header
                const ct = tmp.querySelector('#resultCount');
                if (ct) {
                    const n = parseInt(ct.textContent || '0', 10);
                    const headerCt = document.getElementById('headerCount');
                    if (headerCt) headerCt.textContent = n;
                }

                syncURL(p);

                // Update badge
                const n = countActive(p);
                if (badge) {
                    badge.textContent = n;
                    badge.style.display = n > 0 ? '' : 'none';
                }
                if (badgeM) {
                    badgeM.textContent = n;
                    badgeM.hidden = n === 0;
                }
            })
            .catch(err => {
                console.error('Filter AJAX error:', err);
            })
            .finally(() => {
                if (loading) loading.classList.remove('show');
            });
        inFlight = req;
        return req;
    }

    const debouncedFetch = debounce(p => fetchResults(p), 250);

    // ---- 0. Thanh search ngang đầu trang (realtime) ----
    (function initTopSearch() {
        const inp = document.getElementById('topSearchInput');
        if (!inp) return;
        const refresh = () => fetchResults(buildParams({ page: 1 }));
        inp.addEventListener('input', debounce(refresh, 300));
        // Native X (search input) hoặc Enter của trình duyệt/điện thoại cũng refresh
        inp.addEventListener('search', () => { refresh(); });
        inp.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });
    })();

    // ---- 1. Auto-submit khi tick radio/checkbox ----
    form.querySelectorAll('input[data-filter]').forEach(el => {
        el.addEventListener('change', () => {
            const p = buildParams({ page: 1 });
            fetchResults(p);
            // Trên mobile: đóng drawer sau khi chọn
            if (window.innerWidth < 992) {
                drawer.classList.remove('mobile-open');
                opener?.setAttribute('aria-expanded', 'false');
                document.documentElement.style.overflow = '';
            }
        });
    });

    // ---- 2. Dual-range slider giá ----
    (function initPriceRange() {
        const wrap = document.getElementById('priceRange');
        if (!wrap) return;
        const minEl = document.getElementById('minRange');
        const maxEl = document.getElementById('maxRange');
        const minHidden = document.getElementById('minPrice');
        const maxHidden = document.getElementById('maxPrice');
        const minVal = document.getElementById('minVal');
        const maxVal = document.getElementById('maxVal');
        const fill = document.getElementById('rangeFill');
        const boundMax = parseInt(wrap.dataset.max, 10) || 100;

        function formatVND(v) {
            if (v >= 1_000_000) return (v / 1_000_000).toFixed(v % 1_000_000 === 0 ? 0 : 1) + ' triệu';
            if (v >= 1_000) return (v / 1_000).toFixed(0) + 'k';
            return v + ' đ';
        }

        function update() {
            let mn = parseInt(minEl.value, 10);
            let mx = parseInt(maxEl.value, 10);
            // Không cho vượt nhau
            if (mn > mx - 100000) {
                // Đẩy handle kia đi
                if (document.activeElement === minEl) mn = Math.max(0, mx - 100000);
                else mx = Math.min(boundMax, mn + 100000);
                minEl.value = mn; maxEl.value = mx;
            }
            // Vẽ vùng fill
            const pct1 = (mn / boundMax) * 100;
            const pct2 = (mx / boundMax) * 100;
            fill.style.left = pct1 + '%';
            fill.style.width = (pct2 - pct1) + '%';
            // Cập nhật text
            minVal.textContent = formatVND(mn);
            // Nếu max = boundMax coi như không giới hạn
            if (mx >= boundMax) {
                maxVal.textContent = 'Không giới hạn';
                maxHidden.value = '';
            } else {
                maxVal.textContent = formatVND(mx);
                maxHidden.value = mx;
            }
            minHidden.value = mn > 0 ? mn : '';
        }

        minEl.addEventListener('input', update);
        maxEl.addEventListener('input', update);

        // Auto-submit khi thả tay (change) + debounce khi đang kéo
        let dragTimeout;
        function scheduleFetch() {
            clearTimeout(dragTimeout);
            dragTimeout = setTimeout(() => {
                const p = buildParams({ page: 1 });
                fetchResults(p);
            }, 350);
        }
        minEl.addEventListener('input', scheduleFetch);
        maxEl.addEventListener('input', scheduleFetch);
        minEl.addEventListener('change', () => {
            clearTimeout(dragTimeout);
            const p = buildParams({ page: 1 });
            fetchResults(p);
        });
        maxEl.addEventListener('change', () => {
            clearTimeout(dragTimeout);
            const p = buildParams({ page: 1 });
            fetchResults(p);
        });

        update();
    })();

    // ---- 3. Quick price chips ----
    form.querySelectorAll('.app-pchip').forEach(btn => {
        btn.addEventListener('click', () => {
            const min = btn.getAttribute('data-min');
            const max = btn.getAttribute('data-max');
            // Cập nhật slider
            const minRange = document.getElementById('minRange');
            const maxRange = document.getElementById('maxRange');
            const wrap = document.getElementById('priceRange');
            const boundMax = parseInt(wrap.dataset.max, 10) || 100;
            const newMin = min === '0' ? 0 : parseInt(min, 10);
            const newMax = max === '0' ? boundMax : parseInt(max, 10);
            if (minRange) minRange.value = newMin;
            if (maxRange) maxRange.value = newMax;
            // Trigger update bằng cách dispatch event
            minRange.dispatchEvent(new Event('input', { bubbles: true }));
            // Submit ngay
            const p = buildParams({ page: 1 });
            fetchResults(p);
        });
    });

    // ---- 4. Sort (desktop + mobile) ----
    function bindSort(sel) {
        if (!sel) return;
        sel.addEventListener('change', () => {
            const p = buildParams({ page: 1 });
            fetchResults(p);
        });
    }
    bindSort(document.getElementById('sortSelect'));
    bindSort(document.getElementById('sortSelectMobile'));

    // ---- 6. Click AJAX link (chips X) ----
    document.addEventListener('click', e => {
        const link = e.target.closest('[data-ajax-link]');
        if (!link) return;
        e.preventDefault();
        const href = link.getAttribute('href');
        if (!href) return;

        // Lấy URL của link, dùng làm state mới
        const u = new URL(href, window.location.origin);
        const p = new URLSearchParams(u.search);
        fetchResults(p, { replaceSort: false });
    });

    // ---- 7. History back/forward ----
    window.addEventListener('popstate', e => {
        if (!e.state || !e.state.path) return;
        const u = new URL(e.state.path);
        const p = new URLSearchParams(u.search);
        fetchResults(p, { replaceSort: false });
    });

    // ---- 8. Mobile drawer ----
    function openDrawer() {
        drawer.classList.add('mobile-open');
        opener?.setAttribute('aria-expanded', 'true');
        document.documentElement.style.overflow = 'hidden';
    }
    function closeDrawer() {
        drawer.classList.remove('mobile-open');
        opener?.setAttribute('aria-expanded', 'false');
        document.documentElement.style.overflow = '';
    }
    opener?.addEventListener('click', openDrawer);
    drawer.querySelectorAll('[data-close]').forEach(el => el.addEventListener('click', closeDrawer));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });
})();
</script>