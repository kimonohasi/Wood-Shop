/* =====================================================================
   WoodCon - Goong Maps autocomplete + map picker (dùng chung frontend + admin)
   - Gợi ý địa chỉ khi gõ (debounce 350ms, >= 3 ký tự)
   - Session token UUID v4 theo khuyến nghị Goong (tối ưu phí theo phiên)
   - Chọn gợi ý -> fetch Place Detail -> điền địa chỉ chuẩn + lat/lng ẩn
   - Kéo thả ghim trên bản đồ -> reverse geocode -> cập nhật địa chỉ
   - Graceful degradation: tắt tính năng / thiếu key / Goong lỗi -> ô nhập
     vẫn là input thường, không gợi ý, không gọi API, không báo lỗi khách.
   Auto khởi tạo cho mọi <input data-goong-autocomplete>. Api key KHÔNG bao
   giờ xuất hiện phía client — mọi request qua endpoint server.
   ===================================================================== */
(function (window, document) {
  'use strict';

  const BASE = window.WOODCON_BASE_URL || '';
  const CSRF = window.WOODCON_CSRF || '';

  function spriteIcon(id, cls) {
    const iconCls = 'icon' + (cls ? ' ' + cls : '');
    return '<svg class="' + iconCls + '" aria-hidden="true"><use href="' + BASE + '/assets/icons/sprite.svg#' + id + '"></use></svg>';
  }

  // Tính năng Goong đang bật? (server set window.WOODCON_GOONG_ENABLED khi render)
  // Nếu không xác định -> vẫn cho chạy, endpoint server tự quyết định trả về rỗng.
  const FEATURE_ENABLED = window.WOODCON_GOONG_ENABLED !== false;

  // UUID v4 (session token Goong)
  function uuidv4() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
      return window.crypto.randomUUID();
    }
    const v = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx';
    return v.replace(/[xy]/g, (c) => {
      const r = (Math.random() * 16) | 0;
      return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
    });
  }

  // Gọi endpoint server (key ẩn phía backend); lỗi mạng/phiên -> coi như thất bại im lặng
  function callApi(action, body, then) {
    const fd = new FormData();
    fd.append('_token', CSRF);
    fd.append('action', action);
    Object.keys(body).forEach((k) => fd.append(k, body[k] == null ? '' : body[k]));
    fetch(BASE + '/thanh-toan', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then((r) => r.json())
      .then((res) => then(res && typeof res === 'object' ? res : { ok: false }))
      .catch(() => then({ ok: false }));
  }

  function GoongAutocomplete(input, opts) {
    const self = this;
    self.input = input;
    self.opts = opts = opts || {};
    self.sessionToken = null;   // sinh mới ngay khi khách bắt đầu gõ
    self.timer = 0;
    self.active = -1;
    self.predictions = [];
    self.map = null;
    self.marker = null;
    self.leafletReady = false;
    self.mapOpen = false;

    if (!FEATURE_ENABLED) return; // tính năng tắt -> giữ nguyên input thường

    self.build();
  }

  GoongAutocomplete.prototype.build = function () {
    const self = this;
    const o = self.opts;

    // Bọc input trong .goong-wrap để định vị dropdown
    if (!self.input.dataset.goongWrapped) {
      const wrap = document.createElement('div');
      wrap.className = 'goong-wrap';
      self.input.parentNode.insertBefore(wrap, self.input);
      wrap.appendChild(self.input);
      self.input.dataset.goongWrapped = '1';
      self.wrap = wrap;
    } else {
      self.wrap = self.input.parentNode;
    }

    // Dropdown gợi ý
    self.dropdown = document.createElement('div');
    self.dropdown.className = 'goong-dropdown d-none';
    self.wrap.appendChild(self.dropdown);

    // Input ẩn lưu tọa độ (submit kèm form)
    self.latInput = document.createElement('input');
    self.latInput.type = 'hidden';
    self.latInput.name = o.latName || 'delivery_lat';
    self.wrap.appendChild(self.latInput);
    self.lngInput = document.createElement('input');
    self.lngInput.type = 'hidden';
    self.lngInput.name = o.lngName || 'delivery_lng';
    self.wrap.appendChild(self.lngInput);

    // Nút + khung bản đồ tinh chỉnh (lazy load Leaflet)
    self.mapCtl = document.createElement('div');
    self.mapCtl.className = 'goong-map-ctl';
    self.wrap.appendChild(self.mapCtl);
    self.mapBtn = document.createElement('button');
    self.mapBtn.type = 'button';
    self.mapBtn.className = 'btn btn-sm btn-outline-secondary mt-2';
    self.mapBtn.innerHTML = spriteIcon('bi-geo-alt-fill', 'me-1') + 'Tinh chỉnh vị trí trên bản đồ';
    self.mapCtl.appendChild(self.mapBtn);
    self.mapBox = document.createElement('div');
    self.mapBox.className = 'goong-map-box d-none';
    self.mapCtl.appendChild(self.mapBox);
    self.mapDiv = document.createElement('div');
    self.mapDiv.className = 'goong-map';
    self.mapBox.appendChild(self.mapDiv);
    const tip = document.createElement('div');
    tip.className = 'goong-map-tip';
    tip.textContent = 'Kéo biểu tượng ghim (pin) đến đúng vị trí giao hàng. Địa chỉ sẽ được cập nhật tự động.';
    self.mapBox.appendChild(tip);

    // Tọa độ khởi tạo bản đồ: ưu tiên lat/lng đã có, kế đến vị trí cửa hàng, cuối là trung tâm VN
    self.centerFromFields();
    if (o.centerLat && o.centerLng && (self.centerLat === null || self.centerLng === null)) {
      self.centerLat = Number(o.centerLat);
      self.centerLng = Number(o.centerLng);
    }
    if (self.centerLat === null || self.centerLng === null || isNaN(self.centerLat) || isNaN(self.centerLng)) {
      self.centerLat = 16.0471;
      self.centerLng = 108.2062;
    }

    // ----- Events -----
    self.input.addEventListener('input', () => self.onInput());
    self.input.addEventListener('focus', () => { if (self.input.value.trim() !== '') self.showLast(); });
    self.input.addEventListener('blur', () => setTimeout(() => self.onBlur(), 150));
    self.input.addEventListener('keydown', (e) => self.onKeydown(e));
    self.mapBtn.addEventListener('click', () => self.toggleMap());

    // Đóng dropdown khi click ngoài
    document.addEventListener('click', (e) => {
      if (self.wrap && !self.wrap.contains(e.target)) self.hideDropdown();
    });
  };

  // ---------- Session token ----------
  GoongAutocomplete.prototype.ensureToken = function () {
    if (!this.sessionToken) this.sessionToken = uuidv4();
    return this.sessionToken;
  };
  // "rời khỏi ô nhập / chọn xong" -> bỏ token, lần gõ sau sinh token mới
  GoongAutocomplete.prototype.invalidateToken = function () {
    this.sessionToken = null;
  };

  // ---------- Autocomplete ----------
  GoongAutocomplete.prototype.onInput = function () {
    const self = this;
    const v = self.input.value.trim();
    if (v === '') {
      self.invalidateToken();
      self.hideDropdown();
      return;
    }
    self.ensureToken(); // sinh token ngay lần gõ đầu của phiên mới
    clearTimeout(self.timer);
    self.timer = setTimeout(() => self.requestAutocomplete(v), 350);
  };

  GoongAutocomplete.prototype.requestAutocomplete = function (input) {
    const self = this;
    if (self.input.value.trim() !== input) return; // đã có gõ mới -> hủy kết quả cũ
    callApi('goong-autocomplete', { input, session_token: self.ensureToken() }, (res) => {
      if (self.input.value.trim() !== input) return;
      if (!res || res.ok !== true || !res.predictions || !res.predictions.length) {
        self.hideDropdown();
        return;
      }
      self.predictions = res.predictions;
      self.renderDropdown();
    });
  };

  GoongAutocomplete.prototype.renderDropdown = function () {
    const self = this;
    self.dropdown.innerHTML = '';
    self.active = -1;
    self.predictions.forEach((p, i) => {
      const item = document.createElement('div');
      item.className = 'goong-item';
      item.innerHTML = spriteIcon('bi-geo-alt', 'goong-icon')
        + '<span class="goong-text"></span>';
      item.querySelector('.goong-text').textContent = p.description || p.main_text || '';
      item.addEventListener('mousedown', (e) => { e.preventDefault(); self.selectPrediction(i); });
      item.addEventListener('mouseenter', () => self.setActive(i, true));
      self.dropdown.appendChild(item);
    });
    self.dropdown.classList.remove('d-none');
  };

  GoongAutocomplete.prototype.setActive = function (idx, scroll) {
    const self = this;
    const items = self.dropdown.querySelectorAll('.goong-item');
    if (idx < 0 || idx >= items.length) { self.active = -1; return; }
    self.active = idx;
    items.forEach((el, i) => el.classList.toggle('goong-active', i === idx));
    if (scroll) items[idx].scrollIntoView({ block: 'nearest' });
  };

  GoongAutocomplete.prototype.selectPrediction = function (idx) {
    const self = this;
    const p = self.predictions[idx];
    if (!p) return;
    const token = self.ensureToken();
    // Gọi Place Detail cùng session token rồi xóa token (bắt đầu phiên tìm mới)
    callApi('goong-place-detail', { place_id: p.place_id, session_token: token }, (res) => {
      self.invalidateToken();
      if (res && res.ok === true && res.address) {
        self.applyPlace(res, p.compound);
      } else {
        // Goong detail lỗi/quota: vẫn điền text gợi ý, không gọi lại API
        self.input.value = p.description || '';
        self.input.dispatchEvent(new Event('input', { bubbles: true }));
      }
    });
    self.hideDropdown();
  };

  // Điền địa chỉ chuẩn hóa + tọa độ + (nếu có) tách Phường/Quận/Tỉnh
  GoongAutocomplete.prototype.applyPlace = function (res, compoundFallback) {
    const self = this;
    self.input.value = res.address || '';
    self.latInput.value = res.lat != null ? res.lat : '';
    self.lngInput.value = res.lng != null ? res.lng : '';
    self.centerLat = res.lat != null ? Number(res.lat) : self.centerLat;
    self.centerLng = res.lng != null ? Number(res.lng) : self.centerLng;

    // Tách thành phần địa chỉ vào các ô Phường/Quận/Tỉnh (nếu form có).
    // Ưu tiên components từ Place Detail, fallback compound từ AutoComplete.
    const cp = res.components && (res.components.ward || res.components.district || res.components.province)
      ? res.components
      : (compoundFallback || {});
    const setField = (sel, val) => {
      if (!sel) return;
      const el = typeof sel === 'string' ? document.querySelector(sel) : sel;
      if (el && val && (el.value || '').trim() === '') {
        el.value = val;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
      }
    };
    setField(self.opts.wardSel, cp.ward);
    setField(self.opts.districtSel, cp.district);
    setField(self.opts.citySel, cp.province);

    if (self.opts.onSelect) self.opts.onSelect(res);
    // Cập nhật lại ghim bản đồ nếu đang mở
    if (self.mapOpen) self.updateMarker(self.centerLat, self.centerLng, false);
    // Kích hoạt sự kiện change để các nghiệp vụ khác (tính phí ship) chạy lại
    self.input.dispatchEvent(new Event('change', { bubbles: true }));
  };

  GoongAutocomplete.prototype.onBlur = function () {
    const self = this;
    clearTimeout(self.timer);
    self.hideDropdown();
    self.invalidateToken();
  };

  GoongAutocomplete.prototype.onKeydown = function (e) {
    const self = this;
    const items = self.dropdown.querySelectorAll('.goong-item');
    if (self.dropdown.classList.contains('d-none') || items.length === 0) return;
    if (e.key === 'ArrowDown') { e.preventDefault(); self.setActive(Math.max(0, self.active + 1), true); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); self.setActive(self.active <= 0 ? 0 : self.active - 1, true); }
    else if (e.key === 'Enter') {
      if (self.active >= 0) { e.preventDefault(); self.selectPrediction(self.active); }
      else self.hideDropdown();
    }
    else if (e.key === 'Escape') { e.preventDefault(); self.hideDropdown(); }
  };

  GoongAutocomplete.prototype.showLast = function () {
    const self = this;
    if (self.predictions.length) self.dropdown.classList.remove('d-none');
  };

  GoongAutocomplete.prototype.hideDropdown = function () {
    const self = this;
    self.dropdown && self.dropdown.classList.add('d-none');
  };

  GoongAutocomplete.prototype.centerFromFields = function () {
    const self = this;
    const l = self.latInput && self.latInput.value ? Number(self.latInput.value) : null;
    const n = self.lngInput && self.lngInput.value ? Number(self.lngInput.value) : null;
    self.centerLat = (l !== null && !isNaN(l)) ? l : (self.opts.centerLat || null);
    self.centerLng = (n !== null && !isNaN(n)) ? n : (self.opts.centerLng || null);
  };

  // ---------- Map picker (lazy load Leaflet) ----------
  GoongAutocomplete.prototype.toggleMap = function () {
    const self = this;
    if (self.mapOpen) {
      self.mapBox.classList.add('d-none');
      self.mapOpen = false;
      return;
    }
    self.mapBtn.disabled = true;
    self.ensureLeaflet(() => {
      self.mapBtn.disabled = false;
      self.mapBox.classList.remove('d-none');
      self.mapOpen = true;
      self.buildMap();
      // Đợi map render xong rồi hiệu chỉnh kích thước nếu cần
      setTimeout(() => { self.map && self.map.invalidateSize(); }, 80);
    });
  };

  GoongAutocomplete.prototype.ensureLeaflet = function (cb) {
    const self = this;
    if (window.L && window.L.map) { self.leafletReady = true; cb(); return; }
    const css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(css);
    const s = document.createElement('script');
    s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    s.onload = () => { self.leafletReady = true; cb(); };
    s.onerror = () => {
      // Không tải được Leaflet -> ẩn nút, không làm phiền khách
      self.mapBtn.closest('.goong-map-ctl').classList.add('d-none');
    };
    document.head.appendChild(s);
  };

  GoongAutocomplete.prototype.buildMap = function () {
    const self = this;
    if (self.map) { self.map.invalidateSize(); return; }
    const L = window.L;
    self.map = L.map(self.mapDiv, { scrollWheelZoom: false }).setView([self.centerLat, self.centerLng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(self.map);
    // Ghim kéo được
    self.marker = L.marker([self.centerLat, self.centerLng], { draggable: true }).addTo(self.map);
    self.marker.on('dragend', () => {
      const p = self.marker.getLatLng();
      self.reverseGeocode(p.lat, p.lng);
    });
    // Click vào bản đồ cũng di chuyển ghim
    self.map.on('click', (e) => {
      self.marker.setLatLng(e.latlng);
      self.reverseGeocode(e.latlng.lat, e.latlng.lng);
    });
  };

  GoongAutocomplete.prototype.updateMarker = function (lat, lng, fly) {
    const self = this;
    if (!self.map || !self.marker) return;
    self.marker.setLatLng([lat, lng]);
    if (fly) self.map.setView([lat, lng], Math.max(15, self.map.getZoom()));
  };

  // Kéo thả -> geocode ngược -> cập nhật địa chỉ
  GoongAutocomplete.prototype.reverseGeocode = function (lat, lng) {
    const self = this;
    callApi('goong-reverse-geocode', { lat, lng }, (res) => {
      if (res && res.ok === true && res.address) {
        self.applyPlace(res);
      }
    });
  };

  // =====================================================================
  // AUTO INIT: mọi input có [data-goong-autocomplete]
  // =====================================================================
  function autoInit() {
    const els = document.querySelectorAll('[data-goong-autocomplete]');
    for (let i = 0; i < els.length; i++) {
      const el = els[i];
      if (el.closest('.goong-wrap')) continue; // đã khởi tạo
      const cfg = (typeof el.dataset.goongAutocomplete === 'string' && el.dataset.goongAutocomplete)
        ? JSON.parse(el.dataset.goongAutocomplete) : {};
      new GoongAutocomplete(el, cfg);
    }
  }
  // Rerender cho các phần nội dung AJAX/cart mới thêm later
  window.goongApp = { init: (el, opts) => new GoongAutocomplete(el, opts || {}), reinit: autoInit };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoInit);
  } else {
    autoInit();
  }
})(window, document);