<?php
/**
 * WoodCon - Model Cấu hình Hóa đơn
 * Lưu thông tin công ty dùng để xuất hóa đơn in (POS 80mm / A5-A4).
 * Riêng biệt, không tự copy từ Cài đặt chung — admin chủ động khai báo.
 * Lưu trong bảng settings với group 'invoice'.
 */

declare(strict_types=1);

namespace WoodCon;

class InvoiceSetting
{
    /** Danh sách các trường cấu hình hóa đơn (key => nhãn) */
    public static function fields(): array
    {
        return [
            'invoice_company_name' => 'Tên công ty / cửa hàng',
            'invoice_tax_code'     => 'Mã số thuế (MST)',
            'invoice_address'      => 'Địa chỉ xuất hóa đơn',
            'invoice_phone'        => 'Số điện thoại',
            'invoice_email'        => 'Email',
            'invoice_number_prefix'=> 'Tiền tố số hóa đơn (VD: HD)',
            'invoice_footer'       => 'Chân trang hóa đơn',
            'invoice_legal_note'   => 'Ghi chú pháp lý (hóa đơn nội bộ)',
        ];
    }

    /** Đọc toàn bộ cấu hình hóa đơn (kèm giá trị), về dạng key => value. */
    public static function all(): array
    {
        $out = [];
        foreach (array_keys(static::fields()) as $key) {
            $out[$key] = get_setting($key, '');
        }
        return $out;
    }

    /** Lưu toàn bộ cấu hình hóa đơn (chỉ nhận các key hợp lệ). */
    public static function saveAll(array $data): int
    {
        $saved = 0;
        foreach (array_keys(static::fields()) as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            if (set_setting($key, (string)$data[$key], 'invoice')) {
                $saved++;
            }
        }
        return $saved;
    }

    /** Lưu số hóa đơn tăng dần cuối cùng đã phát hành (internal) */
    public static function lastNumber(): int
    {
        return max(0, (int)get_setting('invoice_last_number', 0));
    }

    /** Cấp số hóa đơn kế tiếp và đánh dấu đã dùng. */
    public static function nextNumber(): int
    {
        $n = static::lastNumber() + 1;
        set_setting('invoice_last_number', (string)$n, 'invoice');
        return $n;
    }

    /**
     * Bảo đảm có số hóa đơn ổn định cho một đơn (không tăng khi in lại).
     * Nếu đơn đã có số thì giữ nguyên; ngược lại cấp số mới và lưu vào đơn.
     */
    public static function ensureNumberForOrder(array $order): string
    {
        $cfg = static::all();
        $prefix = trim($cfg['invoice_number_prefix'] ?: 'HD');
        if (!empty($order['invoice_number'])) {
            return (string)$order['invoice_number'];
        }
        $seq = static::nextNumber();
        $num = $prefix . '-' . str_pad((string)$seq, 6, '0', STR_PAD_LEFT);
        Order::update((int)$order['id'], ['invoice_number' => $num]);
        return $num;
    }
}
