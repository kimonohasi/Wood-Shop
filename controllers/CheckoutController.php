<?php
/**
 * WoodCon - Thanh toán / đặt hàng
 * Luồng COD mới (Phần 1-4):
 *   - Giá đã GỒM VAT: "Thành tiền" = Giá x SL; "Tạm tính" = tổng Thành tiền (gồm thuế).
 *   - Phần 2: chọn 1 trong 2 loại vận chuyển (Loại 1 giao & lắp đặt / Loại 2 đơn vị vận chuyển) + tick lắp đặt.
 *   - Phần 3: popup OTP + SMS thật (nếu đã cấu hình cổng SMS).
 *   - Phần 4: thành viên VIP/Diamond miễn OTP (trừ tier Đỏ).
 * Trust gate: đỏ = chặn COD (thông báo trung lập), vàng = OTP, nghi ngờ = manual_verifying.
 */

declare(strict_types=1);

namespace WoodCon\Controllers;

use WoodCon\Cart;
use WoodCon\Checkout;
use WoodCon\Order;
use WoodCon\Otp;
use WoodCon\Product;
use WoodCon\Shipping;
use WoodCon\Sms;
use WoodCon\TrustEngine;
use WoodCon\User;

class CheckoutController extends BaseController
{
    /** Trang thanh toán + các endpoint AJAX con trong cùng route */
    public function index(): void
    {
        // ------- AJAX -------
        if ($this->isPost()) {
            $action = $this->post('action', '');
            if ($action !== '') {
                $this->handleAjax($action);
            }
            // POST đặt đơn (không có action)
            $this->place();
        }

        $items = Cart::items();
        if (empty($items)) {
            set_flash('warning', 'Giỏ hàng của bạn đang trống.');
            redirect(BASE_URL . '/gio-hang');
        }

        $user = current_user();
        $this->render('checkout', [
            'pageTitle' => 'Thanh toán - WoodCon',
            'items'     => $items,
            'user'      => $user,
            'zones'     => Shipping::zones(),
            'memberTier'=> $user ? User::membershipTier((int)$user['id']) : null,
        ]);
    }

    private function handleAjax(string $action): never
    {
        try {
            switch ($action) {
                case 'preview':      $this->preview(); break;
                case 'gate-check':   $this->gateCheck(); break;
                case 'send-otp':     $this->sendOtp(); break;
                case 'verify-otp':   $this->verifyOtp(); break;
                case 'goong-autocomplete':   $this->goongAutocomplete(); break;
                case 'goong-place-detail':   $this->goongPlaceDetail(); break;
                case 'goong-reverse-geocode': $this->goongReverseGeocode(); break;
                default:
                    json_response(['ok' => false, 'message' => 'Hành động không hợp lệ.'], 400);
            }
        } catch (\Throwable $e) {
            write_log('error', 'Checkout ajax [' . $action . ']: ' . $e->getMessage());
            json_response(['ok' => false, 'message' => 'Lỗi hệ thống, vui lòng thử lại.'], 500);
        }
        exit;
    }

    /** Tính tổng giá + phí vận chuyển (2 loại) + phí lắp đặt theo lựa chọn hiện tại */
    private function preview(): never
    {
        $items = $this->cartItems();
        if (empty($items)) {
            json_response(['ok' => false, 'message' => 'Giỏ hàng trống.']);
        }

        $city = trim($this->post('city', ''));
        $district = trim($this->post('district', ''));
        $zone = Shipping::detectZone($city);
        $shipOpt = Shipping::cartShippingOptions($items);
        $grossGoodsValue = array_sum(array_map(fn($i) => (float)$i['unit_price'] * (int)$i['quantity'], $items));
        $hasFreeshipCode = (string)$this->post('freeship_code', '') !== '';

        // Loại 2 hiện tại do Viettel Post tự động tính cước (khi bật + có token + map được địa chỉ)
        $vtp = null;
        if ($shipOpt['type2']) {
            $codEstimate = $this->post('payment_method', 'cod') === 'cod' ? $grossGoodsValue : 0.0;
            $vtp = \WoodCon\ViettelPost::calculateForCity($items, $city, $district, $codEstimate);
            if (!$vtp['available']) {
                $shipOpt['type2'] = false;
            }
        }

        // Xác định loại vận chuyển được chọn (ưu tiên chính xác, fallback theo khả dụng)
        $requested = (string)$this->post('shipping_type', '');
        $selected = null;
        if ($requested === Shipping::TYPE_SHOP && $shipOpt['type1']) {
            $selected = Shipping::TYPE_SHOP;
        } elseif ($requested === Shipping::TYPE_CARRIER && $shipOpt['type2']) {
            $selected = Shipping::TYPE_CARRIER;
        } elseif ($shipOpt['type1']) {
            $selected = Shipping::TYPE_SHOP;
        } elseif ($shipOpt['type2']) {
            $selected = Shipping::TYPE_CARRIER;
        } else {
            $selected = Shipping::TYPE_CARRIER;
        }

        $shipping = $selected === Shipping::TYPE_SHOP
            ? Shipping::type1Fee($city)
            : ($vtp ? $vtp['fee'] : Shipping::carrierFee($items, $zone));
        // Miễn phí vận chuyển theo đúng bậc/vùng của loại đã chọn (khớp placeOrder).
        // Khi có mã freeship thì để applyVouchers xử lý, không tự zero ở đây.
        if (!$hasFreeshipCode && Shipping::isFreeShipping($grossGoodsValue, $selected, $city, $zone)) {
            $shipping = 0;
        }

        $installRequested = (bool)$this->post('install_requested', false);
        $installFee = ($selected === Shipping::TYPE_SHOP && $installRequested && $shipOpt['install_available'])
            ? $shipOpt['install_fee']
            : 0.0;

        $userId = current_user()['id'] ?? null;
        // Giảm giá hạng thành viên trong preview (khớp placeOrder) — snapshot % theo user hiện tại
        $tierPercent = 0.0;
        if ($userId) {
            $tierRow = \WoodCon\User::membershipTier((int)$userId);
            $tierPercent = $tierRow ? max(0, (float)($tierRow['discount_percent'] ?? 0)) : 0.0;
        }
        $voucher = Checkout::applyVouchers(
            $items,
            $this->post('discount_code', ''),
            $this->post('freeship_code', ''),
            $shipping,
            $installFee,
            $userId ? (int)$userId : null,
            $tierPercent
        );

        if (!$voucher['valid']) {
            json_response(['ok' => false, 'message' => $voucher['error']]);
        }
        $mock = $voucher['totals'];
        $points = Checkout::computePoints($mock, $userId ? (int)$userId : null, (int)$this->post('use_points', 0));

        // Danh sách 2 loại vận chuyển để UI vẽ thẻ chọn (kèm phí tính sẵn, khớp ngưỡng freeship riêng từng loại)
        $options = [];
        if ($shipOpt['type1']) {
            $shopFee = Shipping::type1Fee($city);
            $options[] = [
                'type'              => Shipping::TYPE_SHOP,
                'label'             => Shipping::typeLabels()[Shipping::TYPE_SHOP],
                'fee'               => !$hasFreeshipCode && Shipping::isFreeShipping($grossGoodsValue, Shipping::TYPE_SHOP, $city, $zone) ? 0 : $shopFee,
                'install_available' => $shipOpt['install_available'],
                'install_fee'       => $shipOpt['install_fee'],
            ];
        }
        if ($shipOpt['type2']) {
            $carrierFee = $vtp ? $vtp['fee'] : Shipping::carrierFee($items, $zone);
            $options[] = [
                'type'              => Shipping::TYPE_CARRIER,
                'label'             => Shipping::typeLabels()[Shipping::TYPE_CARRIER],
                'fee'               => !$hasFreeshipCode && Shipping::isFreeShipping($grossGoodsValue, Shipping::TYPE_CARRIER, $city, $zone) ? 0 : $carrierFee,
                'install_available' => false,
                'install_fee'       => 0,
            ];
        }

        $label = Shipping::typeLabels();
        json_response([
            'ok' => true,
            'zone'          => $zone,
            'zone_label'    => Shipping::zones()[$zone],
            'shipping_fee'  => $shipping,
            'net_shipping'  => $mock['net_shipping'],
            'shipping_type' => $selected,
            'shipping_options' => $options,
            'install_requested' => $installRequested,
            'install_fee'   => $mock['install_fee'],
            'install_label' => (string)get_setting('ship_label_install', 'Lắp đặt tại nhà'),
            'subtotal'      => $mock['subtotal'],
            'vat_rate'      => $mock['vat_rate'],
            'vat_amount'    => $mock['vat_amount'],
            'vat_san_pham'  => $mock['vat_san_pham'],
            'vat_shipping'  => $mock['vat_shipping'],
            'vat_install'   => $mock['vat_install'],
            'vat_breakdown' => $mock['vat_breakdown'] ?? [],
            'discount'      => $mock['discount_amount'],
            'freeship'      => $mock['freeship_discount'],
            'tier_discount_percent' => $mock['tier_discount_percent'],
            'tier_discount_amount'  => $mock['tier_discount_amount'],
            'total'         => $points['total_amount'],
            'gross_goods'   => $mock['gross_goods'],
            'type_labels'   => $label,
            'points_balance'=> $points['balance'],
            'points_can_use'=> $points['can_use'],
            'points_used'   => $points['used_points'],
            'points_discount'=> $points['points_discount'],
            'point_value'   => (int)get_setting('point_value', 1000),
        ]);
    }

    /** Kiểm tra cổng COD theo SĐT + hạng thành viên (Phần 3/4) - dùng khi bấm "Đặt hàng" */
    private function gateCheck(): never
    {
        $phone = trim($this->post('phone', ''));
        if (!preg_match('/^(0|\+84)[0-9]{9,10}$/', $phone)) {
            json_response(['ok' => false, 'message' => 'Số điện thoại không hợp lệ.']);
        }
        // Truyền đúng địa chỉ + tên người nhận như khi đặt đơn để gate (trust/tín hiệu rủi ro)
        // khớp với kết quả tại placeOrder() — tránh case gate-check báo sai rồi mới báo cần OTP khi submit.
        $name = trim((string)$this->post('name', ''));
        $address = trim((string)$this->post('address', ''));
        $user = current_user();
        $gate = Checkout::gate($phone, $address, $name, $user ? (int)$user['id'] : null);
        json_response([
            'ok'   => true,
            'gate' => [
                'tier'             => $gate['tier'],
                'tier_label'       => match ($gate['tier']) {
                    TrustEngine::GREEN  => 'Xanh',
                    TrustEngine::RED    => 'Đỏ',
                    default             => 'Vàng',
                },
                'otp_required'     => $gate['otp_required'],
                'otp_exempt'       => $gate['otp_exempt'],
                'otp_exempt_reason'=> $gate['otp_exempt_reason'],
                'payment_allowed'  => $gate['payment_allowed'],
            ],
        ]);
    }

    /** Gửi OTP cho số điện thoại (tier vàng bắt buộc khi COD). SMS thật nếu đã cấu hình (Phần 3). */
    private function sendOtp(): never
    {
        $phone = trim($this->post('phone', ''));
        if (!preg_match('/^(0|\+84)[0-9]{9,10}$/', $phone)) {
            json_response(['ok' => false, 'message' => 'Số điện thoại không hợp lệ.']);
        }

        $user = current_user();
        $userId = $user ? (int)$user['id'] : null;
        // Truyền đúng địa chỉ + tên người nhận (như placeOrder) để send-otp ra quyết định OTP
        // khớp với gate-check — không báo nhầm "không cần OTP" khi địa chỉ thật hợp lệ.
        $name = trim((string)$this->post('name', ''));
        $address = trim((string)$this->post('address', ''));
        $gate = Checkout::gate($phone, $address, $name, $userId);

        // Tier Đỏ: thông báo trung lập, yêu cầu trả trước (không nói lý do bùng hàng)
        if ($gate['tier'] === TrustEngine::RED) {
            json_response([
                'ok'      => false,
                'blocked' => true,
                'message' => 'Đơn hàng này cần thanh toán trước để đảm bảo xử lý nhanh nhất.',
                'gate'    => $gate,
            ]);
        }

        // Phần 4: VIP/Diamond miễn OTP (còn hiệu lực)
        if ($gate['otp_exempt']) {
            json_response([
                'ok'         => true,
                'otp_exempt' => true,
                'message'    => $gate['otp_exempt_reason'],
                'gate'       => $gate,
            ]);
        }

        // Khách an toàn (xanh): không cần OTP
        if (!$gate['otp_required']) {
            json_response(['ok' => true, 'not_required' => true, 'gate' => $gate]);
        }

        // Giới hạn gửi lại theo giờ (chống spam)
        $limit = max(1, (int)get_setting('otp_resend_limit_per_hour', 5));
        $key = 'otp:resend:' . $phone;
        if (rate_limit_blocked($key, $limit, 3600)) {
            json_response([
                'ok'      => false,
                'blocked' => true,
                'message' => 'Bạn đã yêu cầu gửi lại mã quá nhiều lần. Vui lòng thử lại sau 1 giờ.',
            ], 429);
        }
        rate_limit_hit($key, $limit, 3600);

        $code = Otp::send($phone, Otp::PURPOSE_ORDER);
        json_response([
            'ok'        => true,
            'message'   => 'Đã gửi mã xác thực đến ' . $phone,
            'dev_code'  => Sms::isConfigured() ? null : $code, // chỉ hiện mã demo khi CHƯA cấu hình SMS thật
        ]);
    }

    private function verifyOtp(): never
    {
        $phone = trim($this->post('phone', ''));
        $code = trim($this->post('otp', ''));
        if (!Otp::verify($phone, $code, Otp::PURPOSE_ORDER)) {
            json_response(['ok' => false, 'message' => 'Mã OTP không đúng hoặc đã hết hạn.']);
        }
        $_SESSION['otp_verified_for'] = $phone;
        json_response(['ok' => true, 'message' => 'Xác thực OTP thành công.']);
    }

    // ============ GOONG MAPS (gợi ý địa chỉ + geocode ngược) ============
    // Api key chỉ nằm phía server (models/GoongService.php), client không bao giờ thấy.
    // Khi tính năng tắt / thiếu key / Goong lỗi -> trả ok=false, ô địa chỉ vẫn là
    // input thường, không báo lỗi kỹ thuật cho khách (server đã ghi log).

    /** Endpoint 1: gợi ý địa chỉ (Place AutoComplete). location ưu tiên lấy từ Cài đặt. */
    private function goongAutocomplete(): never
    {
        if (!verify_csrf($this->post('_token'))) {
            json_response(['ok' => false, 'predictions' => []]);
        }
        $input = trim((string)$this->post('input', ''));
        if (\WoodCon\GoongService::enabled() && mb_strlen($input, 'UTF-8') >= 3) {
            $location = \WoodCon\GoongService::biasLocationString();
            $res = \WoodCon\GoongService::autocomplete(
                $input,
                $location,
                trim((string)$this->post('session_token', '')) ?: null
            );
            json_response($res);
        }
        json_response(['ok' => false, 'predictions' => []]);
    }

    /** Endpoint 2: chi tiết địa điểm (Place Detail) -> địa chỉ chuẩn hóa + lat/lng */
    private function goongPlaceDetail(): never
    {
        if (!verify_csrf($this->post('_token'))) {
            json_response(['ok' => false]);
        }
        $res = \WoodCon\GoongService::placeDetail(
            trim((string)$this->post('place_id', '')),
            trim((string)$this->post('session_token', '')) ?: null
        );
        json_response($res);
    }

    /** Endpoint 3: geocode ngược (Geocode) khi khách kéo thả ghim trên bản đồ */
    private function goongReverseGeocode(): never
    {
        if (!verify_csrf($this->post('_token'))) {
            json_response(['ok' => false]);
        }
        $lat = (float)$this->post('lat', 0);
        $lng = (float)$this->post('lng', 0);
        json_response(\WoodCon\GoongService::reverseGeocode($lat, $lng));
    }

    /** Xử lý POST đặt đơn (form) */
    public function place(): never
    {
        if (!$this->isPost()) {
            redirect(BASE_URL . '/thanh-toan');
        }
        if (!verify_csrf($this->post('_token'))) {
            set_flash('error', 'Phiên làm việc hết hạn, vui lòng thử lại.');
            redirect(BASE_URL . '/thanh-toan');
        }

        $errors = validate_form($_POST, [
            'customer_name'  => ['required' => true, 'max' => 120],
            'customer_phone' => ['required' => true, 'max' => 20],
            'address'        => ['required' => true, 'max' => 255],
        ]);
        if ($errors) {
            set_flash('error', reset($errors));
            redirect(BASE_URL . '/thanh-toan');
        }

        $paymentMethod = $this->post('payment_method', 'cod');
        if (!in_array($paymentMethod, ['cod', 'bank', 'qr', 'wallet'], true)) {
            $paymentMethod = 'cod';
        }

        $phone = trim($this->post('customer_phone'));
        // OTP chỉ bắt buộc với COD + tier vàng; kiểm tra luôn ở đây khi COD
        $otpVerified = isset($_SESSION['otp_verified_for']) && $_SESSION['otp_verified_for'] === $phone;

        $result = Checkout::placeOrder([
            'user_id'          => current_user()['id'] ?? 0,
            'items'            => $this->cartItems(true),
            'customer_phone'   => $phone,
            'customer_name'    => trim((string)$this->post('customer_name', '')),
            'customer_email'   => trim((string)$this->post('customer_email', '')) ?: null,
            'address'          => trim((string)$this->post('address', '')),
            'ward'             => trim((string)$this->post('ward', '')) ?: null,
            'district'         => trim((string)$this->post('district', '')) ?: null,
            'city'             => trim((string)$this->post('city', '')) ?: null,
            'delivery_lat'     => $this->post('delivery_lat'),
            'delivery_lng'     => $this->post('delivery_lng'),
            'note'             => trim((string)$this->post('note', '')) ?: null,
            'payment_method'   => $paymentMethod,
            'discount_code'    => trim((string)$this->post('discount_code', '')),
            'freeship_code'    => trim((string)$this->post('freeship_code', '')),
            'use_points'       => (int)$this->post('use_points', 0),
            'shipping_type'    => $this->post('shipping_type') === Shipping::TYPE_SHOP ? Shipping::TYPE_SHOP : Shipping::TYPE_CARRIER,
            'install_requested'=> (bool)$this->post('install_requested', false),
            'otp_verified'     => $paymentMethod === 'cod' ? $otpVerified : true,
        ]);

        if (!$result['ok']) {
            if (!empty($result['otp_required'])) {
                set_flash('warning', $result['message'] . ' Bạn sẽ cần xác thực OTP.');
            } else {
                set_flash('error', $result['message']);
            }
            redirect(BASE_URL . '/thanh-toan');
        }

        unset($_SESSION['otp_verified_for']);
        $_SESSION['last_order_code'] = $result['order_code'];
        set_flash('success', 'Đặt hàng thành công! Mã đơn: ' . $result['order_code'] . ' được gửi để chúng tôi xác nhận.');
        redirect(BASE_URL . '/hoan-tat');
    }

    /** Trang hoàn tất đặt hàng (truy cập không cần đăng nhập) */
    public function success(): void
    {
        $code = $_SESSION['last_order_code'] ?? '';
        $order = $code !== '' ? Order::byCode($code) : null;
        $this->render('order_success', [
            'pageTitle' => 'Đặt hàng thành công - WoodCon',
            'order'     => $order,
            'items'     => $order ? Order::itemsOf((int)$order['id']) : [],
        ]);
    }

    /** Dựng danh sách item từ giỏ hàng (kèm dữ liệu ship) */
    private function cartItems(bool $forOrder = false): array
    {
        $rows = Cart::items();
        $out = [];
        foreach ($rows as $row) {
            $p = Product::find((int)$row['product_id']);
            if (!$p || !$p['status']) {
                continue;
            }
            $qty = min((int)$row['quantity'], max(1, (int)$p['quantity']));
            if ($qty <= 0) {
                continue;
            }
            $out[] = [
                'product_id' => (int)$p['id'],
                'quantity'   => $qty,
                'unit_price' => Product::effectivePrice($p),
                'weight_kg'  => (float)$p['weight_kg'],
                'dim_l'      => (float)$p['dim_l'],
                'dim_w'      => (float)$p['dim_w'],
                'dim_h'      => (float)$p['dim_h'],
                'product'    => $p,
                'vat_rate'   => \WoodCon\TaxRate::rateForProduct($p),
            ];
        }
        return $out;
    }
}