<?php
/**
 * WoodCon - Dịch vụ Thanh toán (đặt hàng)
 * Điều phối toàn bộ luồng đặt COD theo logic_chong_bung_hang_COD.txt:
 *   - Tính giá tách VAT: Tạm tính -> VAT -> Phí ship -> Tổng cộng
 *   - Cổng chặn theo trust tier: Đỏ = chỉ trả trước; Vàng = OTP; Rủi ro = manual_verifying
 *   - Trừ kho (reserve), ghi discounts, tăng lượt voucher, xoá giỏ hàng
 */

declare(strict_types=1);

namespace WoodCon;

use PDO;

class Checkout
{
    public const VATEXCLUDED_TXT = 'Tất cả giá đã gồm VAT theo NĐ 15/2022/NĐ-CP.';

    /**
     * Tính toán tổng giá đơn hàng dựa trên items và voucher.
     * Giá sản phẩm đã GỒM VAT; tách ngược phần VAT theo từng mức thuế của từng sản phẩm
     * (hỗ trợ đa mức thuế 0%/5%/8%/10% theo NĐ 15/2022).
     * @param array $items [['product_id','quantity','unit_price','vat_rate'?, 'product'?], ...]
     * @return array Tổng các thành phần chi tiết (kèm vat_breakdown theo từng mức)
     */
    public static function buildTotals(array $items): array
    {
        $defaultRate = TaxRate::defaultRateValue();

        // Gom theo từng mức thuế: gross từng mức
        $groups = []; // rate => gross
        foreach ($items as $item) {
            // Chỉ resolve theo sản phẩm khi item không mang sẵn vat_rate (0% là giá trị hợp lệ)
            $rate = $item['vat_rate'] ?? null;
            if ($rate === null) {
                $p = $item['product'] ?? Product::find((int)($item['product_id'] ?? 0));
                $rate = TaxRate::rateForProduct($p ?: null);
            }
            $rate = max(0, (float)$rate);
            $gross = (float)$item['unit_price'] * max(1, (int)$item['quantity']);
            $groups[$rate] = ($groups[$rate] ?? 0.0) + $gross;
        }

        $grossGoods = 0.0;
        $netTotal = 0.0;
        $vatTotal = 0.0;
        $breakdown = [];
        foreach ($groups as $rate => $gross) {
            $gross = round($gross);
            $floatRate = max(0, (float)$rate);
            $net = $floatRate > 0 ? $gross / (1 + $floatRate / 100) : $gross;
            $vat = $gross - $net;
            $netTotal += $net;
            $vatTotal += $vat;
            $grossGoods += $gross;
            $breakdown[] = [
                'rate'   => $floatRate,
                'gross'  => (int)round($gross),
                'net'    => (int)round($net),
                'vat'    => (int)round($vat),
            ];
        }

        return [
            'gross_goods'   => (int)round($grossGoods),
            'subtotal'      => (int)round($netTotal),
            'vat_rate'      => $defaultRate, // blended/primary dùng cho lưu orders.vat_rate
            'vat_amount'    => (int)round($vatTotal),
            'vat_breakdown' => $breakdown,
        ];
    }

    /**
     * Áp dụng voucher và tính đủ các thành phần đơn hàng.
     * @param string|null $discountCode mã giảm giá (loại discount)
     * @param string|null $freeshipCode mã miễn phí ship (loại freeship)
     * @param float $shippingFee phí ship gốc
     * @param float $installFee phí lắp đặt (Chỉ mục 2, khi khách chọn lắp đặt)
     * @return array{valid:bool,error?:string,totals?:array}
     */
    public static function applyVouchers(array $items, ?string $discountCode, ?string $freeshipCode, float $shippingFee, float $installFee = 0, ?int $userId = null, float $tierDiscountPercent = 0.0): array
    {
        $goods = static::buildTotals($items);
        $grossGoods = $goods['gross_goods'];

        // Giảm giá theo hạng thành viên (BƯỚC 2): snapshot % + số tiền trên tiền hàng (đã gồm VAT).
        // Áp TRƯỚC voucher (mỗi loại một cơ sở riêng, không chồng lên nhau); voucher vẫn dùng
        // grossGoods gốc để kiểm tra điều kiện tối thiểu (Voucher::validate không đổi).
        $tierPercent = max(0, (float)$tierDiscountPercent);
        $tierAmount  = $tierPercent > 0 ? (int)round($grossGoods * $tierPercent / 100) : 0;

        // GHẾ đầu: đủ thông tin sản phẩm để kiểm tra scope voucher
        $scopeItems = array_map(fn($i) => [
            'product_id'  => (int)$i['product_id'],
            'category_id' => Product::categoryIdOf((int)$i['product_id']),
        ], $items);

        $discountAnswer = ['valid' => true, 'error' => '', 'discount' => 0, 'id' => 0];
        if (!empty($discountCode)) {
            $discountAnswer = Voucher::validate($discountCode, 'discount', $grossGoods, 0, $userId, $scopeItems);
            if (!$discountAnswer['valid']) {
                return ['valid' => false, 'error' => $discountAnswer['error']];
            }
        }

        $freeshipAnswer = ['valid' => true, 'error' => '', 'discount' => 0, 'id' => 0];
        if (!empty($freeshipCode)) {
            $freeshipAnswer = Voucher::validate($freeshipCode, 'freeship', $grossGoods, $shippingFee, $userId, $scopeItems);
            if (!$freeshipAnswer['valid']) {
                return ['valid' => false, 'error' => $freeshipAnswer['error']];
            }
        }

        $discountAmount = (int)$freeshipAnswer['discount']; // đã thử: nếu lộn loại
        $discountAmount = (int)$discountAnswer['discount'];
        $netShipping = max(0, (int)round($shippingFee - $freeshipAnswer['discount']));

        // Tách VAT sau khi đã trừ chiết khấu hàng hoá + giảm giá hạng (đa mức thuế)
        $vatRate = $goods['vat_rate'];
        $finalGoods = max(0, $grossGoods - $tierAmount - $discountAmount);
        $breakdown = $goods['vat_breakdown'] ?? [];
        $netTotal = 0.0;
        $vatTotal = 0.0;
        foreach ($breakdown as $grp) {
            $grpGross = (float)$grp['gross'];
            $alloc = $grossGoods > 0 ? ($grpGross / $grossGoods) * $finalGoods : 0;
            $floatRate = max(0, (float)$grp['rate']);
            $grpNet = $floatRate > 0 ? $alloc / (1 + $floatRate / 100) : $alloc;
            $grpVat = $alloc - $grpNet;
            $netTotal += $grpNet;
            $vatTotal += $grpVat;
        }
        $net = $netTotal;
        $vat = $vatTotal;

        // Phí vận chuyển (sau freeship) + phí lắp đặt là DOANH THU DỊCH VỤ đã GỒM VAT
        // (không khoản phí nào được miễn thuế) -> tách ngược riêng TỪNG phí một dòng,
        // KHÔNG gộp với VAT sản phẩm và KHÔNG cộng thêm vào Tổng cộng.
        // Mức thuế cho phí dịch vụ được CẤU HÌNH ĐỘC LẬP (settings.tax_shipping_rate /
        // tax_install_rate), mặc định 5% — không còn lệ thuộc ngầm vào thuế suất sản phẩm.
        $installFeeInt = (int)round($installFee);
        $taxShipRate = TaxRate::shippingRate();
        $taxInstallRate = TaxRate::installRate();
        $vatShipping = $taxShipRate > 0 && $netShipping > 0
            ? (int)round($netShipping - ($netShipping / (1 + $taxShipRate / 100)))
            : 0;
        $vatInstall = $taxInstallRate > 0 && $installFeeInt > 0
            ? (int)round($installFeeInt - ($installFeeInt / (1 + $taxInstallRate / 100)))
            : 0;

        $total = (int)round($net + $vat + $netShipping + $installFeeInt);

        return [
            'valid' => true,
            'error' => '',
            'totals' => [
                'gross_goods'      => $grossGoods,
                'subtotal'         => (int)round($net),
                'vat_san_pham'     => (int)round($vat),          // VAT sản phẩm (tách ngược từ tiền hàng)
                'vat_rate'         => $vatRate,
                'vat_shipping'     => $vatShipping,     // VAT tách ngược trên Phí vận chuyển (đã gồm trong giá)
                'vat_install'      => $vatInstall,      // VAT tách ngược trên Phí lắp đặt (đã gồm trong giá)
                'vat_amount'       => (int)round($vat), // VAT sản phẩm (deprecated - lưu orders.vat_amount)
                'vat_breakdown'    => $breakdown,
                'shipping_fee'     => (int)round($shippingFee),
                'install_fee'      => (int)round($installFee),
                'discount_amount'  => $discountAmount,
                'freeship_discount'=> (int)round($freeshipAnswer['discount']),
                'tier_discount_percent' => $tierPercent,
                'tier_discount_amount'  => $tierAmount,
                'net_shipping'     => $netShipping,
                'total_amount'     => $total,
                'voucher_code'     => $discountCode ? strtoupper($discountCode) : null,
                'freeship_code'    => $freeshipCode ? strtoupper($freeshipCode) : null,
                'voucher_id'       => $discountAnswer['id'],
                'freeship_id'      => $freeshipAnswer['id'],
            ],
        ];
    }

    /**
     * Xác định cổng đặt hàng: tier + tín hiệu rủi ro.
     * Phần 4: hạng thành viên VIP/Diamond được MIỄN OTP (không áp dụng với tier Đỏ).
     * @return array{payment_allowed:string[],order_status:string,otp_required:bool,manual_verify:bool,flags:array,tier:string,otp_exempt:bool,otp_exempt_reason:?string}
     */
    public static function gate(string $phone, string $address, string $name, ?int $userId = null): array
    {
        $tierInfo = TrustEngine::tierForPhone($phone);
        $tier = $tierInfo['tier'];
        $signals = TrustEngine::evaluateSignals($phone, $address, $name);
        $risk = $signals['risk'];

        // Phần 4 - Miễn OTP khi là thành viên VIP/Diamond (còn hiệu lực); TUYỆT ĐỐI không áp cho tier Đỏ
        $otpExempt = false;
        $otpExemptReason = null;
        if ($tier !== TrustEngine::RED && $userId) {
            $m = User::membershipTier((int)$userId);
            if ($m && in_array(strtoupper(trim((string)$m['name'])), ['VIP', 'DIAMOND'], true)) {
                $exp = (string)($m['membership_expired'] ?? '');
                if ($exp === '' || $exp >= date('Y-m-d')) {
                    $otpExempt = true;
                    $otpExemptReason = 'Hạng thành viên ' . trim((string)$m['name']) . ' - đơn hàng được xử lý nhanh, không cần xác thực OTP.';
                }
            }
        }

        $highValueNotified = false; // đơn giá trị cao sẽ được xử lý ở placeOrder
        $orderStatus = 'pending';

        // Tier Đỏ: cấm COD, chỉ trả trước
        if ($tier === TrustEngine::RED) {
            return [
                'payment_allowed' => ['bank', 'qr', 'wallet'],
                'order_status'    => 'pending',
                'otp_required'    => false,
                'manual_verify'   => false,
                'flags'           => $signals['flags'],
                'tier'            => $tier,
                'otp_exempt'      => false,
                'otp_exempt_reason' => null,
            ];
        }

        $otpRequired = false;
        $manualVerify = false;

        if ($risk) {
            // Nhiều tín hiệu nghi ngờ -> giữ đơn ở manual_verifying
            $orderStatus = 'cancelled_by_risk_placeholder'; // không dùng, sẽ set 'manual_verifying'
            $orderStatus = 'manual_verifying';
            $manualVerify = true;
        } elseif ($tier === TrustEngine::YELLOW) {
            // Khách mới (vàng): bắt buộc OTP xác thực SĐT, TRỪ hạng VIP/Diamond được miễn (Phần 4)
            $otpRequired = !$otpExempt;
            $orderStatus = 'pending';
        }

        return [
            'payment_allowed' => ['cod', 'bank', 'qr', 'wallet'],
            'order_status'    => $orderStatus,
            'otp_required'    => $otpRequired,
            'manual_verify'   => $manualVerify,
            'flags'           => $signals['flags'],
            'tier'            => $tier,
            'otp_exempt'      => $otpExempt,
            'otp_exempt_reason' => $otpExemptReason,
        ];
    }

    /**
     * Tạo đơn hàng hoàn chỉnh.
     * @param array $payload gồm: user_id|null, items, customer_phone, customer_name, address, ward, district, city,
     *                       shipping_zone, payment_method, discount_code, freeship_code, otp_verified, ip
     * @return array{ok:bool,message:string,order_id?:int,order_code?:string,gate?:array}
     */
    public static function placeOrder(array $payload): array
    {
        $items = $payload['items'] ?? [];
        if (empty($items)) {
            return ['ok' => false, 'message' => 'Giỏ hàng trống.'];
        }

        $phone = trim($payload['customer_phone'] ?? '');
        $name = trim($payload['customer_name'] ?? '');
        $address = trim($payload['address'] ?? '');
        $city = trim($payload['city'] ?? '');
        // Tọa độ địa chỉ giao (Goong Maps): validate phạm vi, nếu lỗi thì bỏ qua lưu null
        $deliveryLat = $payload['delivery_lat'] ?? null;
        $deliveryLng = $payload['delivery_lng'] ?? null;
        if (is_numeric($deliveryLat) && is_numeric($deliveryLng)) {
            $dl = (float)$deliveryLat;
            $dn = (float)$deliveryLng;
            if ($dl < -90 || $dl > 90 || $dn < -180 || $dn > 180) {
                $deliveryLat = null;
                $deliveryLng = null;
            }
        } else {
            $deliveryLat = null;
            $deliveryLng = null;
        }
        $zone = $payload['shipping_zone'] ?? Shipping::detectZone($city);
        if (!isset((Shipping::zones())[$zone])) {
            $zone = Shipping::detectZone($city);
        }

        // Tính phí ship từ thông tin đầy đủ của sản phẩm
        $fullItems = [];
        foreach ($items as $item) {
            $p = Product::find((int)$item['product_id']);
            if (!$p || $p['status'] != 1) {
                return ['ok' => false, 'message' => 'Sản phẩm không còn bán.'];
            }
            if ((int)$p['quantity'] < (int)$item['quantity']) {
                return ['ok' => false, 'message' => 'Sản phẩm "' . $p['name'] . '" không đủ số lượng trong kho.'];
            }
            $fullItems[] = [
                'product_id'   => (int)$p['id'],
                'quantity'     => (int)$item['quantity'],
                'unit_price'   => Product::effectivePrice($p),
                'name'         => $p['name'],
                'cover_image'  => $p['cover_image'],
                'weight_kg'    => (float)$p['weight_kg'],
                'dim_l'        => (float)$p['dim_l'],
                'dim_w'        => (float)$p['dim_w'],
                'dim_h'        => (float)$p['dim_h'],
                'warranty_months' => (int)$p['warranty_months'],
                'product'      => $p,
                'vat_rate'     => TaxRate::rateForProduct($p),
            ];
        }

        $paymentMethod = $payload['payment_method'] ?? 'cod';
        $userId = (int)($payload['user_id'] ?? 0);
        $gate = static::gate($phone, $address, $name, $userId ?: null);

        // Kiểm tra phương thức thanh toán theo cổng
        if (!in_array($paymentMethod, $gate['payment_allowed'], true)) {
            return [
                'ok' => false,
                'number' => 1,
                'message' => 'Đơn hàng này cần thanh toán trước để đảm bảo xử lý nhanh nhất.',
                'gate' => $gate,
            ];
        }
        if ($gate['otp_required'] && empty($payload['otp_verified'])) {
            return ['ok' => false, 'message' => 'Vui lòng xác thực mã OTP trước khi đặt hàng.', 'gate' => $gate, 'otp_required' => true];
        }

        $grossGoodsValue = array_sum(array_map(fn($i) => $i['unit_price'] * $i['quantity'], $fullItems));

        // ---- Phần 2: chọn loại vận chuyển (Loại 1 shop / Loại 2 carrier) + phí lắp đặt ----
        $shipOpt = Shipping::cartShippingOptions($fullItems);
        $shippingType = (string)($payload['shipping_type'] ?? Shipping::TYPE_CARRIER);
        if ($shippingType === Shipping::TYPE_SHOP && !$shipOpt['type1']) {
            $shippingType = Shipping::TYPE_CARRIER;
        }
        if ($shippingType === Shipping::TYPE_CARRIER && !$shipOpt['type2'] && $shipOpt['type1']) {
            $shippingType = Shipping::TYPE_SHOP;
        }

        // Phí Loại 2 tính qua Viettel Post (theo địa chỉ + khối lượng); lỗi thì không thể đặt Loại 2
        $district = trim((string)($payload['district'] ?? ''));
        $vtp = null;
        if ($shippingType === Shipping::TYPE_CARRIER) {
            // MONEY_COLLECTION chỉ gửi khi bật COD + phương thức COD (ước lượng theo tổng hàng hóa)
            $codAmount = $paymentMethod === 'cod' ? $grossGoodsValue : 0.0;
            $vtp = \WoodCon\ViettelPost::calculateForCity($fullItems, $city, $district, $codAmount);
            if (!$vtp['available']) {
                $shippingType = Shipping::TYPE_SHOP;
                $vtp = null;
            }
        }

        $shippingFee = $shippingType === Shipping::TYPE_SHOP
            ? Shipping::type1Fee($city)
            : ($vtp ? $vtp['fee'] : Shipping::carrierFee($fullItems, $zone));
        if (Shipping::isFreeShipping($grossGoodsValue, $shippingType, $city, $zone) && empty($payload['freeship_code'])) {
            $shippingFee = 0;
        }

        $installRequested = (bool)($payload['install_requested'] ?? false);
        $installFee = 0.0;
        if ($shippingType === Shipping::TYPE_SHOP && $installRequested && $shipOpt['install_available']) {
            $installFee = $shipOpt['install_fee'];
        }

        // Giảm giá theo hạng thành viên: snapshot % tại thời điểm đặt (không tính lại khi xem đơn cũ)
        $tierPercent = 0.0;
        if ($userId > 0) {
            $tierRow = User::membershipTier($userId);
            $tierPercent = $tierRow ? max(0, (float)($tierRow['discount_percent'] ?? 0)) : 0.0;
        }
        $voucher = static::applyVouchers($fullItems, $payload['discount_code'] ?? '', $payload['freeship_code'] ?? '', $shippingFee, $installFee, $userId, $tierPercent);
        if (!$voucher['valid']) {
            return ['ok' => false, 'message' => $voucher['error']];
        }
        $t = $voucher['totals'];

        // Điểm thưởng: giới hạn theo số dư + tổng đơn (GIAI ĐOẠN 3.4)
        $userId = (int)($payload['user_id'] ?? 0);
        $userIdOrNull = $userId > 0 ? $userId : null;
        $points = static::computePoints($t, $userIdOrNull, (int)($payload['use_points'] ?? 0));
        if ($points['used_points'] > 0) {
            $t['total_amount'] = $points['total_amount'];
        }
        $t['points_used'] = $points['used_points'];

        // Đơn giá trị cao với khách vàng: chuyển sang xác minh thủ công (dù đã OTP)
        $highValue = (float)get_setting('cod_manual_verify_amount', 2000000);
        $orderStatus = $gate['order_status'];
        $notes = trim((string)($payload['note'] ?? ''));
        if ($gate['tier'] === TrustEngine::YELLOW && $t['total_amount'] >= $highValue) {
            $orderStatus = 'manual_verifying';
            $notes .= ($notes !== '' ? ' | ' : '') . 'Đơn COD giá trị cao cần xác minh thủ công.';
        }
        if (static::percentAlert($t['discount_amount'], $t['gross_goods'])) {
            $notes .= ($notes !== '' ? ' | ' : '') . 'Giảm giá > 50% giá niêm yết, cần xác nhận theo NĐ 81/2018/NĐ-CP.';
        }

        $orderCode = Order::generateCode();

        $db = Database::connect();
        $db->beginTransaction();
        try {
            $stmtOrd = $db->prepare(
                'INSERT INTO orders (
                    order_code, user_id, customer_name, customer_phone, customer_email,
                    address, ward, district, city, delivery_lat, delivery_lng, note,
subtotal, vat_rate, vat_amount, vat_shipping_amount, vat_install_amount,
                    shipping_fee, discount_amount, freeship_discount, tier_discount_percent, tier_discount_amount,
                    total_amount, payment_method,
                    payment_status, order_status, trust_level_at_order, risk_flag, otp_verified,
                    manual_verify_note, shipping_type, install_requested, install_fee, otp_bypass_reason,
                    voucher_code, freeship_code, points_used
) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )'
            );
            $stmtOrd->execute([
                    $orderCode, $userIdOrNull, $name, $phone, $payload['customer_email'] ?? null,
                    $address, $payload['ward'] ?? null, $payload['district'] ?? null, $city,
                    $deliveryLat, $deliveryLng, $notes,
                    $t['subtotal'], $t['vat_rate'], $t['vat_amount'], $t['vat_shipping'], $t['vat_install'],
                    $t['shipping_fee'], $t['discount_amount'], $t['freeship_discount'],
                    $t['tier_discount_percent'], $t['tier_discount_amount'],
                    $t['total_amount'],
                    $paymentMethod, $paymentMethod === 'cod' ? 'unpaid' : 'paid',
                    $orderStatus, $gate['tier'], count($gate['flags']) >= 2 ? 1 : 0,
                    ($gate['otp_required'] && !empty($payload['otp_verified'])) ? 1 : 0,
                    implode(', ', $gate['flags']) ?: null,
                    $shippingType,
                    $installRequested ? 1 : 0, $t['install_fee'],
                    $gate['otp_exempt_reason'] ?? null,
                    $t['voucher_code'], $t['freeship_code'], $t['points_used'],
            ]);
            $orderId = (int)$db->lastInsertId();

            // Trừ điểm thưởng đã dùng + ghi log giao dịch
            if ($userId && $t['points_used'] > 0) {
                $stmtPts = $db->prepare('UPDATE users SET points = points - ? WHERE id = ? AND points >= ?');
                $ok = $stmtPts->execute([$t['points_used'], $userId, $t['points_used']]);
                if (!$ok || $stmtPts->rowCount() === 0) {
                    $db->rollBack();
                    return ['ok' => false, 'message' => 'Số dư điểm không đủ, vui lòng thử lại.'];
                }
                $insPt = $db->prepare(
                    'INSERT INTO points_transactions (user_id, order_id, points_change, type, note)
                     VALUES (?, ?, ?, "spend", ?)'
                );
                $insPt->execute([
                    $userId, $orderId, -$t['points_used'],
                    'Dùng điểm cho đơn ' . $orderCode,
                ]);
            }

            // Ghi chi tiết (snapshot tên/ảnh) + trừ kho
            $stmtItems = $db->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, cover_image, quantity, price, vat_rate, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmtStock = $db->prepare('UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?');
            foreach ($fullItems as $it) {
                $lineTotal = $it['unit_price'] * $it['quantity'];
                $stmtItems->execute([
                    $orderId, $it['product_id'], $it['name'], $it['cover_image'],
                    $it['quantity'], $it['unit_price'], $it['vat_rate'], $lineTotal,
                ]);
                $stmtStock->execute([$it['quantity'], $it['product_id'], $it['quantity']]);
            }

            // Tăng lượt dùng voucher
            if (!empty($t['voucher_id'])) {
                Voucher::incrementUsed($t['voucher_id']);
            }
            if (!empty($t['freeship_id'])) {
                Voucher::incrementUsed($t['freeship_id']);
            }

            // Ghi log chống bom hàng nếu là đơn COD bị nghi ngờ
            if (($gate['tier'] !== TrustEngine::GREEN || $gate['otp_required']) && $paymentMethod === 'cod') {
                TrustLog::write($phone, $userId ?: null, $orderId, 'order_placed', null, $gate['tier'], implode(',', $gate['flags']) . ' ' . $notes);
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            write_log('error', 'Đặt hàng thất bại: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Lỗi hệ thống khi đặt hàng, vui lòng thử lại.'];
        }

        // Xoá giỏ hàng sau khi đặt đơn
        Cart::clear();

        write_log('order', 'Đơn mới ' . $orderCode . ' tổng ' . format_money($t['total_amount']) . ' (' . $gate['tier'] . ')');

        return [
            'ok' => true,
            'message' => 'Đặt hàng thành công.',
            'order_id' => $orderId,
            'order_code' => $orderCode,
            'gate' => $gate,
        ];
    }

    /** Cảnh báo khuyến mãi > 50% theo NĐ 81/2018 */
    protected static function percentAlert(float $discount, float $grossGoods): bool
    {
        if ($grossGoods <= 0) {
            return false;
        }
        return ($discount / $grossGoods) > 0.5;
    }

    /**
     * Tính điểm thưởng có thể dùng cho đơn (GIAI ĐOẠN 3.4).
     * Giá trị 1 điểm = setting point_value (VDN), làm tròn xuống.
     * Giới hạn: <= số dư điểm, <= floor(total_amount / point_value).
     * @param array $totals kết quả sau applyVouchers
     * @return array{used_points:int,points_discount:int,can_use:int,balance:int,total_amount:int}
     */
    public static function computePoints(array $totals, ?int $userId, int $usePoints = 0): array
    {
        $balance = 0;
        if ($userId) {
            $balance = (int)(User::find($userId)['points'] ?? 0);
        }
        $value = max(1, (int)get_setting('point_value', 1000));
        $maxByTotal = (int)floor((float)($totals['total_amount'] ?? 0) / $value);
        $canUse = max(0, min($balance, $maxByTotal));

        $used = min(max(0, $usePoints), $canUse);
        $discount = $used * $value;

        return [
            'can_use'        => $canUse,
            'balance'        => $balance,
            'used_points'    => $used,
            'points_discount'=> $discount,
            'total_amount'   => max(0, (int)($totals['total_amount'] ?? 0) - $discount),
        ];
    }
}