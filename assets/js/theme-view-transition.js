/* =====================================================================
   WoodCon - theme-view-transition.js
   Hiệu ứng chuyển dark/light mode bằng View Transitions API.
   - Vòng tròn "circle with blur" lan tỏa từ vị trí nút dark mode.
   - Tâm tính từ getBoundingClientRect của nút (không dùng tọa độ click).
   - Bán kính đủ phủ màn hình (khoảng cách tới góc xa nhất).
   - Tọa độ/tỉ lệ đặt vào biến CSS --x, --y, --r (xử lý trong CSS).
   - Fallback: trình duyệt không hỗ trợ startViewTransition vẫn đổi theme.
   Dùng chung cho cả trang người dùng (frontend) và trang admin.
   ===================================================================== */
(function () {
  'use strict';

  function farCornerRadius(cx, cy, vw, vh) {
    var r = 0;
    var corners = [[0, 0], [vw, 0], [0, vh], [vw, vh]];
    for (var i = 0; i < corners.length; i++) {
      var dx = cx - corners[i][0];
      var dy = cy - corners[i][1];
      var d = Math.sqrt(dx * dx + dy * dy);
      if (d > r) r = d;
    }
    return r;
  }

  window.WCThemeReveal = {
    /**
     * Chạy hiệu ứng đổi theme từ vị trí nút.
     * @param {HTMLElement} btn     Nút dark mode (không để null/undefined).
     * @param {Function}    flipFn  Hàm thực hiện việc đổi theme + lưu trạng thái.
     */
    fromButton: function (btn, flipFn) {
      if (!btn || typeof flipFn !== 'function') {
        // Thiếu nút hoặc hàm -> vẫn đổi theme bình thường.
        return flipFn && flipFn();
      }

      var rect = btn.getBoundingClientRect();
      var cx = rect.left + rect.width / 2;
      var cy = rect.top + rect.height / 2;
      var vw = document.documentElement.clientWidth;
      var vh = document.documentElement.clientHeight;
      var r = farCornerRadius(cx, cy, vw, vh);

      var root = document.documentElement;
      root.style.setProperty('--x', cx + 'px');
      root.style.setProperty('--y', cy + 'px');
      root.style.setProperty('--r', r + 'px');

      // Chỉ áp hiệu ứng khi trình duyệt hỗ trợ View Transitions.
      if (typeof document.startViewTransition === 'function') {
        root.classList.add('wc-vt-on');
        var t = document.startViewTransition(function () {
          flipFn();
        });
        if (t && t.finished) {
          t.finished.then(function () {
            root.classList.remove('wc-vt-on');
            root.style.removeProperty('--x');
            root.style.removeProperty('--y');
            root.style.removeProperty('--r');
          }).catch(function () {
            root.classList.remove('wc-vt-on');
            root.style.removeProperty('--x');
            root.style.removeProperty('--y');
            root.style.removeProperty('--r');
          });
        } else {
          root.classList.remove('wc-vt-on');
        }
      } else {
        // Không hỗ trợ -> đổi theme ngay, không hiệu ứng.
        flipFn();
      }
    }
  };
})();
