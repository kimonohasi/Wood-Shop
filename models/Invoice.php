<?php
/**
 * WoodCon - Model Hóa đơn (Legal Invoices / e-Invoice compliant NĐ 123/2020)
 *
 * Mô-đun chứng từ kế toán tách riêng với "Quản lý đơn hàng" (operational).
 *  - SALE_INVOICE   : phát hành tự động khi đơn HOÀN THÀNH (delivered).
 *  - REFUND_INVOICE : Hóa đơn điều chỉnh giảm / Credit Note khi trả hàng/hoàn tiền,
 *                     mang giá trị ÂM, trỏ về hóa đơn bán gốc (original_invoice_id).
 *
 * RÀNG BUỘC IMMUTABILITY:
 *  - Mọi bản ghi trong invoices KHÔNG ĐƯỢC XÓA (is_immutable = 1, SQL:
 *    FK ON DELETE RESTRICT, và không expose bất kỳ delete() nào ở đây).
 *  - Khi phát sinh hủy/trả → tạo REFUND_INVOICE, KHÔNG xóa hóa đơn gốc.
 */

declare(strict_types=1);

namespace WoodCon;

use PDO;

class Invoice extends Base
{
    protected static string $table = 'invoices';

    public const TYPE_SALE   = 'SALE_INVOICE';
    public const TYPE_REFUND = 'REFUND_INVOICE';

    public const TYPE_LABEL = [
        self::TYPE_SALE   => 'Hóa đơn bán',
        self::TYPE_REFUND => 'Điều chỉnh giảm / Hoàn tiền',
    ];

    /* ============================================================
     * CÔNG THỨC TÀI CHÍNH (chuẩn hóa, dễ unit-test)
     * Giá bán ĐÃ GỒM VAT (NĐ 15/2022) — chỉ tách ngược phần thuế khi xuất hóa đơn:
     *   Thành tiền item    = qty * unit_price        (đã gồm VAT)
     *   Subtotal           = Σ(Thành tiền item)       (đã gồm VAT)
     *   GrossTaxable       = Subtotal - Discount      (sau chiết khấu, vẫn đã gồm VAT)
     *   VAT                = GrossTaxable - GrossTaxable/(1 + vat_rate/100)   (tách ngược)
     *   Net/Taxable        = GrossTaxable - VAT       (giá trước thuế)
     *   Grand Total        = Net + VAT + ShippingFee + InstallFee
     *                      = GrossTaxable + ShippingFee + InstallFee
     *   ==> KHÔNG cộng thêm VAT lên tổng tiền khách phải trả.
     * ============================================================ */
    public static function calculate(
        array $items,
        float $discount,
        float $shippingFee,
        float $vatRate,
        float $installFee = 0.0,
        float $tierDiscount = 0.0,
        float $tierPercent = 0.0
    ): array {
        $defaultRate = max(0, (float)$vatRate);

        // Subtotal = Σ giá bán đã gồm thuế; gom theo từng mức thuế để tách ngược chính xác
        $subtotal = 0.0;
        $netSubtotal = 0.0;
        $groups = []; // rate => gross
        $lines = [];
        foreach ($items as $it) {
            $qty  = max(1, (int)($it['quantity'] ?? 1));
            $unit = max(0, (float)($it['unit_price'] ?? $it['price'] ?? 0));
            $rate = max(0, (float)($it['vat_rate'] ?? $defaultRate));
            $line = $qty * $unit;
            $subtotal += $line;
            $groups[$rate] = ($groups[$rate] ?? 0.0) + $line;
            $netSubtotal += $rate > 0 ? ($line / (1 + $rate / 100)) : $line;
            $lines[] = [
                'product_id'    => isset($it['product_id']) ? (int)$it['product_id'] : null,
                'product_name'  => (string)($it['product_name'] ?? ''),
                'variant_name'  => $it['variant_name'] ?? null,
                'variant_value' => $it['variant_value'] ?? null,
                'quantity'      => $qty,
                'unit_price'    => $unit,
                'vat_rate'      => $rate,
                'line_total'    => round($line, 2),
            ];
        }

        $subtotal     = round($subtotal, 2);
        // GrossTaxable = Subtotal − chiết khấu (voucher/freeship) − giảm giá hạng thành viên
        $grossTaxable = round(max(0, $subtotal - max(0, $discount) - max(0, $tierDiscount)), 2); // tiền hàng đã gồm thuế sau chiết khấu

        // Tách ngược VAT theo từng mức thuế trên phần tiền hàng sau chiết khấu
        $vat   = 0.0;
        $net   = 0.0;
        foreach ($groups as $rate => $gross) {
            $alloc    = $subtotal > 0 ? ($gross / $subtotal) * $grossTaxable : 0.0;
            $r        = max(0, (float)$rate);
            $allocNet = $r > 0 ? $alloc / (1 + $r / 100) : $alloc;
            $net += $allocNet;
            $vat += $alloc - $allocNet;
        }
        $taxable = round($net, 2);
        $vat     = round($vat, 2);
        $install = max(0, round((float)$installFee, 2));
        $grand   = round($taxable + $vat + max(0, $shippingFee) + $install, 2); // = GrossTaxable + ShippingFee + InstallFee

        // VAT tách ngược (chỉ thông tin) cho TỪNG loại phí dịch vụ — đã gồm trong phí,
        // KHÔNG cộng thêm vào tổng (đồng bộ công thức ở checkout/admin).
        // Mức thuế của phí dịch vụ đọc từ cấu hình ĐỘC LẬP (settings.tax_shipping_rate /
        // tax_install_rate, mặc định 5%), không phụ thuộc thuế suất sản phẩm.
        $taxShipRate   = max(0, TaxRate::shippingRate());
        $taxInstallRat = max(0, TaxRate::installRate());
        $vatShip    = $taxShipRate > 0 && max(0, (float)$shippingFee) > 0
            ? round(max(0, (float)$shippingFee) - (max(0, (float)$shippingFee) / (1 + $taxShipRate / 100)), 2)
            : 0.0;
        $vatInstall = $taxInstallRat > 0 && $install > 0
            ? round($install - ($install / (1 + $taxInstallRat / 100)), 2)
            : 0.0;

        return [
            'lines'    => $lines,
            'subtotal' => round($netSubtotal, 2), // tổng giá trước thuế (tách ngược) — "Tạm tính (chưa thuế)"
            'discount' => round(max(0, (float)$discount), 2),
            'tier_discount' => round(max(0, (float)$tierDiscount), 2),
            'tier_discount_percent' => round(max(0, (float)$tierPercent), 2),
            'taxable'  => $taxable,               // giá trước thuế sau chiết khấu
            'vat_rate' => $defaultRate,
            'vat'      => $vat,                   // thuế GTGT tách ngược (chỉ thông tin, không cộng thêm)
            'shipping' => max(0, round($shippingFee, 2)),
            'install'  => $install,
            'vat_shipping' => $vatShip,           // thông tin
            'vat_install'  => $vatInstall,        // thông tin
            'grand'    => $grand,                 // = (giá bán đã gồm thuế − chiết khấu) + phí vận chuyển + phí lắp đặt
        ];
    }

    /** Phát hành tiếp số hóa đơn (transaction-safe). */
    public static function nextNumber(): string
    {
        $db = static::db();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'SELECT setting_value FROM settings WHERE setting_key = ? FOR UPDATE'
            );
            $stmt->execute(['invoice_hd_seq']);
            $row = $stmt->fetch();
            $seq = $row ? (int)$row['setting_value'] + 1 : 1;

            $cfg  = InvoiceSetting::all();
            $pref = trim($cfg['invoice_number_prefix'] ?: 'HD');
            $num  = $pref . '-' . str_pad((string)$seq, 6, '0', STR_PAD_LEFT);
            $code = 'HD' . date('YmdHis') . strtoupper(bin2hex(random_bytes(2)));

            $db->prepare(
                'INSERT INTO settings (setting_key, setting_value, group_name)
                 VALUES (?, ?, "invoice")
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
            )->execute(['invoice_hd_seq', (string)$seq]);

            $db->commit();
            return $num;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            // Fallback dùng bộ đếm cũ của InvoiceSetting để không block luồng
            $cfg  = InvoiceSetting::all();
            $pref = trim($cfg['invoice_number_prefix'] ?: 'HD');
            $n    = InvoiceSetting::nextNumber();
            return $pref . '-' . str_pad((string)$n, 6, '0', STR_PAD_LEFT);
        }
    }

    /**
     * Tự động phát hành HÓA ĐƠN BÁN khi đơn HOÀN THÀNH (delivered).
     * Idempotent: nếu đơn đã có SALE_INVOICE thì trả về bản cũ.
     */
    public static function generateForOrder(array $order, array $items, ?int $adminId = null): ?array
    {
        // Đã phát hành hóa đơn bán cho đơn này → không phát hành lại
        $existing = static::first(
            "SELECT * FROM invoices WHERE order_id = ? AND type = ? LIMIT 1",
            [(int)$order['id'], self::TYPE_SALE]
        );
        if ($existing) {
            return $existing;
        }

        $vatRate = (float)($order['vat_rate'] ?? 8.0);
        $discount = max(0, (float)($order['discount_amount'] ?? 0))
                  + max(0, (float)($order['freeship_discount'] ?? 0));
        $tierDiscount = max(0, (float)($order['tier_discount_amount'] ?? 0));
        $tierPercent  = max(0, (float)($order['tier_discount_percent'] ?? 0));
        $shipping = max(0, (float)($order['shipping_fee'] ?? 0));
        $install  = max(0, (float)($order['install_fee'] ?? 0));

        $calc = static::calculate($items, $discount, $shipping, $vatRate, $install, $tierDiscount, $tierPercent);

        $number = static::nextNumber();
        $db = static::db();
        $db->beginTransaction();
        try {
            $invId = static::insert([
                'invoice_code'        => $number . '-' . (int)$order['id'],
                'invoice_number'      => $number,
                'type'                => self::TYPE_SALE,
                'order_id'            => (int)$order['id'],
                'original_invoice_id' => null,
                'customer_name'       => (string)($order['customer_name'] ?? ''),
                'customer_phone'      => (string)($order['customer_phone'] ?? ''),
                'customer_email'      => (string)($order['customer_email'] ?? ''),
                'address'             => (string)($order['address'] ?? ''),
                'invoice_date'        => $order['delivered_at'] ?: date('Y-m-d H:i:s'),
                'subtotal'            => $calc['subtotal'],
                'discount'            => $calc['discount'],
                'tier_discount_percent' => $calc['tier_discount_percent'],
                'tier_discount_amount'  => $calc['tier_discount'],
                'taxable'             => $calc['taxable'],
                'vat_rate'            => $calc['vat_rate'],
                'vat'                 => $calc['vat'],
                'shipping_fee'        => $calc['shipping'],
                'install_fee'         => $calc['install'],
                'vat_shipping_amount' => $calc['vat_shipping'],
                'vat_install_amount'  => $calc['vat_install'],
                'grand_total'         => $calc['grand'],
                'currency'            => 'VND',
                'note'                => 'Tự động phát hành khi đơn hoàn thành.',
                'is_immutable'        => 1,
                'created_by'          => $adminId,
            ]);

            foreach ($calc['lines'] as $line) {
                $ins = [];
                $ins['invoice_id']   = $invId;
                $ins['product_id']   = $line['product_id'];
                $ins['product_name'] = $line['product_name'];
                $ins['variant_name'] = $line['variant_name'];
                $ins['variant_value'] = $line['variant_value'];
                $ins['quantity']     = $line['quantity'];
                $ins['unit_price']   = $line['unit_price'];
                $ins['vat_rate']     = $line['vat_rate'];
                $ins['line_total']   = $line['line_total'];
                static::db()->prepare(
                    'INSERT INTO invoice_items
                        (invoice_id, product_id, product_name, variant_name, variant_value,
                         quantity, unit_price, vat_rate, line_total)
                     VALUES (?,?,?,?,?,?,?,?,?)'
                )->execute(array_values($ins));
            }
            $db->commit();
            write_log('invoice', 'Phát hành HĐ bán ' . $number . ' cho đơn ' . $order['order_code'], $adminId);
            return static::find($invId);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            write_log('invoice', 'Lỗi phát hành HĐ cho đơn ' . $order['order_code'] . ': ' . $e->getMessage(), $adminId);
            return null;
        }
    }

    /**
     * Phát hành HÓA ĐƠN ĐIỀU CHỈNH GIẢM / HOÀN TIỀN (Credit Note).
     * Giá trị ÂM; trỏ về hóa đơn bán gốc. KHÔNG xóa hóa đơn gốc.
     */
    public static function createCreditNote(
        array $originalInvoice,
        array $order,
        array $items,
        string $reason = '',
        ?int $adminId = null
    ): ?array {
        if ($originalInvoice['type'] !== self::TYPE_SALE) {
            return null; // chỉ dùng hóa đơn bán gốc làm cơ sở
        }
        // Chống phát hành trùng credit note cho cùng đơn (một lần)
        $existing = static::first(
            "SELECT * FROM invoices WHERE original_invoice_id = ? AND type = ? LIMIT 1",
            [(int)$originalInvoice['id'], self::TYPE_REFUND]
        );
        if ($existing) {
            return $existing;
        }

        $vatRate = (float)($order['vat_rate'] ?? $originalInvoice['vat_rate'] ?? 8.0);
        $discount = (float)$originalInvoice['discount'];
        $tierDiscount = max(0, (float)($originalInvoice['tier_discount_amount'] ?? 0));
        $tierPercent  = max(0, (float)($originalInvoice['tier_discount_percent'] ?? 0));
        $shipping = (float)$originalInvoice['shipping_fee'];
        $install  = (float)($originalInvoice['install_fee'] ?? 0);

        // Tính đủ các thành phần (dương) bằng chính công thức chuẩn, rồi đảo dấu sang ÂM
        $calc = static::calculate($items, $discount, $shipping, $vatRate, $install, $tierDiscount, $tierPercent);
        $calc['subtotal'] = -$calc['subtotal'];
        $calc['discount'] = -$calc['discount'];
        $calc['tier_discount'] = -$calc['tier_discount'];
        $calc['taxable']  = -$calc['taxable'];
        $calc['vat']      = -$calc['vat'];
        $calc['shipping'] = -$calc['shipping'];
        $calc['install']  = -$calc['install'];
        $calc['vat_shipping'] = -$calc['vat_shipping'];
        $calc['vat_install']  = -$calc['vat_install'];
        $calc['grand']    = -$calc['grand'];

        $number = static::nextNumber();
        $db = static::db();
        $db->beginTransaction();
        try {
            $invId = static::insert([
                'invoice_code'        => $number . '-CN-' . (int)$order['id'],
                'invoice_number'      => $number,
                'type'                => self::TYPE_REFUND,
                'order_id'            => (int)$order['id'],
                'original_invoice_id' => (int)$originalInvoice['id'],
                'customer_name'       => (string)($order['customer_name'] ?? $originalInvoice['customer_name'] ?? ''),
                'customer_phone'      => (string)($order['customer_phone'] ?? $originalInvoice['customer_phone'] ?? ''),
                'customer_email'      => (string)($order['customer_email'] ?? $originalInvoice['customer_email'] ?? ''),
                'address'             => (string)($order['address'] ?? $originalInvoice['address'] ?? ''),
                'invoice_date'        => date('Y-m-d H:i:s'),
                'subtotal'            => $calc['subtotal'],
                'discount'            => $calc['discount'],
                'tier_discount_percent' => $calc['tier_discount_percent'],
                'tier_discount_amount'  => $calc['tier_discount'],
                'taxable'             => $calc['taxable'],
                'vat_rate'            => $calc['vat_rate'],
                'vat'                 => $calc['vat'],
                'shipping_fee'        => $calc['shipping'],
                'install_fee'         => $calc['install'],
                'vat_shipping_amount' => $calc['vat_shipping'],
                'vat_install_amount'  => $calc['vat_install'],
                'grand_total'         => $calc['grand'],
                'currency'            => 'VND',
                'note'                => trim($reason) ?: 'Điều chỉnh giảm / hoàn tiền đơn ' . $order['order_code'],
                'is_immutable'        => 1,
                'created_by'          => $adminId,
            ]);

            foreach ($calc['lines'] as $line) {
                static::db()->prepare(
                    'INSERT INTO invoice_items
                        (invoice_id, product_id, product_name, variant_name, variant_value,
                         quantity, unit_price, vat_rate, line_total)
                     VALUES (?,?,?,?,?,?,?,?,?)'
                )->execute([
                    $invId,
                    $line['product_id'],
                    $line['product_name'],
                    $line['variant_name'],
                    $line['variant_value'],
                    $line['quantity'],
                    $line['unit_price'],
                    $line['vat_rate'],
                    -$line['line_total'],
                ]);
            }
            $db->commit();
            write_log('invoice', 'Phát hành HĐ điều chỉnh giảm ' . $number . ' cho đơn ' . $order['order_code'] . ' (HĐ gốc ' . $originalInvoice['invoice_number'] . ')', $adminId);
            return static::find($invId);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            write_log('invoice', 'Lỗi phát hành HĐ điều chỉnh ' . $order['order_code'] . ': ' . $e->getMessage(), $adminId);
            return null;
        }
    }

    public static function itemsOfInvoice(int $invoiceId): array
    {
        return static::query('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id', [$invoiceId]);
    }

    public static function byCode(string $code): ?array
    {
        return static::first('SELECT * FROM invoices WHERE invoice_number = ? OR invoice_code = ? LIMIT 1', [$code, $code]);
    }

    /** Danh sách hóa đơn (phân trang + lọc). */
    public static function listInvoices(array $f = []): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($f['q'])) {
            $where[] = '(invoice_number LIKE ? OR invoice_code LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)';
            $like = '%' . $f['q'] . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if (!empty($f['type'])) {
            $where[] = 'type = ?';
            $params[] = $f['type'];
        }
        if (!empty($f['from']) && !empty($f['to'])) {
            $where[] = 'invoice_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY) - INTERVAL 1 SECOND';
            array_push($params, $f['from'] . ' 00:00:00', $f['to']);
        }
        $whereStr = implode(' AND ', $where);

        $stmt = static::db()->prepare("SELECT COUNT(*) AS c FROM invoices WHERE {$whereStr}");
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['c'];

        $perPage = (int)($f['per_page'] ?? 15);
        $pager = paginate($total, $perPage, max(1, (int)($f['page'] ?? 1)));

        $rows = static::query(
            "SELECT * FROM invoices WHERE {$whereStr} ORDER BY invoice_date DESC, id DESC LIMIT {$perPage} OFFSET {$pager['offset']}",
            $params
        );
        return ['rows' => $rows, 'total' => $total, 'pager' => $pager];
    }

    /**
     * Báo cáo kế toán - thuế (Dạng 2), chỉ lấy từ mục Hóa đơn.
     * Taxable_revenue = Σ(subtotal) của SALE + Σ(subtotal) của REFUND (âm)
     * VAT_payable     = Σ(vat) của SALE + Σ(vat) của REFUND (âm)
     */
    public static function taxReport(string $from, string $to): array
    {
        $rows = static::query(
            "SELECT invoice_number, type, order_id, original_invoice_id, invoice_date,
                    subtotal, discount, taxable, vat, shipping_fee, grand_total,
                    customer_name, customer_phone
             FROM invoices
             WHERE invoice_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY) - INTERVAL 1 SECOND
             ORDER BY invoice_date, id",
            [$from . ' 00:00:00', $to]
        );

        $saleSubtotal = $refundSubtotal = $saleVat = $refundVat = 0.0;
        foreach ($rows as $r) {
            if ($r['type'] === self::TYPE_SALE) {
                $saleSubtotal += (float)$r['subtotal'];
                $saleVat += (float)$r['vat'];
            } else {
                $refundSubtotal += (float)$r['subtotal']; // âm
                $refundVat += (float)$r['vat'];           // âm
            }
        }
        return [
            'rows' => $rows,
            'sale_subtotal'    => $saleSubtotal,
            'refund_subtotal'  => $refundSubtotal,
            'sale_vat'         => $saleVat,
            'refund_vat'       => $refundVat,
            'taxable_revenue'  => $saleSubtotal + $refundSubtotal,
            'vat_payable'      => $saleVat + $refundVat,
            'sale_grand'       => array_sum(array_map(fn($r) => $r['type'] === self::TYPE_SALE ? (float)$r['grand_total'] : 0, $rows)),
            'refund_grand'     => array_sum(array_map(fn($r) => $r['type'] === self::TYPE_REFUND ? (float)$r['grand_total'] : 0, $rows)),
        ];
    }

    /**
     * Báo cáo lại theo kỳ (Dạng 2 - kế toán hóa đơn): tách Hóa đơn bán / Điều chỉnh giảm.
     * group: 'day' | 'month'.
     */
    public static function taxReportByPeriod(string $from, string $to, string $group = 'day'): array
    {
        $groupExpr = [
            'day'     => "DATE_FORMAT(invoice_date, '%Y-%m-%d')",
            'week'    => "DATE_FORMAT(DATE_SUB(invoice_date, INTERVAL WEEKDAY(invoice_date) DAY), '%Y-%m-%d')",
            'month'   => "DATE_FORMAT(invoice_date, '%Y-%m')",
            'quarter' => "CONCAT(YEAR(invoice_date), '-Q', QUARTER(invoice_date))",
            'year'    => "DATE_FORMAT(invoice_date, '%Y')",
        ][$group] ?? "DATE_FORMAT(invoice_date, '%Y-%m-%d')";

        $rows = static::query(
            "SELECT {$groupExpr} AS period,
                    COALESCE(COUNT(DISTINCT CASE WHEN type='" . self::TYPE_SALE . "' THEN id END),0) AS num_invoices,
                    COALESCE(SUM(CASE WHEN type='" . self::TYPE_SALE . "' THEN subtotal ELSE 0 END),0)  AS sale_subtotal,
                    COALESCE(SUM(CASE WHEN type='" . self::TYPE_SALE . "' THEN vat ELSE 0 END),0)         AS sale_vat,
                    COALESCE(SUM(CASE WHEN type='" . self::TYPE_REFUND . "' THEN subtotal ELSE 0 END),0) AS refund_subtotal,
                    COALESCE(SUM(CASE WHEN type='" . self::TYPE_REFUND . "' THEN vat ELSE 0 END),0)       AS refund_vat,
                    COALESCE(SUM(subtotal),0) AS taxable,
                    COALESCE(SUM(vat),0)      AS vat
             FROM invoices
             WHERE invoice_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY) - INTERVAL 1 SECOND
             GROUP BY period ORDER BY period",
            [$from . ' 00:00:00', $to]
        );

        return [
            'rows'    => $rows,
            'summary' => [
                'num_invoices'    => array_sum(array_column($rows, 'num_invoices')),
                'sale_subtotal'   => array_sum(array_column($rows, 'sale_subtotal')),
                'sale_vat'        => array_sum(array_column($rows, 'sale_vat')),
                'refund_subtotal' => array_sum(array_column($rows, 'refund_subtotal')),
                'refund_vat'      => array_sum(array_column($rows, 'refund_vat')),
                'taxable'         => array_sum(array_column($rows, 'taxable')),
                'vat'             => array_sum(array_column($rows, 'vat')),
            ],
        ];
    }
}
