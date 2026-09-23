/* =====================================================================
   WoodCon - toast.js (dùng chung toàn hệ thống: frontend + admin)
   jQuery thuần, không phụ thuộc thư viện ngoài.
   - showToast(message, type, duration)
     type: success | error | warning | info (danger => error)
     duration: số ms hiển thị (mặc định 3500). MỘT NGUỒN DUY NHẤT cho cả
     độ dài thanh tiến trình (.toast-progress) lẫn thời điểm tự ẩn:
       * set --toast-duration (ms) lên thẻ toast → CSS chạy animation đúng
         theo duration (theme.css dùng var(--toast-duration)).
       * setTimeout ẩn toast cũng dùng ĐÚNG duration.
     KHÔNG có hover-pause: thanh tiến trình LUÔN chạy trọn đúng 1 vòng và
     toast luôn tự ẩn đúng 1 lúc với lúc thanh chạy hết — không dừng giữa
     chừng như lỗi cũ (trước đây hover tạm dừng thanh + hoãn ẩn gây hiểu
     nhầm "thanh chết giữa chừng").
   - Toast nổi góc trên phải, xếp chồng dọc, tự biến mất, có nút ×.
   - On mobile (<576px) container full-width, căn trên cùng.
   Các alias tương thích ngược: toastSuccess/Error/Info/Warning, wcToast, WoodConToast
   ===================================================================== */
(function ($) {
  'use strict';

  // Version để cache-busting khi load (phải khớp với ?v= ở các footer/header).
  window.WOODCON_TOAST_VERSION = 'v11';

  var ICONS = {
    success: 'bi-check-circle-fill',
    error: 'bi-x-circle-fill',
    warning: 'bi-exclamation-triangle-fill',
    info: 'bi-info-circle-fill'
  };

  var TOAST_SVG_BASE = (window.WOODCON_BASE_URL || '').replace(/\/$/, '') + '/assets/icons/sprite.svg';

  var COLORS = {
    success: '#1a7f37',
    error: '#c81e1e',
    warning: '#b45309',
    info: '#0b57d0'
  };

  function normalize(type) {
    if (type === 'danger') type = 'error';
    return ICONS[type] ? type : 'info';
  }

  function container() {
    var $c = $('#toast-container');
    if (!$c.length) {
      $c = $('<div id="toast-container" class="position-fixed top-0 end-0"></div>').appendTo('body');
    }
    return $c;
  }

  function dismiss($t) {
    if ($t.data('closing')) return;
    $t.data('closing', true);
    var onResize = $t.data('wc-toast-resize');
    if (onResize) $(window).off('resize.wc-toast', onResize);
    $t.removeClass('in').addClass('out');
    setTimeout(function () { $t.remove(); }, 300);
  }

  window.showToast = function (message, type, duration) {
    type = normalize(type);
    if (typeof duration !== 'number' || !isFinite(duration) || duration <= 0) {
      duration = 3500;
    }
    duration = Math.round(duration);

    var $box = container();
    var icon = ICONS[type];
    var color = COLORS[type];

    var $t = $('<div class="toast-item toast-' + type + '"></div>').appendTo($box);
    // 1 nguồn duration DUY NHẤT: CSS thanh tiến trình đọc --toast-duration,
    // JS ẩn toast cũng dùng đúng duration → 2 đồng hồ luôn khớp, thanh chạy
    // trọn 1 vòng và toast ẩn đúng lúc thanh vừa chạy hết.
    $t.css('--toast-duration', duration + 'ms');
    $('<svg class="toast-icon icon" aria-hidden="true"><use href="' + TOAST_SVG_BASE + '#' + icon + '"></use></svg>').appendTo($t);
    $('<span class="toast-msg"></span>').text(message == null ? '' : String(message)).appendTo($t);
    $('<button type="button" class="toast-close" aria-label="Đóng">×</button>').appendTo($t);
    // Vệt quét = SVG <rect> (pathLength=1) animate stroke-dashoffset trong
    // theme.css → tốc độ đều, bắt đầu từ đỉnh trên-trái, quét đủ 1 vòng.
    // Kích thước rect được ĐO từ toast thật bằng JS (thuộc tính SVG) chứ
    // không dùng CSS geometry — tránh trình duyệt không hỗ trợ gây mất.
    var SVG_NS = 'http://www.w3.org/2000/svg';
    var $svg = $('<svg class="toast-progress" aria-hidden="true"></svg>').appendTo($t);
    var ring = document.createElementNS(SVG_NS, 'rect');
    ring.setAttribute('class', 'toast-progress-ring');
    ring.setAttribute('pathLength', '1');
    $svg[0].appendChild(ring);
    $svg.css('--wc-toast-sweep', color);

    function layoutRing() {
      var w = $t.outerWidth();
      var h = $t.outerHeight();
      ring.setAttribute('x', '1');
      ring.setAttribute('y', '1');
      ring.setAttribute('width', Math.max(0, w - 2));
      ring.setAttribute('height', Math.max(0, h - 2));
      ring.setAttribute('rx', '9');
      ring.setAttribute('ry', '9');
    }
    layoutRing();
    var onResize = function () { layoutRing(); };
    $(window).on('resize.wc-toast', onResize);
    $t.data('wc-toast-resize', onResize);

    // Enter animation
    requestAnimationFrame(function () { $t.addClass('in'); });

    // Thanh LUÔN chạy trọn vòng đúng duration; toast tự ẩn đúng lúc đó.
    var timer = setTimeout(function () { dismiss($t); }, duration);

    $t.find('.toast-close').on('click', function () {
      clearTimeout(timer);
      dismiss($t);
    });
  };

  // Aliases thuận tiện (hỗ trợ truyền duration tuỳ chọn, giữ tương thích cũ)
  window.toastSuccess = function (m, d) { showToast(m, 'success', d); };
  window.toastError   = function (m, d) { showToast(m, 'error', d); };
  window.toastWarning = function (m, d) { showToast(m, 'warning', d); };
  window.toastInfo    = function (m, d) { showToast(m, 'info', d); };

  // Tương thích ngược với các view/controller cũ
  window.wcToast      = function (m, t, d) { showToast(m, t, d); };
  window.WoodConToast = function (m, t, d) { showToast(m, t === 'danger' ? 'error' : t, d); };
})(jQuery);