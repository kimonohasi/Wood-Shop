/* ============================================================
   Auth modal (WoodCon): mở popup đăng nhập/đăng ký/quên mật khẩu
   - Chuyển pane trong một modal duy nhất (#authModal)
   - Gửi AJAX (fetch) tới route auth; lỗi hiện inline, thành công
     reload trang giữ nguyên vị trí (login/register) hoặc chuyển
     sang bước OTP (quên mật khẩu).
   Nạp toàn cục qua footer.php, hoạt động khi header có modal.
   ============================================================ */
(function () {
    'use strict';

    var modalEl = document.getElementById('authModal');
    if (!modalEl) return;

    var PANES = ['login', 'register', 'forgot'];
    var BASE = (window.WOODCON_BASE_URL || '').replace(/\/$/, '');

    function paneEl(name) {
        return document.getElementById('authPane' + name.charAt(0).toUpperCase() + name.slice(1));
    }

    function hideError(box) {
        if (!box) return;
        box.hidden = true;
        box.textContent = '';
    }

    function showError(box, msg) {
        if (!box) return;
        box.textContent = msg || 'Đã có lỗi xảy ra. Vui lòng thử lại.';
        box.hidden = false;
    }

    function setLoading(btn, loading) {
        if (!btn) return;
        if (loading) {
            btn.setAttribute('data-orig', btn.innerHTML);
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Đang xử lý…';
        } else {
            btn.disabled = false;
            if (btn.dataset.orig !== undefined) btn.innerHTML = btn.dataset.orig;
        }
    }

    /* ---- Chuyển pane ---- */
    function showPane(name) {
        PANES.forEach(function (p) {
            var el = paneEl(p);
            if (el) el.style.display = (p === name) ? '' : 'none';
        });
        if (name === 'forgot') {
            var verify = document.getElementById('authForgotVerify');
            var send = modalEl.querySelector('[data-auth-action="forgot-send"]');
            if (verify && send) { verify.style.display = 'none'; send.style.display = ''; }
            hideError(send ? send.querySelector('.auth-error') : null);
            hideError(verify ? verify.querySelector('.auth-error') : null);
        }
    }

    window.openAuthModal = function (pane) {
        pane = pane || 'login';
        var m = bootstrap.Modal.getOrCreateInstance(modalEl);
        // Nhớ trang hiện tại để quay lại sau khi đăng nhập/đăng ký thành công
        modalEl.querySelectorAll('.auth-next').forEach(function (n) { n.value = window.location.href; });
        showPane(pane);
        m.show();
    };

    /* ---- Chuyển phương thức Email/SMS (quên mật khẩu) ---- */
    function activateMethod(method) {
        var field = document.getElementById('authMethodField');
        if (field) field.value = method;
        var emailWrap = document.getElementById('authEmailField');
        var phoneWrap = document.getElementById('authPhoneField');
        if (emailWrap) {
            emailWrap.style.display = method === 'email' ? '' : 'none';
            var ei = emailWrap.querySelector('input');
            if (ei) { ei.required = method === 'email'; }
        }
        if (phoneWrap) {
            phoneWrap.style.display = method === 'phone' ? '' : 'none';
            var pi = phoneWrap.querySelector('input');
            if (pi) { pi.required = method === 'phone'; }
        }
        modalEl.querySelectorAll('.auth-method-tab').forEach(function (btn) {
            var on = btn.dataset.method === method;
            btn.className = 'btn btn-sm flex-fill fw-semibold auth-method-tab ' + (on ? 'btn-primary' : 'btn-outline-secondary');
        });
        if (method === 'email' && emailWrap) { var e2 = emailWrap.querySelector('input'); if (e2) e2.focus(); }
        if (method === 'phone' && phoneWrap) { var p2 = phoneWrap.querySelector('input'); if (p2) p2.focus(); }
    }

    /* ---- Hành động sau khi nhận JSON thành công ---- */
    function onSuccess(action, json) {
        if (action === 'forgot-send') {
            document.getElementById('authResetAccount').value = json.account || '';
            document.getElementById('authResetMethod').value = json.method || 'email';
            document.getElementById('authOtpHint').textContent =
                ((json.method === 'email') ? 'Mã xác nhận đã được gửi đến email ' : 'Mã xác nhận đã được gửi đến số điện thoại ') +
                (json.account || '');
            var sendForm = modalEl.querySelector('[data-auth-action="forgot-send"]');
            var verifyForm = document.getElementById('authForgotVerify');
            if (sendForm) { hideError(sendForm.querySelector('.auth-error')); sendForm.style.display = 'none'; }
            if (verifyForm) { verifyForm.style.display = ''; setTimeout(function () { var o = verifyForm.querySelector('input[name="otp"]'); if (o) o.focus(); }, 100); }
            return;
        }
        if (action === 'forgot-reset') {
            if (window.showToast) showToast(json.message || 'Đã đặt lại mật khẩu thành công.', 'success');
            window.openAuthModal('login');
            return;
        }
        // login / register: reload giữ nguyên trang (header sẽ hiện tài khoản)
        window.location.href = json.redirect || BASE;
    }

    /* ---- AJAX submit ---- */
    var ACTION_URL = {
        'login': '/dang-nhap',
        'register': '/dang-ky',
        'forgot-send': '/quen-mat-khau',
        'forgot-reset': '/quen-mat-khau'
    };

    modalEl.addEventListener('submit', function (e) {
        var form = e.target.closest('form.auth-form');
        if (!form) return;
        e.preventDefault();

        var action = form.dataset.authAction;
        var btn = form.querySelector('.auth-submit');
        var errBox = form.querySelector('.auth-error');
        hideError(errBox);
        setLoading(btn, true);

        fetch(BASE + (ACTION_URL[action] || '/'), {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: new FormData(form)
        }).then(function (r) {
            // Đọc body dạng text trước, rồi thử parse JSON: phân loại lỗi đúng nguồn
            // (server trả JSON, trang lỗi HTML, hay response rỗng) thay vì fallback chung chung.
            return r.text().then(function (body) {
                var json = null;
                try { json = JSON.parse(body); } catch (ignore) { json = null; }
                if (json && typeof json === 'object' && !Array.isArray(json)) {
                    return json;
                }
                // Server lỗi thật (HTML 500 / body rỗng) — không parse được JSON
                return {
                    ok: false,
                    message: 'Hệ thống đang gặp sự cố. Vui lòng thử lại sau.'
                };
            });
        }).then(function (json) {
            setLoading(btn, false);
            if (!json || json.ok !== true) {
                showError(errBox, (json && json.message) || 'Đã có lỗi xảy ra. Vui lòng thử lại.');
                return;
            }
            onSuccess(action, json);
        }).catch(function () {
            // Mất mạng / không kết nối được máy chủ (fetch reject)
            setLoading(btn, false);
            showError(errBox, 'Không thể kết nối máy chủ. Vui lòng thử lại.');
        });
    });

    /* ---- Các nút điều hướng trong modal ---- */
    modalEl.addEventListener('click', function (e) {
        var goto = e.target.closest('[data-auth-goto]');
        if (goto) { showPane(goto.dataset.authGoto); return; }

        var tab = e.target.closest('.auth-method-tab');
        if (tab) { activateMethod(tab.dataset.method); return; }

        var google = e.target.closest('[data-auth-google]');
        if (google) {
            window.location.href = BASE + '/dang-nhap/google?next=' + encodeURIComponent(window.location.href);
        }
    });

    /* ---- Skip thư mục: thoát modal bằng Esc/backdrop vẫn giữ Bootstrap ---- */
})();