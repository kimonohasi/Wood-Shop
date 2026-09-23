<?php
/** Trang thanh toán (luồng COD: preview -> gate-check -> OTP modal -> đặt đơn) */
declare(strict_types=1);
use WoodCon\Product;
use WoodCon\Shipping;
$__user = $user;
$__gross = 0;
foreach ($items as $__it) {
    $__gross += (float)$__it['unit_price'] * (int)$__it['quantity'];
}
// Ước lượng mặc định loại vận chuyển trình bày ban đầu (khớp mặc định server)
try {
    $__autoShipOpt = Shipping::cartShippingOptions($items);
    // Loại 2 hiện tại do Viettel Post tự động tính cước: chỉ hiện khi tích hợp đang bật + có token
    if (!\WoodCon\ViettelPost::configured() && $__autoShipOpt['type2']) {
        $__autoShipOpt['type2'] = false;
    }
    $__defType = $__autoShipOpt['type1'] ? Shipping::TYPE_SHOP
        : ($__autoShipOpt['type2'] ? Shipping::TYPE_CARRIER : Shipping::TYPE_SHOP);
} catch (\Throwable $__e) {
    $__autoShipOpt = ['type1' => true, 'type2' => true, 'install_available' => false, 'install_fee' => 0.0, 'install_detail' => []];
    $__defType = Shipping::TYPE_SHOP;
}
$__labels = Shipping::typeLabels();
$_memberTier = $memberTier ?? null;
?>
<div class="container py-4">
    <div class="app-crumb"><a href="<?= BASE_URL ?>">Trang chủ</a><span class="sep">/</span><a href="<?= BASE_URL ?>/gio-hang">Giỏ hàng</a><span class="sep">/</span><span>Thanh toán</span></div>
    <h1 class="app-section-title mb-4">Thanh toán</h1>

    <form id="checkoutForm" method="post" action="<?= BASE_URL ?>/thanh-toan">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">

        <div class="row g-4">
            <div class="col-lg-7">
                <!-- Thông tin giao hàng -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Thông tin giao hàng</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small">Họ tên người nhận *</label>
                                <input type="text" class="form-control" name="customer_name" id="custName" value="<?= e($__user['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Số điện thoại *</label>
                                <input type="tel" class="form-control" name="customer_phone" id="custPhone" value="<?= e($__user['phone'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Email (tùy chọn)</label>
                                <input type="email" class="form-control" name="customer_email" value="<?= e($__user['email'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Địa chỉ cụ thể *</label>
                                <?php
                                    $__goongOn = \WoodCon\GoongService::enabled();
                                    $__goongCfg = [
                                        'latName'      => 'delivery_lat',
                                        'lngName'      => 'delivery_lng',
                                        'wardSel'      => '#custWard',
                                        'districtSel'  => '#custDistrict',
                                        'citySel'      => '#custCity',
                                    ];
                                    $__goongLoc = \WoodCon\GoongService::biasLocationString();
                                    if ($__goongLoc !== '') {
                                        [$__glat, $__glng] = explode(',', $__goongLoc);
                                        $__goongCfg['centerLat'] = $__glat;
                                        $__goongCfg['centerLng'] = $__glng;
                                    }
                                ?>
                                <input type="text" class="form-control" name="address" id="custAddress"
                                       value="<?= e($__user['address'] ?? '') ?>" placeholder="Số nhà, tên đường..."
                                       data-goong-autocomplete="<?= e(json_encode($__goongCfg, JSON_UNESCAPED_UNICODE)) ?>" required>
                            </div>
                            <div class="col-md-4"><input type="text" class="form-control" name="ward" id="custWard" placeholder="Phường/Xã" value="<?= e($__user['ward'] ?? '') ?>"></div>
                            <div class="col-md-4"><input type="text" class="form-control" name="district" id="custDistrict" placeholder="Quận/Huyện" value="<?= e($__user['district'] ?? '') ?>"></div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="city" id="custCity" placeholder="Tỉnh/Thành phố" value="<?= e($__user['city'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Ghi chú đơn hàng</label>
                                <textarea class="form-control" name="note" rows="2" placeholder="Ghi chú giao hàng (nếu có)"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Phương thức vận chuyển (2 loại - Phần 2) -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-1">Phương thức vận chuyển</h5>
                        <p class="small text-muted mb-3">Phí vận chuyển được cập nhật ngay sau khi điền Tỉnh/Thành phố và chọn loại.</p>
                        <div class="d-grid gap-2 mb-3">
                            <?php if ($__autoShipOpt['type1']): ?>
                                <div class="border rounded method-item ship-type-card overflow-hidden">
                                    <label class="d-flex align-items-center gap-2 p-3 mb-0" style="cursor:pointer">
                                        <input type="radio" name="shipping_type" value="<?= Shipping::TYPE_SHOP ?>" <?= $__defType === Shipping::TYPE_SHOP ? 'checked' : '' ?>>
                                        <?= icon('ms-home_repair_service', 'style="font-size:28px;color:var(--wc-secondary)"') ?>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold"><?= e($__labels[Shipping::TYPE_SHOP]) ?></div>
                                            <small class="text-muted" id="shipDesc_shop">Nhân viên cửa hàng giao tận nơi và lắp đặt. Phí tính theo khoảng cách.</small>
                                            <span class="ship-fee d-block small fw-semibold mt-1" data-type="<?= Shipping::TYPE_SHOP ?>">—</span>
                                        </div>
                                    </label>
                                    <div class="px-3 pb-3 d-none" id="installWrap">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="install_requested" value="1" id="installCheck">
                                            <label class="form-check-label" for="installCheck">
                                                <span class="fw-semibold" id="installLabel"><?= e((string)get_setting('ship_label_install', 'Lắp đặt tại nhà')) ?></span>
                                                <span class="text-muted small" id="installFeeText"></span>
                                            </label>
                                        </div>
                                        <div class="rounded bg-light p-2 small d-none" id="installSummary">
                                            <div class="d-flex justify-content-between"><span class="text-muted">Vận chuyển:</span><span id="installSumShip" class="fw-semibold">—</span></div>
                                            <div class="d-flex justify-content-between"><span class="text-muted">Lắp đặt:</span><span id="installSumFee" class="fw-semibold">—</span></div>
                                            <div class="d-flex justify-content-between fw-bold border-top mt-1 pt-1"><span>Tổng:</span><span id="installSumTotal" class="fw-bold">—</span></div>
                                            <div class="text-muted mt-1">Không cần lắp đặt? Bỏ tích ô trên để chỉ trả phí vận chuyển.</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($__autoShipOpt['type2']): ?>
                                <label class="d-flex align-items-center gap-2 p-3 rounded border method-item ship-type-card">
                                    <input type="radio" name="shipping_type" value="<?= Shipping::TYPE_CARRIER ?>" <?= $__defType === Shipping::TYPE_CARRIER ? 'checked' : '' ?>>
                                    <?= icon('ms-local_shipping', 'style="font-size:28px;color:var(--wc-secondary)"') ?>
                                    <div>
                                        <div class="fw-semibold"><?= e($__labels[Shipping::TYPE_CARRIER]) ?></div>
                                        <small class="text-muted">Tính cước tự động qua Viettel Post theo địa chỉ + khối lượng (không gồm lắp đặt).</small>
                                        <span class="ship-fee d-block small fw-semibold mt-1" data-type="<?= Shipping::TYPE_CARRIER ?>">—</span>
                                    </div>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Chọn phương thức thanh toán -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Phương thức thanh toán</h5>
                        <div class="d-grid gap-2">
                            <label class="d-flex align-items-center gap-2 p-3 rounded border method-item">
                                <input type="radio" name="payment_method" value="cod" checked>
                                <?= icon('ms-payments', 'style="font-size:28px;color:var(--wc-secondary)"') ?>
                                <div><div class="fw-semibold">Thanh toán khi nhận hàng (COD)</div><small class="text-muted">Trả tiền khi nhận sản phẩm</small></div>
                            </label>
                            <label class="d-flex align-items-center gap-2 p-3 rounded border method-item">
                                <input type="radio" name="payment_method" value="bank">
                                <?= icon('ms-account_balance', 'style="font-size:28px;color:var(--wc-secondary)"') ?>
                                <div><div class="fw-semibold">Chuyển khoản ngân hàng</div><small class="text-muted">Chuyển khoản trước khi giao hàng</small></div>
                            </label>
                            <label class="d-flex align-items-center gap-2 p-3 rounded border method-item">
                                <input type="radio" name="payment_method" value="qr">
                                <?= icon('ms-qr_code', 'style="font-size:28px;color:var(--wc-secondary)"') ?>
                                <div><div class="fw-semibold">Quét mã QR</div><small class="text-muted">Momo / VNPay / ZaloPay</small></div>
                            </label>
                            <label class="d-flex align-items-center gap-2 p-3 rounded border method-item">
                                <input type="radio" name="payment_method" value="wallet">
                                <?= icon('ms-account_balance_wallet', 'style="font-size:28px;color:var(--wc-secondary)"') ?>
                                <div><div class="fw-semibold">Ví điện tử</div><small class="text-muted">Thanh toán online tức thì</small></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tóm tắt -->
            <div class="col-lg-5">
                <div class="summary-box p-4 sticky-top" style="top:120px">
                    <h5 class="fw-bold mb-3">Đơn hàng</h5>
                    <?php foreach ($items as $__it): ?>
                        <div class="d-flex justify-content-between align-items-center small py-1">
                            <span class="text-truncate pe-2"><?= e($__it['product_name']) ?> × <?= (int)$__it['quantity'] ?></span>
                            <span class="fw-semibold"><?= format_money((int)round($__it['unit_price'] * $__it['quantity'])) ?></span>
                        </div>
                    <?php endforeach; ?>

                    <hr class="divider-dash">

                    <!-- Mã giảm giá -->
                    <div class="mb-3">
                        <input type="text" class="form-control form-control-sm mb-1" name="discount_code" id="discountCode" placeholder="Mã giảm giá (nếu có)">
                        <input type="text" class="form-control form-control-sm mb-2" name="freeship_code" id="freeshipCode" placeholder="Mã freeship (nếu có)">
                        <button type="button" id="btnPreview" class="btn btn-sm btn-outline-secondary w-100">Áp dụng</button>
                    </div>

                    <?php $__pts = (int)($__user['points'] ?? 0); if ($__pts > 0): ?>
                    <div class="mb-3">
                        <label class="small text-muted d-block mb-1">Điểm thưởng (số dư: <strong id="ptsBalance"><?= number_format($__pts) ?></strong> điểm)</label>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control" name="use_points" id="usePoints" min="0" max="<?= $__pts ?>" value="0" placeholder="Số điểm dùng">
                            <button type="button" id="btnUseAll" class="btn btn-outline-primary" title="Dùng hết điểm">Dùng hết</button>
                        </div>
                        <div class="small text-success mt-1 d-none" id="ptsHint"></div>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="use_points" id="usePoints" value="0">
                    <?php endif; ?>

                    <div class="summary-row"><span>Tạm tính (đã gồm thuế)</span><span id="sumTmp"><?= format_money((int)$__gross) ?></span></div>
                    <div class="summary-row"><span>Vùng giao hàng</span><span id="sumZone">—</span></div>
                    <div class="summary-row"><span>Phí vận chuyển</span><span id="sumShip">—</span></div>
                    <div class="summary-row text-muted d-none" id="sumVatShipRow"><span>VAT phí vận chuyển (đã gồm trong giá)</span><span id="sumVatShip">—</span></div>
                    <div class="summary-row d-none" id="sumInstallRow"><span id="sumInstallLabel">Phí lắp đặt</span><span id="sumInstall">—</span></div>
                    <div class="summary-row text-muted d-none" id="sumVatInstallRow"><span>VAT phí lắp đặt (đã gồm trong giá)</span><span id="sumVatInstall">—</span></div>
                    <div class="summary-row"><span>Điểm thưởng</span><span id="sumPoints" class="text-success">—</span></div>
                    <div class="summary-row"><span>Chiết khấu</span><span id="sumDiscount" class="text-success">—</span></div>
                    <div class="summary-row"><span>Miễn phí ship</span><span id="sumFreeship" class="text-success">—</span></div>
                    <div class="summary-row text-muted"><span>VAT sản phẩm (đã gồm trong giá)</span><span id="sumVat">—</span></div>
                    <div id="vatBreakdown" class="small text-muted d-none"></div>
                    <div class="summary-row total mt-2"><span>Tổng cộng</span><span id="sumTotal"><?= format_money((int)$__gross) ?></span></div>
                    <input type="hidden" name="shipping_zone" id="shippingZone">

                    <?php if ($_memberTier): ?>
                        <div class="small alert alert-light border mt-2 mb-2 py-2 px-3">
                            <?= icon('bi-stars', 'me-1') ?>
                            Hạng thành viên của bạn: <strong><?= e((string)($_memberTier['name'] ?? '')) ?></strong>
                            <?php if (in_array(strtoupper(trim((string)($_memberTier['name'] ?? ''))), ['VIP', 'DIAMOND'], true)): ?>
                                <div class="text-success">Đơn COD được ưu tiên xử lý nhanh, không cần xác thực OTP.</div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary w-100 btn-lg mt-3" id="btnPlaceOrder"><?= icon('ms-shopping_bag', 'me-1', 'style="font-size:18px;vertical-align:-3px"') ?>Đặt hàng</button>
                    <div class="small text-muted text-center mt-2">Tất cả giá đã gồm VAT theo quy định hiện hành.</div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- ==================== MODAL OTP ==================== -->
<div class="modal fade" id="otpModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><?= icon('ms-verified_user', 'me-1', 'style="font-size:22px;vertical-align:-5px;color:var(--wc-secondary)"') ?>Xác thực số điện thoại</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="small text-muted mb-3">Xác thực số điện thoại giúp đảm bảo đơn hàng được giao đúng và nhanh hơn.</p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0" id="btnSendOtp">Gửi mã OTP</button>
                    <span class="small text-muted align-self-center" id="otpResendInfo"></span>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <input type="text" class="form-control" id="otpInput" placeholder="Nhập mã OTP" maxlength="6" inputmode="numeric" autocomplete="one-time-code">
                    <button type="button" class="btn btn-primary flex-shrink-0" id="btnVerifyOtp">Xác thực</button>
                </div>
                <div class="small text-muted mt-2" id="otpCountdown"></div>
                <div class="small text-danger mt-1 d-none" id="otpErr"></div>
                <div class="small text-success mt-1 d-none" id="otpOkModal">✓ Đã xác thực số điện thoại</div>
                <div class="small text-warning mt-1 d-none" id="otpDevCode"></div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const base = window.WOODCON_BASE_URL || '';
    const csrf = window.WOODCON_CSRF || '';
    // Cờ bật/tắt Goong Maps do server phát (goong-autocomplete.js đọc khi nạp ở footer)
    window.WOODCON_GOONG_ENABLED = <?= $__goongOn ? 'true' : 'false' ?>;
    const TYPE_SHOP = 'shop';
    let otpVerified = false;

    function ajax(action, body, then) {
        const data = new FormData();
        data.append('_token', csrf);
        data.append('action', action);
        for (const k in body) data.append(k, body[k]);
        fetch(base + '/thanh-toan', {method: 'POST', body: data, credentials: 'same-origin'})
            .then(r => r.json()).then(then).catch(() => WoodConToast('Lỗi kết nối, vui lòng thử lại.', 'danger'));
    }
    function money(v) { return Number(v).toLocaleString('vi-VN') + ' đ'; }
    function selectedShipType() {
        const r = document.querySelector('input[name=shipping_type]:checked');
        return r ? r.value : (document.querySelector('input[name=shipping_type]')?.value || TYPE_SHOP);
    }

    // ============ PREVIEW ============
    function preview() {
        ajax('preview', {
            city: document.getElementById('custCity').value,
            district: document.getElementById('custDistrict')?.value || '',
            payment_method: document.querySelector('input[name=payment_method]:checked')?.value || 'cod',
            shipping_type: selectedShipType(),
            install_requested: document.getElementById('installCheck').checked ? '1' : '0',
            discount_code: document.getElementById('discountCode').value,
            freeship_code: document.getElementById('freeshipCode').value,
            use_points: document.getElementById('usePoints').value
        }, res => {
            if (!res.ok) { WoodConToast(res.message, 'danger'); return; }
            document.getElementById('sumTmp').textContent = money(res.gross_goods);
            document.getElementById('sumZone').textContent = res.zone_label;
            document.getElementById('sumShip').textContent = money(res.shipping_fee);
            document.getElementById('sumDiscount').textContent = res.discount ? '-' + money(res.discount) : '—';
            document.getElementById('sumFreeship').textContent = res.freeship ? '-' + money(res.freeship) : '—';
            document.getElementById('sumPoints').textContent = res.points_discount ? '-' + money(res.points_discount) : '—';
            document.getElementById('sumVat').textContent = money(res.vat_san_pham ?? res.vat_amount);
            const $vbd = document.getElementById('vatBreakdown');
            if ($vbd && Array.isArray(res.vat_breakdown) && res.vat_breakdown.length > 0) {
                const multi = res.vat_breakdown.length > 1;
                $vbd.innerHTML = res.vat_breakdown.map(b =>
                    '<div class="d-flex justify-content-between"><span>Thuế ' + (Number(b.rate) % 1 === 0 ? Number(b.rate) : Number(b.rate).toFixed(2)) + '%: ' + Number(b.vat).toLocaleString('vi-VN') + ' đ</span></div>'
                ).join('');
                $vbd.classList.toggle('d-none', !multi);
            }
            document.getElementById('sumTotal').textContent = money(res.total);
            document.getElementById('shippingZone').value = res.zone;

            // Cập nhật 2 thẻ loại vận chuyển
            (res.shipping_options || []).forEach(o => {
                const feeEl = document.querySelector('.ship-fee[data-type="' + o.type + '"]');
                if (feeEl) feeEl.textContent = money(o.fee);
            });

            // Tick lắp đặt: CHỈ hiện khi đang chọn Loại 1 (shop) VÀ loại đó hỗ trợ lắp đặt.
            // Khi chuyển sang Loại 2 (carrier) hoặc không còn hỗ trợ -> ẩn hẳn ô tick.
            const $iw = document.getElementById('installWrap');
            const shipSel = selectedShipType();
            const shopOpt = (res.shipping_options || []).find(o => o.type === TYPE_SHOP);
            const installPickable = !!(shopOpt && shopOpt.install_available && shipSel === TYPE_SHOP);
            if (installPickable) {
                $iw.classList.remove('d-none');
                const fee = shopOpt.install_fee;
                document.getElementById('installFeeText').textContent = ' (+' + money(fee) + ')';
            } else {
                $iw.classList.add('d-none');
            }

            // Tóm tắt 2 khoản + tổng ngay trong khung Loại 1 (chỉ khi khách ĐÃ tick lắp đặt)
            const $isum = document.getElementById('installSummary');
            const installChecked = document.getElementById('installCheck').checked;
            if (installPickable && installChecked && shopOpt.install_fee > 0) {
                $isum.classList.remove('d-none');
                document.getElementById('installSumShip').textContent = money(shopOpt.fee);
                document.getElementById('installSumFee').textContent = money(shopOpt.install_fee);
                document.getElementById('installSumTotal').textContent = money(Number(shopOpt.fee) + Number(shopOpt.install_fee));
            } else {
                $isum.classList.add('d-none');
            }

            // Dòng VAT phí vận chuyển (tách ngược, đã gồm trong giá)
            document.getElementById('sumVatShip').textContent = money(res.vat_shipping || 0);
            document.getElementById('sumVatShipRow').classList.toggle('d-none', !(res.vat_shipping > 0));

            // Dòng Phí lắp đặt + VAT phí lắp đặt (chỉ khi khách đang tích + Loại 1 có phí)
            if (res.install_requested && res.install_fee > 0) {
                document.getElementById('sumInstallRow').classList.remove('d-none');
                document.getElementById('sumVatInstallRow').classList.remove('d-none');
                document.getElementById('sumInstall').textContent = money(res.install_fee);
                document.getElementById('sumVatInstall').textContent = money(res.vat_install || 0);
            } else {
                document.getElementById('sumInstallRow').classList.add('d-none');
                document.getElementById('sumVatInstallRow').classList.add('d-none');
            }

            document.getElementById('ptsBalance') && (document.getElementById('ptsBalance').textContent = Number(res.points_balance).toLocaleString('vi-VN'));
            const $up = document.getElementById('usePoints');
            if ($up && $up.max) $up.max = res.points_can_use;
            const $hint = document.getElementById('ptsHint');
            if ($hint) {
                if (res.points_can_use > 0) {
                    $hint.classList.remove('d-none');
                    $hint.textContent = 'Có thể dùng tối đa ' + Number(res.points_can_use).toLocaleString('vi-VN') + ' điểm (Mỗi điểm = ' + Number(res.point_value).toLocaleString('vi-VN') + 'đ).';
                } else $hint.classList.add('d-none');
            }
        });
    }

    document.getElementById('btnPreview').addEventListener('click', preview);
    document.getElementById('usePoints')?.addEventListener('input', preview);
    document.getElementById('btnUseAll')?.addEventListener('click', () => {
        const pts = document.getElementById('usePoints');
        pts.value = pts.max || 0;
        preview();
    });
    document.getElementById('custCity').addEventListener('change', preview);
    document.getElementById('custCity').addEventListener('input', () => {
        const el = document.getElementById('custCity');
        if (el.dataset.t) clearTimeout(el.dataset.t);
        el.dataset.t = setTimeout(preview, 500);
    });
    document.getElementById('custDistrict')?.addEventListener('input', preview);
    document.querySelectorAll('input[name=shipping_type]').forEach(r => r.addEventListener('change', () => {
        document.getElementById('sumInstallRow').classList.add('d-none');
        document.getElementById('sumVatInstallRow').classList.add('d-none');
        document.getElementById('installCheck').checked = false;
        document.getElementById('installWrap').classList.add('d-none');
        preview();
    }));
    document.getElementById('installCheck').addEventListener('change', () => {
        const on = document.getElementById('installCheck').checked;
        document.getElementById('sumInstallRow').classList.toggle('d-none', !on);
        document.getElementById('sumVatInstallRow').classList.toggle('d-none', !on);
        document.getElementById('installSummary')?.classList.add('d-none');
        preview();
    });

    // ============ OTP MODAL ============
    const otpModal = document.getElementById('otpModal');
    let otpTimer = null;

    function startCountdown(seconds, info) {
        const el = document.getElementById('otpCountdown');
        const infoEl = document.getElementById('otpResendInfo');
        const btn = document.getElementById('btnSendOtp');
        btn.disabled = true;
        clearInterval(otpTimer);
        otpTimer = setInterval(() => {
            if (seconds <= 0) {
                clearInterval(otpTimer);
                btn.disabled = false;
                el.textContent = '';
                if (info === 'resend') {
                    infoEl.textContent = '';
                    el.textContent = 'Bạn có thể gửi lại mã OTP.';
                }
                return;
            }
            const m = String(Math.floor(seconds / 60)).padStart(2, '0');
            const s = String(seconds % 60).padStart(2, '0');
            el.textContent = 'Mã có hiệu lực trong ' + m + ':' + s;
            seconds--;
        }, 1000);
        if (info === 'resend') { infoEl.textContent = 'Gửi lại mã sau 1 phút'; }
    }

    document.getElementById('btnSendOtp').addEventListener('click', sendOtp);
    function customerName() {
        const el = document.getElementById('custName');
        return el ? String(el.value).trim() : '';
    }
    function customerAddress() {
        const el = document.getElementById('custAddress');
        return el ? String(el.value).trim() : '';
    }
    function sendOtp() {
        const phone = document.getElementById('custPhone').value.trim();
        if (!phone) { WoodConToast('Vui lòng nhập số điện thoại.', 'warning'); return; }
        document.getElementById('otpErr').classList.add('d-none');
        document.getElementById('otpDevCode').classList.add('d-none');
        ajax('send-otp', {phone: phone, name: customerName(), address: customerAddress()}, res => {
            if (res.otp_exempt) {
                otpVerified = true;
                document.getElementById('otpOkModal').classList.remove('d-none');
                document.getElementById('otpOkModal').textContent = res.message || 'Đơn được xử lý nhanh, không cần OTP.';
                document.getElementById('otpCountdown').textContent = '';
                WoodConToast(res.message || 'Đơn được xử lý nhanh.', 'success');
                return;
            }
            if (res.not_required) {
                otpVerified = true;
                document.getElementById('otpOkModal').classList.remove('d-none');
                document.getElementById('otpOkModal').textContent = 'Số điện thoại đã được xác thực trước đó.';
                return;
            }
            if (!res.ok) {
                const $err = document.getElementById('otpErr');
                $err.textContent = res.message || 'Không thể gửi mã.';
                $err.classList.remove('d-none');
                if (res.blocked) { clearInterval(otpTimer); document.getElementById('btnSendOtp').disabled = false; }
                return;
            }
            document.getElementById('otpDevCode').classList.remove('d-none');
            document.getElementById('otpDevCode').textContent = res.dev_code ? ('Mã OTP demo: ' + res.dev_code) : '';
            document.getElementById('otpInput').focus();
            startCountdown(300, 'noresend');
        });
    }

    document.getElementById('btnVerifyOtp').addEventListener('click', () => {
        const phone = document.getElementById('custPhone').value.trim();
        const code = document.getElementById('otpInput').value.trim();
        if (!code) { WoodConToast('Vui lòng nhập mã OTP.', 'warning'); return; }
        ajax('verify-otp', {phone: phone, otp: code}, res => {
            if (!res.ok) { WoodConToast(res.message, 'danger'); return; }
            otpVerified = true;
            clearInterval(otpTimer);
            document.getElementById('otpOkModal').classList.remove('d-none');
            document.getElementById('otpCountdown').textContent = '✓ Đã xác thực';
            WoodConToast('Đã xác thực số điện thoại.', 'success');
            setTimeout(() => { bootstrap.Modal.getInstance(otpModal)?.hide(); }, 800);
        });
    });

    function openOtpModal() {
        const modal = bootstrap.Modal.getOrCreateInstance(otpModal);
        modal.show();
        document.getElementById('btnSendOtp').disabled = false;
        document.getElementById('otpCountdown').textContent = '';
    }

    // ============ SUBMIT: gate-check + OTP ============
    let allowSubmit = false;
    document.getElementById('checkoutForm').addEventListener('submit', e => {
        if (allowSubmit) return;
        const method = document.querySelector('input[name=payment_method]:checked').value;
        if (method !== 'cod') return; // thanh toán trước: không cần OTP
        e.preventDefault();

        const phone = document.getElementById('custPhone').value.trim();
        if (!phone) { WoodConToast('Vui lòng nhập số điện thoại.', 'warning'); return; }

        const btn = document.getElementById('btnPlaceOrder');
        btn.disabled = true;

        ajax('gate-check', {phone: phone, name: customerName(), address: customerAddress()}, res => {
            btn.disabled = false;
            if (!res.ok || !res.gate) { WoodConToast('Không kiểm tra được, vui lòng thử lại.', 'danger'); return; }
            const g = res.gate;
            // Tier Đỏ: thông báo trung lập + chuyển hướng thanh toán trước
            if ((g.payment_allowed || []).indexOf('cod') === -1) {
                WoodConToast('Đơn hàng này cần thanh toán trước để đảm bảo xử lý nhanh nhất.', 'danger');
                const bank = document.querySelector('input[name=payment_method][value="bank"]');
                if (bank) { bank.checked = true; }
                return;
            }
            if (g.otp_exempt) {
                otpVerified = true;
                doSubmit();
                return;
            }
            if (g.otp_required && !otpVerified) {
                openOtpModal();
                sendOtp();
                return;
            }
            doSubmit();
        });
    });

    function doSubmit() {
        allowSubmit = true;
        document.getElementById('checkoutForm').submit();
    }
})();
</script>