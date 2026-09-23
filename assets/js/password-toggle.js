/**
 * WoodCon - password-toggle.js (dùng chung toàn dự án)
 * Tự tìm mọi input[type="password"], bọc trong .wc-password-toggle (position:relative),
 * chèn nút con mắt SVG inline (stroke currentColor) để hiện/ẩn mật khẩu.
 * Không đổi name/id/mã server; chỉ đổi type password <-> text, giữ giá trị vị trí con trỏ.
 */
(function () {
    'use strict';

    var EYE_INNER =
        '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>' +
        '<circle cx="12" cy="12" r="3"/>';
    var EYE_SLASH_INNER =
        '<path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/>' +
        '<path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/>' +
        '<path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/>' +
        '<path d="m2 2 20 20"/>';

    function iconHtml(inner) {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" ' +
            'stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" ' +
            'aria-hidden="true" focusable="false">' + inner + '</svg>';
    }

    function setState(btn, iconArea, show) {
        btn.setAttribute('aria-label', show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        iconArea.innerHTML = iconHtml(show ? EYE_SLASH_INNER : EYE_INNER);
    }

    function wrap(input) {
        if (!input || input.type !== 'password') return;
        if (input.closest('.wc-password-toggle')) return;

        var wrapEl = document.createElement('span');
        wrapEl.className = 'wc-password-toggle';

        var iconArea = document.createElement('span');
        iconArea.className = 'wc-password-toggle-icon';

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'wc-password-toggle-btn';
        btn.setAttribute('aria-label', 'Hiện mật khẩu');
        btn.setAttribute('aria-pressed', 'false');
        btn.title = 'Hiện mật khẩu';
        btn.appendChild(iconArea);
        setState(btn, iconArea, false);

        input.parentNode.insertBefore(wrapEl, input);
        wrapEl.appendChild(input);
        wrapEl.appendChild(btn);

        /* Không để chuột kéo focus khỏi ô input (giữ caret) */
        btn.addEventListener('mousedown', function (e) { e.preventDefault(); });

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var show = input.type === 'password';
            var start = input.selectionStart;
            var end = input.selectionEnd;
            input.type = show ? 'text' : 'password';
            setState(btn, iconArea, show);
            try {
                input.focus();
                input.setSelectionRange(start, end);
            } catch (err) { /* ignore */ }
        });
    }

    function scan(root) {
        var list = (root || document).querySelectorAll('input[type="password"]');
        for (var i = 0; i < list.length; i++) wrap(list[i]);
    }

    function start() { scan(document); }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }

    /* Theo dõi input tạo sau (modal/AJAX) */
    if ('MutationObserver' in window) {
        var observer = new MutationObserver(function (mutations) {
            for (var m = 0; m < mutations.length; m++) {
                var added = mutations[m].addedNodes;
                if (!added || !added.length) continue;
                for (var n = 0; n < added.length; n++) {
                    var node = added[n];
                    if (node.nodeType !== 1) continue;
                    if (node.matches && node.matches('input[type="password"]')) wrap(node);
                    if (node.querySelectorAll) scan(node);
                }
            }
        });
        var initObs = function () {
            if (document.body) observer.observe(document.body, { childList: true, subtree: true });
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initObs);
        } else {
            initObs();
        }
    }
})();