<?php
/**
 * WoodCon - Nạp biến từ file .env (theo phong cách 12-factor)
 *
 * - File `.env` nằm ở thư mục gốc dự án, bị `.gitignore` (KHÔNG commit).
 *   Bản mẫu được commit là `.env.example` (chỗ giữ chỗ).
 * - Quy tắc ưu tiên: biến môi trường THẬT của server (Apache vhost, Docker,
 *   `setx`, shell...) luôn thắng giá trị trong file `.env`.
 * - Giá trị được nhồi vào process qua `putenv()` + `$_ENV` nên mọi chỗ gọi
 *   `getenv('DB_NAME')`... trong dự án chạy như cũ — không phải sửa hàng loạt file.
 *   Lưu ý: `variables_order` trên XAMPP là `GPCS` (không có E) nên `$_ENV` rỗng
 *   mặc định — luôn kiểm tra "biến có tồn tại" qua `getenv()`.
 */

declare(strict_types=1);

namespace WoodCon;

final class Env
{
    private static bool $loaded = false;

    /**
     * Nạp file .env (chỉ gọi 1 lần / request).
     * File không tồn tại hoặc không đọc được → bỏ qua im lặng; dự án vẫn chạy
     * với các giá trị mặc định trong config/config.php.
     */
    public static function load(string $envFile): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        if ($envFile === '' || !is_readable($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (str_starts_with($line, 'export ')) {
                $line = ltrim(substr($line, 7));
            }

            $eq = strpos($line, '=');
            if ($eq === false) {
                continue;
            }

            $key = trim(substr($line, 0, $eq));
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                continue;
            }

            // Biến môi trường thật của server đã đặt → giữ nguyên, không ghi đè.
            if (getenv($key) !== false) {
                continue;
            }

            $value = self::parseValue(substr($line, $eq + 1));
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }

    /**
     * Đọc một biến: môi trường thật / .env → không có thì dùng $default.
     * Chuỗi rỗng cũng coi như "chưa đặt" (giống `getenv(...) ?: mặc định` cũ).
     */
    public static function get(string $key, string $default = ''): string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }
        return $value;
    }

    /** Biến có giá trị khác rỗng không? (dùng để bật/tắt tính năng theo secret) */
    public static function has(string $key): bool
    {
        return self::get($key) !== '';
    }

    /** Bóc giá trị của một dòng `KEY=...`: có nháy → lấy trong nháy; không → cắt comment nối tiếp. */
    private static function parseValue(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        $quote = $raw[0];
        if ($quote === '"' || $quote === "'") {
            $end = strrpos($raw, $quote);
            if ($end === false || $end === 0) {
                return '';
            }
            $value = substr($raw, 1, $end - 1);
            if ($quote === '"') {
                $value = strtr($value, [
                    '\\n'  => "\n",
                    '\\r'  => "\r",
                    '\\t'  => "\t",
                    '\\\\' => '\\',
                    '\\"'  => '"',
                ]);
            }
            return $value;
        }

        // Giá trị không bọc nháy: cắt bỏ comment nối tiếp (dấu # sau khoảng trắng)
        if (preg_match('/\s#/', $raw, $m, PREG_OFFSET_CAPTURE) === 1) {
            $raw = substr($raw, 0, (int)$m[0][1]);
        }
        return trim($raw);
    }
}
