<?php
/**
 * WoodCon - Model Mức thuế (VAT)
 * Quản lý các mức thuế suất GTGT theo NĐ 15/2022/NĐ-CP (0%, 5%, 8%, 10%).
 * Mỗi sản phẩm gán một tax_rate_id; nếu chưa gán sẽ dùng mức mặc định (is_default).
 */

declare(strict_types=1);

namespace WoodCon;

class TaxRate extends Base
{
    protected static string $table = 'tax_rates';

    /** Mức thuế của một sản phẩm (rate %), theo tax_rate_id gán hoặc mức mặc định. */
    public static function rateForProduct(?array $product): float
    {
        $rate = null; // null = chưa xác định -> dùng mức mặc định. 0% là giá trị hợp lệ.
        if (!empty($product['tax_rate_id'])) {
            $row = static::find((int)$product['tax_rate_id']);
            if ($row && $row['status']) {
                $rate = (float)$row['rate'];
            }
        }
        if ($rate === null) {
            $def = static::defaultRate();
            if ($def) {
                $rate = (float)$def['rate'];
            }
        }
        return max(0, (float)$rate);
    }

    /** Mức thuế mặc định (is_default=1 & active). */
    public static function defaultRate(): ?array
    {
        $row = static::first('SELECT * FROM tax_rates WHERE is_default = 1 AND status = 1 LIMIT 1');
        return $row ?: null;
    }

    /** Mọi mức thuế đang hoạt động. */
    public static function active(): array
    {
        return static::where('status = 1', [], '*', 'rate ASC');
    }

    /** Đảm bảo chỉ có 1 mức mặc định. */
    public static function setDefault(int $id): bool
    {
        $db = static::db();
        $db->beginTransaction();
        try {
            $db->prepare('UPDATE tax_rates SET is_default = 0 WHERE is_default = 1')->execute();
            $db->prepare('UPDATE tax_rates SET is_default = 1 WHERE id = ?')->execute([$id]);
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            return false;
        }
    }

    /** Thuế suất dùng cho mọi sản phẩm còn 'để trống' (theo default). */
    public static function defaultRateValue(): float
    {
        $def = static::defaultRate();
        return $def ? (float)$def['rate'] : 8.0;
    }

    /**
     * Mức thuế áp dụng cho Phí vận chuyển (dịch vụ) — CẤU HÌNH ĐỘC LẬP với thuế sản phẩm,
     * lưu ở settings.tax_shipping_rate (mặc định 5%). Chỉ dùng tách ngược thông tin, không
     * cộng thêm vào Tổng cộng.
     */
    public static function shippingRate(): float
    {
        return max(0, (float)get_setting('tax_shipping_rate', 5));
    }

    /**
     * Mức thuế áp dụng cho Phí lắp đặt (dịch vụ) — CẤU HÌNH ĐỘC LẬP với thuế sản phẩm,
     * lưu ở settings.tax_install_rate (mặc định 5%). Chỉ dùng tách ngược thông tin, không
     * cộng thêm vào Tổng cộng.
     */
    public static function installRate(): float
    {
        return max(0, (float)get_setting('tax_install_rate', 5));
    }
}
