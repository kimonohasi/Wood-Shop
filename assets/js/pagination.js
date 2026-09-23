/**
 * WoodCon - Pagination interaction (ô nhảy trang, hover preview)
 *
 * Cần: jQuery (được load global).
 * Xử lý form .wc-page-jump: khi nhập số trang + Enter hoặc click GO → chuyển
 * tới trang đó bằng cách nối thêm ?page=N (nếu N>1) vào URL hiện tại.
 */
(function ($) {
    'use strict';

    function wcPaginationInit(scope) {
        const root = scope || document;

        // Click GO / submit form nhảy trang
        root.querySelectorAll('[data-wc-pager-form]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const input = form.querySelector('.wc-page-jump-input');
                const total = parseInt(form.querySelector('.wc-page-jump-total').textContent, 10) || 1;
                let page = parseInt(input.value, 10);
                if (!page || isNaN(page)) page = 1;
                page = Math.max(1, Math.min(total, page));

                // Lấy URL hiện tại (path + query)
                const url = new URL(window.location.href);
                if (page > 1) url.searchParams.set('page', page);
                else url.searchParams.delete('page');

                // Nhảy (reload đầy đủ, giữ query hiện có)
                window.location.href = url.toString();
            });
        });
    }

    // Init trên document ready + mỗi khi DOM được AJAX thay thế
    $(function () {
        wcPaginationInit(document);
    });

    // Hook: nếu có sự kiện custom 'wc:pager:refresh' (dùng trong phần search AJAX)
    document.addEventListener('wc:pager:refresh', function (e) {
        wcPaginationInit(e.detail && e.detail.root ? e.detail.root : document);
    });

    window.wcPaginationInit = wcPaginationInit;
})(jQuery);
