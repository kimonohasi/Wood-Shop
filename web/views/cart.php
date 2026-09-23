<?php
/** Trang giỏ hàng */
declare(strict_types=1);
use WoodCon\Product;
$__gross = 0;
foreach ($items as $__it) {
    $__gross += (float)$__it['unit_price'] * (int)$__it['quantity'];
}
?>
<div class="container py-4">
    <div class="app-crumb"><a href="<?= BASE_URL ?>">Trang chủ</a><span class="sep">/</span><span>Giỏ hàng</span></div>
    <h1 class="app-section-title mb-4">Giỏ hàng của bạn</h1>

    <?php if (empty($items)): ?>
        <div class="app-empty">
            <?= icon('ms-shopping_bag', 'd-block mb-2', 'style="font-size:3rem;color:var(--wc-outline)"') ?>
            <p>Giỏ hàng đang trống.</p>
            <a href="<?= BASE_URL ?>" class="btn btn-primary">Tiếp tục mua sắm</a>
        </div>
    <?php else: ?>
        <div class="row g-4 app-cart-wrap">
            <div class="col-lg-8">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table app-cart-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th class="text-center" style="width:130px">Số lượng</th>
                                    <th class="text-end" style="width:140px">Thành tiền</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $__it): $__line = (int)round($__it['unit_price'] * $__it['quantity']); ?>
                                    <tr data-cart-id="<?= (int)$__it['id'] ?>">
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= e(image_url($__it['cover_image'] ?? '')) ?>" class="rounded" width="64" height="64" style="object-fit:cover" alt="">
                                                <div>
                                                    <a class="fw-semibold" href="<?= BASE_URL ?>/san-pham/<?= e($__it['slug']) ?>"><?= e($__it['product_name']) ?></a>
                                                    <?php if ($__it['variant_name']): ?><div class="small text-muted"><?= e($__it['variant_name']) ?>: <?= e($__it['variant_value']) ?></div><?php endif; ?>
                                                    <div class="small text-muted"><?= format_money((int)$__it['unit_price']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="qty-stepper justify-content-center" style="display:flex">
                                                <button type="button" data-act="minus" data-id="<?= (int)$__it['id'] ?>">-</button>
                                                <input type="text" data-id="<?= (int)$__it['id'] ?>" value="<?= (int)$__it['quantity'] ?>" readonly>
                                                <button type="button" data-act="plus" data-id="<?= (int)$__it['id'] ?>">+</button>
                                            </div>
                                        </td>
                                        <td class="text-end fw-bold" data-line-price data-line-total="<?= $__line ?>"><?= format_money($__line) ?></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-link text-danger" data-act="remove" data-id="<?= (int)$__it['id'] ?>" title="Xóa sản phẩm"><?= icon('ms-delete', 'style="font-size:18px;vertical-align:-3px"') ?></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-3">
                    <a href="<?= BASE_URL ?>" class="btn btn-outline-primary"><?= icon('ms-arrow_back', 'me-1', 'style="font-size:18px;vertical-align:-3px"') ?>Tiếp tục mua sắm</a>
                    <a href="<?= BASE_URL ?>/thanh-toan" class="btn btn-primary"><?= icon('ms-shopping_bag', 'me-1', 'style="font-size:18px;vertical-align:-3px"') ?>Thanh toán</a>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="summary-box p-4">
                    <h5 class="fw-bold mb-3">Tóm tắt đơn hàng</h5>
                    <div class="summary-row"><span>Tạm tính (hàng hóa)</span><span id="sumGoods"><?= format_money((int)$__gross) ?></span></div>
                    <div class="summary-row text-muted"><span>Phí vận chuyển</span><span>Tính khi thanh toán</span></div>
                    <div class="summary-row text-muted"><span>VAT</span><span>Đã gồm</span></div>
                    <div class="summary-row total"><span>Tổng cộng</span><span id="sumTotal"><?= format_money((int)$__gross) ?></span></div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    'use strict';

    function getBase()  { return window.WOODCON_BASE_URL || '/'; }
    function getToken() { return window.WOODCON_CSRF || ''; }

    function fmtMoney(v) {
        return Number(v || 0).toLocaleString('vi-VN') + ' đ';
    }
    function updateBadge(count) {
        var badge = document.getElementById('cartCount');
        if (badge) badge.textContent = count;
    }
    function setTotals(gross) {
        var g = Number(gross) || 0;
        var goods = document.getElementById('sumGoods');
        var total = document.getElementById('sumTotal');
        if (goods) goods.textContent = fmtMoney(g);
        if (total) total.textContent = fmtMoney(g);
    }
    function recomputeTotals() {
        var sum = 0;
        document.querySelectorAll('[data-line-total]').forEach(function (el) {
            sum += parseInt(el.getAttribute('data-line-total'), 10) || 0;
        });
        setTotals(sum);
    }
    function renderEmpty() {
        var wrap = document.querySelector('.app-cart-wrap');
        if (!wrap) return;
        var html = '<div class="app-empty">' +
            '<?= icon('ms-shopping_bag', 'd-block mb-2', 'style="font-size:3rem;color:var(--wc-outline)"') ?>' +
            '<p>Giỏ hàng đang trống.</p>' +
            '<a href="' + getBase() + '" class="btn btn-primary">Tiếp tục mua sắm</a>' +
            '</div>';
        wrap.innerHTML = html;
    }
    function showError(err) {
        var msg = (err && err.message) ? err.message : 'Lỗi kết nối, vui lòng thử lại.';
        if (window.WoodConToast) WoodConToast(msg, 'danger');
    }

    function send(data) {
        return fetch(getBase() + '/gio-hang', {method: 'POST', body: data, credentials: 'same-origin'})
            .then(function (r) {
                return r.json().catch(function () {
                    return {ok: false, message: 'Phản hồi không hợp lệ (HTTP ' + r.status + ').'};
                });
            })
            .then(function (res) {
                if (res && res.ok) return res;
                var err = new Error((res && res.message) || 'Không thể xử lý giỏ hàng.');
                err.handled = true;
                throw err;
            });
    }

    function handleQty(btn) {
        var id = btn.getAttribute('data-id');
        var input = document.querySelector('.qty-stepper input[data-id="' + id + '"]');
        if (!input) return;
        var q = parseInt(input.value, 10) || 1;
        q += btn.getAttribute('data-act') === 'minus' ? -1 : 1;
        if (q < 1) q = 1;

        var data = new FormData();
        data.append('_token', getToken());
        data.append('action', 'update');
        data.append('cart_id', id);
        data.append('quantity', q);
        btn.disabled = true;
        send(data)
            .then(function (res) {
                updateBadge(res.count);
                location.reload();
            })
            .catch(function (err) {
                btn.disabled = false;
                showError(err);
            });
    }

    function handleRemove(btn) {
        var id = btn.getAttribute('data-id');
        var original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        var data = new FormData();
        data.append('_token', getToken());
        data.append('action', 'remove');
        data.append('cart_id', id);
        send(data)
            .then(function (res) {
                var row = document.querySelector('tr[data-cart-id="' + id + '"]');
                if (row) row.remove();
                if (typeof res.gross === 'number') setTotals(res.gross);
                else recomputeTotals();
                if (typeof res.count === 'number') updateBadge(res.count);
                if (window.WoodConToast) WoodConToast('Đã xóa sản phẩm khỏi giỏ hàng.', 'success');
                if (!document.querySelector('tr[data-cart-id]')) renderEmpty();
            })
            .catch(function (err) {
                btn.disabled = false;
                btn.innerHTML = original;
                showError(err);
            });
    }

    // Event delegation: luôn bắt được cả các dòng được render động sau này
    document.addEventListener('click', function (e) {
        var target = e.target;
        if (!target || !target.closest) return;
        var qtyBtn = target.closest('.qty-stepper button');
        if (qtyBtn) { handleQty(qtyBtn); return; }
        var removeBtn = target.closest('[data-act="remove"]');
        if (removeBtn) handleRemove(removeBtn);
    });
})();
</script>