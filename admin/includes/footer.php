<?php
/**
 * WoodCon Admin - Footer layout
 * JS dùng chung: theme, sidebar collapse, command palette Ctrl+K,
 * live search, modal xác nhận xóa, toast. Đóng các thẻ đã mở ở header.
 */

declare(strict_types=1);
?>
        </main>

        <footer class="px-3 px-lg-4 py-3 border-top small text-muted d-flex justify-content-between align-items-center">
            <span>&copy; <?= date('Y') ?> WoodCon Admin.</span>
            <span class="d-none d-sm-inline">Nội thất gỗ cao cấp - bền bỉ theo năm tháng</span>
        </footer>
    </div><!-- /main -->
</div><!-- /d-flex -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
window.WOODCON_BASE_URL = <?= json_encode(BASE_URL) ?>;
window.WOODCON_CSRF = <?= json_encode(csrf_token()) ?>;
window.WOODCON_GOONG_ENABLED = <?= \WoodCon\GoongService::enabled() ? 'true' : 'false' ?>;

/* ===== Modal xác nhận dùng chung (thay thế confirm() native) ===== */
(function () {
    var modalEl = null;
    function ensureModal() {
        if (modalEl) return modalEl;
        modalEl = document.createElement('div');
        modalEl.className = 'modal fade';
        modalEl.innerHTML =
            '<div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:1rem;border:1px solid var(--wc-border)">' +
            '<div class="modal-body p-4 text-center">' +
            '<div style="width:56px;height:56px;border-radius:50%;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb, var(--wc-danger) 12%, transparent);color:var(--wc-danger)"><?= icon('bi-exclamation-triangle-fill', 'fs-3') ?></div>' +
            '<h5 class="fw-bold mb-2" id="wcConfirmTitle">Xác nhận</h5>' +
            '<p class="text-muted mb-3" id="wcConfirmMsg">Bạn chắc chắn chưa?</p>' +
            '<div class="d-flex gap-2 justify-content-center">' +
            '<button class="btn btn-light border" data-bs-dismiss="modal"><?= icon('bi-x-lg', 'me-1') ?>Hủy</button>' +
            '<button class="btn btn-danger" id="wcConfirmOk"><?= icon('bi-check-lg', 'me-1') ?>Xác nhận</button>' +
            '</div></div></div></div>';
        document.body.appendChild(modalEl);
        window.__wcConfirmModal = new bootstrap.Modal(modalEl);
        return modalEl;
    }
    window.wcConfirm = function (msg, onOk) {
        var m = ensureModal();
        m.querySelector('#wcConfirmTitle').textContent = 'Xác nhận';
        m.querySelector('#wcConfirmMsg').textContent = msg || 'Bạn chắc chắn chưa?';
        var ok = m.querySelector('#wcConfirmOk');
        ok.onclick = function () { window.__wcConfirmModal.hide(); if (onOk) onOk(); };
        window.__wcConfirmModal.show();
    };
    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-confirm]');
        if (el) {
            e.preventDefault();
            var msg = el.dataset.confirm || 'Bạn chắc chắn chưa?';
            wcConfirm(msg, function () { window.location.href = el.href || el.dataset.href; });
        }
    });
})();

/* ===== Theme sáng/tối + cỡ chữ (lưu localStorage, dùng chung Cài đặt) ===== */
(function () {
    var PREF_THEME = 'woodcon_theme';
    var PREF_SIZE  = 'woodcon_ad_size';
    var SIZES = { base: 1, large: 1.13, xlarge: 1.27 };

    function currentSize() {
        var s = localStorage.getItem(PREF_SIZE);
        return (s && SIZES[s] !== undefined) ? s : 'base';
    }
    function currentDark() {
        return localStorage.getItem(PREF_THEME) === 'dark';
    }

    window.WCSettings = {
        setSize: function (v) {
            if (SIZES[v] === undefined) v = 'base';
            localStorage.setItem(PREF_SIZE, v);
            document.body.setAttribute('data-ad-size', v);
            window.WCSettings.syncUi();
        },
        setTheme: function (dark) {
            localStorage.setItem(PREF_THEME, dark ? 'dark' : 'light');
            document.body.classList.toggle('theme-dark', !!dark);
            document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
            window.WCSettings.syncUi();
        },
        toggleTheme: function () {
            var flip = function () { window.WCSettings.setTheme(!currentDark()); };
            if (window.WCThemeReveal) {
                var btn = document.querySelector('.admin-theme-toggle');
                if (btn) { window.WCThemeReveal.fromButton(btn, flip); return; }
            }
            flip();
        },
        /* Đồng bộ trạng thái nút trên topbar + các control trong Cài đặt */
        syncUi: function () {
            var u = document.querySelector('.admin-theme-toggle use');
            if (u) u.setAttribute('href', WOODCON_BASE_URL + '/assets/icons/sprite.svg#' + (currentDark() ? 'bi-sun' : 'bi-moon-stars'));
            var size = currentSize();
            document.querySelectorAll('[data-ad-size-btn]').forEach(function (b) {
                b.classList.toggle('active', b.getAttribute('data-ad-size-btn') === size);
            });
            document.querySelectorAll('[data-ad-theme-btn]').forEach(function (b) {
                b.classList.toggle('active', (b.getAttribute('data-ad-theme-btn') === 'dark') === currentDark());
            });
        }
    };

    /* Áp dụng lựa chọn đã lưu khi mở trang */
    document.body.setAttribute('data-ad-size', currentSize());
    if (currentDark()) {
        document.body.classList.add('theme-dark');
        document.documentElement.setAttribute('data-bs-theme', 'dark');
    }

    /* Nút Sáng/Tối trên topbar */
    var btn = document.querySelector('.admin-theme-toggle');
    if (btn) btn.addEventListener('click', function () { window.WCSettings.toggleTheme(); });
    window.WCSettings.syncUi();
})();

/* ===== Sidebar thu gọn (lưu preference) + tooltip icon-only ===== */
(function () {
    var sb = document.getElementById('adminSidebar');
    if (!sb) return;
    var store = 'woodcon_admin_sidebar_collapsed';
    if (localStorage.getItem(store) === '1') sb.classList.add('collapsed');
    var toggle = document.querySelector('.admin-collapse-toggle');
    function syncToggle(c) {
        if (!toggle) return;
        toggle.title = c ? 'Mở rộng menu' : 'Thu gọn menu';
        toggle.setAttribute('aria-label', toggle.title);
    }
    syncToggle(sb.classList.contains('collapsed'));
    if (toggle) toggle.addEventListener('click', function () {
        var c = sb.classList.toggle('collapsed');
        localStorage.setItem(store, c ? '1' : '0');
        syncToggle(c);
        if (!c) hideTip();
    });

    /* Tooltip khi sidebar thu gọn (tooltip CSS cũ bị clip bởi overflow nên dùng node cố định) */
    var tipNode = null;
    function isCollapsedDesktop() {
        return sb.classList.contains('collapsed') && window.matchMedia('(min-width: 992px)').matches;
    }
    function hideTip() { if (tipNode) { tipNode.remove(); tipNode = null; } }
    function showTip(text, anchor) {
        if (!isCollapsedDesktop()) return;
        hideTip();
        tipNode = document.createElement('div');
        tipNode.className = 'wc-sidebar-tip';
        tipNode.textContent = text;
        document.body.appendChild(tipNode);
        var r = anchor.getBoundingClientRect();
        tipNode.style.left = (r.right + 10) + 'px';
        tipNode.style.top = (r.top + (r.height / 2)) + 'px';
        tipNode.style.transform = 'translateY(-50%)';
    }
    sb.querySelectorAll('.admin-nav-item').forEach(function (a) {
        if (!a.dataset.tip) return;
        a.addEventListener('mouseenter', function () { showTip(a.dataset.tip, a); });
        a.addEventListener('mouseleave', hideTip);
        a.addEventListener('focus', function () { showTip(a.dataset.tip, a); });
        a.addEventListener('blur', hideTip);
    });
    window.addEventListener('resize', hideTip);
})();

/* ===== Giữ vị trí cuộn sidebar sau khi reload ===== */
(function () {
    var KEY = 'woodcon_admin_sidebar_scroll';
    var sidebar = document.getElementById('adminSidebar');
    if (!sidebar) return;

    /* Khôi phục vị trí đã lưu */
    var saved = sessionStorage.getItem(KEY);
    if (saved !== null) {
        sidebar.scrollTop = parseInt(saved, 10) || 0;
    }

    /* Lưu vị trí trước khi trang unload */
    window.addEventListener('beforeunload', function () {
        sessionStorage.setItem(KEY, String(sidebar.scrollTop));
    });
})();

/* ===== Command Palette (Ctrl+K) ===== */
(function () {
    var palette = document.getElementById('app-palette');
    if (!palette) return;
    var bsPalette = new bootstrap.Modal(palette);
    var input = document.getElementById('paletteInput');
    var list = document.getElementById('paletteList');
    var idx = -1;
    var timer = null;

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); toggle(); }
        if (e.key === 'Escape' && palette.classList.contains('show')) { bsPalette.hide(); }
    });
    document.querySelector('.admin-palette-trigger')?.addEventListener('click', toggle);

    function toggle() { bsPalette.show(); setTimeout(function(){ input.value=''; list.innerHTML='<div class=\"text-center text-muted small py-3\">Nhập từ khóa để tìm kiếm nhanh</div>'; idx=-1; input.focus(); }, 60); }

    function renderSection(label, rows) {
        if (!rows.length) return '';
        var html = '<div class=\"small text-muted fw-semibold px-2 pt-2 pb-1\">' + escapeHtml(label) + '</div>';
        rows.forEach(function (r, i) {
            html += '<a class=\"palette-item\" href=\"' + escapeHtml(r.url) + '\" data-idx=\"' + i + '\">'
                + '<span class=\"palette-thumb\">' + (r.image ? '<img src=\"' + escapeHtml(r.image) + '\" alt=\"\" loading=\"lazy\">' : '<svg class=\"icon text-muted\" aria-hidden=\"true\"><use href=\"' + (WOODCON_BASE_URL + '/assets/icons/sprite.svg#bi-box-seam') + '\"></use></svg>') + '</span>'
                + '<span class=\"palette-info\"><span class=\"palette-title\">' + escapeHtml(r.title) + '</span><span class=\"palette-sub\">' + escapeHtml(r.subtitle) + '</span></span>'
                + '<span class=\"palette-type\">' + escapeHtml(r.label) + '</span>'
                + '</a>';
        });
        return html;
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; });
    }

    function render(q) {
        q = (q || '').trim();
        clearTimeout(timer);
        if (!q) {
            list.innerHTML = '<div class=\"text-center text-muted small py-3\">Nhập từ khóa để tìm kiếm nhanh</div>';
            idx = -1;
            return;
        }
        list.innerHTML = '<div class=\"text-center text-muted small py-3\">Đang tìm...</div>';
        idx = -1;
        timer = setTimeout(function () {
            fetch(WOODCON_BASE_URL + '/quan-tri/tim-kiem-nhanh?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data || !data.ok) return;
                    var grouped = {};
                    (data.results || []).forEach(function (r) { grouped[r.type] = grouped[r.type] || []; grouped[r.type].push(r); });
                    var order = ['product','order','voucher','user','staff'];
                    var labels = {product:'Sản phẩm',order:'Đơn hàng',voucher:'Voucher',user:'Khách hàng',staff:'Nhân sự'};
                    var html = '';
                    order.forEach(function (t) { if (grouped[t]) html += renderSection(labels[t] || t, grouped[t]); });
                    if (!html) html = '<div class=\"text-center text-muted small py-3\">Không tìm thấy kết quả</div>';
                    list.innerHTML = html;
                    idx = -1;
                });
        }, 180);
    }

    input.addEventListener('input', function () { render(this.value); });
    input.addEventListener('keydown', function (e) {
        var all = Array.prototype.slice.call(list.querySelectorAll('.palette-item'));
        if (e.key === 'ArrowDown') { e.preventDefault(); idx = (idx + 1) % all.length; all.forEach(function(it){it.classList.remove('active');}); if (all[idx]) { all[idx].classList.add('active'); all[idx].scrollIntoView({block:'nearest'}); } }
        else if (e.key === 'ArrowUp') { e.preventDefault(); idx = (idx - 1 + all.length) % all.length; all.forEach(function(it){it.classList.remove('active');}); if (all[idx]) { all[idx].classList.add('active'); all[idx].scrollIntoView({block:'nearest'}); } }
        else if (e.key === 'Enter' && all[idx]) { e.preventDefault(); window.location.href = all[idx].href; }
    });
    palette.addEventListener('shown.bs.modal', function(){ input.focus(); });
})();

/* ===== Live search topbar (nhảy tới trang / đơn hàng) ===== */
(function () {
    var box = document.getElementById('adminLiveSearch');
    var res = document.getElementById('adminLiveResults');
    if (!box || !res) return;
    var pages = [
        { label: 'Dashboard',        icon: 'bi-grid-1x2-fill', url: WOODCON_BASE_URL + '/quan-tri' },
        { label: 'Sản phẩm',         icon: 'bi-box-seam-fill', url: WOODCON_BASE_URL + '/quan-tri/san-pham' },
        { label: 'Đơn hàng',         icon: 'bi-receipt-cutoff', url: WOODCON_BASE_URL + '/quan-tri/don-hang' },
        { label: 'Khách hàng',       icon: 'bi-people-fill', url: WOODCON_BASE_URL + '/quan-tri/khach-hang' },
        { label: 'Khuyến mãi',       icon: 'bi-ticket-perforated-fill', url: WOODCON_BASE_URL + '/quan-tri/voucher' },
        { label: 'Kiểm duyệt hủy/hoàn', icon: 'bi-clipboard2-check-fill', url: WOODCON_BASE_URL + '/quan-tri/duyet' },
        { label: 'Đánh giá',         icon: 'bi-star-fill', url: WOODCON_BASE_URL + '/quan-tri/danh-gia' },
        { label: 'Báo cáo',          icon: 'bi-bar-chart-fill', url: WOODCON_BASE_URL + '/quan-tri/bao-cao' },
        { label: 'Bảo hành',         icon: 'bi-tools', url: WOODCON_BASE_URL + '/quan-tri/bao-hanh' },
        { label: 'Banner',           icon: 'bi-images', url: WOODCON_BASE_URL + '/quan-tri/banner' },
        { label: 'Tin tức',          icon: 'bi-newspaper', url: WOODCON_BASE_URL + '/quan-tri/tin-tuc' },
        { label: 'Liên hệ / CSKH',   icon: 'bi-chat-dots-fill', url: WOODCON_BASE_URL + '/quan-tri/lien-he' },
        { label: 'Cài đặt',          icon: 'bi-gear-fill', url: WOODCON_BASE_URL + '/quan-tri/cai-dat' },
        { label: 'Vận chuyển',       icon: 'bi-truck', url: WOODCON_BASE_URL + '/quan-tri/van-chuyen' }
    ];

    function render(q) {
        var html = '';
        q = (q || '').toLowerCase();
        pages.forEach(function (p) {
            if (!q || (p.label + ' ' + p.icon).toLowerCase().indexOf(q) !== -1) {
                html += '<a class="dropdown-item d-flex align-items-center gap-3 py-2" href="' + p.url + '"><svg class="icon text-muted" aria-hidden="true"><use href="' + WOODCON_BASE_URL + '/assets/icons/sprite.svg#' + p.icon + '"></use></svg>' + p.label + '</a>';
            }
        });
        res.innerHTML = html || '<div class="dropdown-item text-muted small text-center py-2">Không có kết quả</div>';
        res.style.display = 'block';
    }
    box.addEventListener('input', function(){ render(this.value); });
    box.addEventListener('focus', function(){ render(this.value); });
    box.addEventListener('keydown', function (e) { if (e.key === 'Escape') { res.style.display='none'; box.value=''; box.blur(); } });
    document.addEventListener('click', function (e) { if (!e.target.closest('.position-relative')) res.style.display = 'none'; });
})();
</script>
<script src="<?= BASE_URL ?>/assets/js/toast.js?v=11"></script>
<script src="<?= BASE_URL ?>/assets/js/theme-view-transition.js"></script>
<script src="<?= BASE_URL ?>/assets/js/pagination.js"></script>
<script src="<?= BASE_URL ?>/assets/js/goong-autocomplete.js?v=1"></script>
<script src="<?= BASE_URL ?>/assets/js/password-toggle.js"></script>
<script>
/* ===== Segmented tabs dùng chung: pill trượt (Dashboard, Báo cáo) ===== */
window.WCSeg = (function () {
    function place(container) {
        var pill = container.querySelector('.seg-indicator');
        var a = container.querySelector('.seg-item.active');
        if (!pill) return;
        if (!a) { pill.style.visibility = 'hidden'; return; }
        pill.style.visibility = 'visible';
        pill.style.transform = 'translateX(' + (a.offsetLeft - 4) + 'px)';
        pill.style.width = a.offsetWidth + 'px';
    }
    function bind(container) {
        if (!container || container.__segBound) return;
        container.__segBound = true;
        var pill = document.createElement('span');
        pill.className = 'seg-indicator';
        container.appendChild(pill);
        container.addEventListener('click', function (e) {
            var item = e.target.closest('.seg-item');
            if (!item || !container.contains(item)) return;
            container.querySelectorAll('.seg-item').forEach(function (b) { b.classList.remove('active'); });
            item.classList.add('active');
            place(container);
            var href = item.getAttribute('href');
            var ajax = container.getAttribute('data-ajax') === '1';
            if (href) e.preventDefault();
            if (href && !ajax) {
                setTimeout(function () { window.location.href = href; }, 220);
            }
        });
        window.addEventListener('resize', function () { place(container); });
        place(container);
    }
    return {
        bind: bind,
        initAll: function (root) { (root || document).querySelectorAll('.seg-tabs').forEach(function (t) { bind(t); }); }
    };
})();
window.addEventListener('DOMContentLoaded', function () { window.WCSeg.initAll(); });
</script>
<?php render_flash_toasts(); ?>
<!-- Toast container (đặt sẵn trong layout) -->
<div id="toast-container"></div>
</body>
</html>
