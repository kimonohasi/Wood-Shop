/* =====================================================================
   WoodCon - main.js (dùng chung frontend + admin)
   jQuery 3 + AJAX helpers, toast, loader, back-to-top, dark mode, palette
   ===================================================================== */
(function ($) {
  'use strict';

  // ---------- AJAX với CSRF tự động ----------
  $.ajaxSetup({
    data: function (data) {
      if (typeof data !== 'string' && data !== null && typeof data === 'object') {
        data = $.extend({}, data, { csrf_token: $('#app-csrf').val() || window.WOODCON_CSRF || '' });
      }
      return data;
    }
  });

  // Ngăn chặn gửi AJAX khi thiếu CSRF
  $(document).ajaxError(function (event, jqXHR) {
    if (jqXHR.status === 419) {
      toastError('Phiên làm việc hết hạn, vui lòng tải lại trang.');
    }
  });

  // ---------- Loading screen ----------
  $(window).on('load', function () {
    $('#app-loader').addClass('hidden');
    setTimeout(function () { $('#app-loader').remove(); }, 500);
  });

  // ---------- Back to top ----------
  var $bt = $('#app-backtop');
  if ($bt.length) {
    $(window).on('scroll', function () {
      $bt.toggleClass('show', $(window).scrollTop() > 400);
    });
    $bt.on('click', function () {
      $('html, body').animate({ scrollTop: 0 }, 300);
    });
  }

  // ---------- Scroll reveal ----------
  function revealOnScroll() {
    $('.app-reveal').each(function () {
      var $el = $(this);
      if ($el.offset().top < $(window).scrollTop() + $(window).height() - 60) {
        $el.addClass('app-visible');
      }
    });
  }
  revealOnScroll();
  $(window).on('scroll', revealOnScroll);

  // ---------- Dark/Light mode (lưu localStorage, toggle class + data-bs-theme) ----------
  function applyTheme(mode) {
    $('body').toggleClass('theme-dark', mode === 'dark');
    $('html').attr('data-bs-theme', mode);
    localStorage.setItem('woodcon-theme', mode);
  }
  window.WOODCON_THEME_TOGGLE = function () {
    var flip = function () {
      var cur = $('body').hasClass('theme-dark') ? 'dark' : 'light';
      applyTheme(cur === 'dark' ? 'light' : 'dark');
      syncThemeIcon();
    };
    var btn = document.getElementById('appThemeToggle');
    if (btn && window.WCThemeReveal) {
      window.WCThemeReveal.fromButton(btn, flip);
    } else {
      flip();
    }
  };
  // Đồng bộ icon nút dark mode bên trang người dùng (chỉ áp dụng khi có nút).
  function syncThemeIcon() {
    var icon = document.getElementById('appThemeIcon');
    if (!icon) return;
    var dark = document.body.classList.contains('theme-dark');
    var use = icon.querySelector('use');
    if (!use) return;
    var base = (window.WOODCON_BASE_URL || '').replace(/\/$/, '');
    use.setAttribute('href', base + '/assets/icons/sprite.svg#' + (dark ? 'ms-light_mode' : 'ms-dark_mode'));
  }
  $(document).ready(function () {
    var saved = localStorage.getItem('woodcon-theme');
    if (saved === 'dark') applyTheme('dark');
    syncThemeIcon();
  });

  // ---------- Toast (dùng chung toast.js: showToast(message, type, duration)) ----------
  // main.js chỉ cung cấp alias thân thiện, không tái tạo logic toast.
  var _show = function (m, t, d) { if (window.showToast) { window.showToast(m, t, d); } };
  window.toastShow = function (m, t, d) { _show(m, t, d); };
  window.toastSuccess = function (m, d) { _show(m, 'success', d); };
  window.toastError   = function (m, d) { _show(m, 'error', d); };
  window.toastInfo    = function (m, d) { _show(m, 'info', d); };
  window.toastWarning = function (m, d) { _show(m, 'warning', d); };
  window.WoodConToast = function (m, t, d) { _show(m, t === 'danger' ? 'error' : t, d); };

  // ---------- Confirm modal (SweetAlert2 nếu có, fallback confirm()) ----------
  window.appConfirm = function (title, text, cb) {
    if (window.Swal) {
      Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Xác nhận',
        cancelButtonText: 'Hủy',
        confirmButtonColor: '#2d241e'
      }).then(function (result) {
        if (result.isConfirmed) cb();
      });
    } else if (window.confirm(title + ' ' + (text || ''))) {
      cb();
    }
  };

  // ---------- Quick add to cart (thẻ [data-quick-add], event delegation) ----------
  $(document).on('click', '[data-quick-add]', function () {
    var $btn = $(this);
    $.post(window.WOODCON_BASE_URL + '/gio-hang', {
      action: 'add', product_id: $btn.data('quick-add'), quantity: 1, _token: window.WOODCON_CSRF
    })
      .done(function (res) {
        if (res && res.ok) {
          var badge = $('#cartCount');
          if (badge.length) badge.text(res.count);
          toastSuccess('Đã thêm vào giỏ hàng');
        } else {
          toastWarning((res && res.message) || 'Không thể thêm sản phẩm vào giỏ');
        }
      })
      .fail(function () { toastError('Có lỗi xảy ra, vui lòng thử lại'); });
  });

  // ---------- Skeleton ẩn sau khi tải ----------
  $(document).on('click', '[data-loading]', function () {
    var $btn = $(this);
    var text = $btn.text();
    if ($btn.data('loading-disabled')) return;
    $btn.data('loading-disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Đang xử lý...');
    setTimeout(function () {
      $btn.data('loading-disabled', false).text(text);
    }, 6000);
  });

  // ---------- Hero carousel: phân biệt vuốt (swipe) vs click ----------
  // Cả slide là vùng bấm được (link). Khi người dùng kéo/vuốt để đổi slide
  // (Bootstrap xử lý swipe riêng), ta chặn không cho nhảy trang — chỉ click
  // thật sự (không di chuyển) mới điều hướng, như Swiper/Slick vẫn làm.
  var $hero = $('#heroSlider');
  if ($hero.length) {
    var HERO_SWIPE_THRESHOLD = 12; // px — ngưỡng phân biệt kéo vs click
    var drag = { active: false, startX: 0, startY: 0, moved: false };

    $hero.on('pointerdown', '.carousel-item', function (e) {
      drag.active = true; drag.moved = false;
      drag.startX = e.clientX; drag.startY = e.clientY;
    });
    $hero.on('pointermove', '.carousel-item', function (e) {
      if (!drag.active) return;
      var dx = e.clientX - drag.startX;
      var dy = e.clientY - drag.startY;
      if (Math.abs(dx) > HERO_SWIPE_THRESHOLD && Math.abs(dx) > Math.abs(dy)) {
        drag.moved = true;
      }
    });
    $hero.on('pointerup pointercancel mouseleave', function () { drag.active = false; });

    $hero.on('click', 'a.hero-slide', function (e) {
      if (drag.moved) { e.preventDefault(); e.stopPropagation(); }
      drag.moved = false;
    });
  }

})(jQuery);