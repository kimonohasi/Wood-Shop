<?php
/**
 * WoodCon - Cổng gửi SMS (Phần 3 - OTP thật)
 * Mặc định eSMS (brandname). Bật bằng cài đặt sms_enabled=1 + nhập esms_api_key.
 * Khi chưa cấu hình -> không gửi thật (fallback ghi log), luôn báo lỗi gửi để không lừa hệ thống.
 */

declare(strict_types=1);

namespace WoodCon;

class Sms
{
    public const PROVIDER_ESMS = 'esms';

    /**
     * Kiểm tra đã cấu hình sẵn sàng gửi SMS thật chưa.
     */
    public static function isConfigured(): bool
    {
        return (int)get_setting('sms_enabled', 0) === 1 && trim((string)get_setting('esms_api_key', '')) !== '';
    }

    /**
     * Kiểm tra kết nối thật tới eSMS (lấy số dư tài khoản, không gửi tin nhắn).
     * @return array{ok:bool, message:string}
     */
    public static function testConnection(): array
    {
        if (trim((string)get_setting('esms_api_key', '')) === '') {
            return ['ok' => false, 'message' => 'Chưa nhập eSMS API Key (mục SMS - Gửi mã OTP).'];
        }

        $url = 'https://rest.esms.vn/MainService.svc/json/GetBalance_json';
        $query = http_build_query([
            'ApiKey'    => trim((string)get_setting('esms_api_key', '')),
            'SecretKey' => trim((string)get_setting('esms_secret', '')),
        ]);

        $ctx = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true]]);
        $res = @file_get_contents($url . '?' . $query, false, $ctx);
        if ($res === false) {
            write_log('sms', 'eSMS kiểm tra kết nối: không phản hồi.');
            return ['ok' => false, 'message' => 'Không kết nối được dịch vụ SMS (rest.esms.vn).'];
        }

        $data = json_decode($res, true);
        $code = (int)($data['CodeResult'] ?? 0);
        if ($code === 100) {
            $balance = trim((string)($data['Balance'] ?? ''));
            $msg = 'Kết nối eSMS thành công.' . ($balance !== '' ? ' Số dư: ' . $balance . ' đ.' : '');
            return ['ok' => true, 'message' => $msg];
        }

        $error = (string)($data['ErrorMessage'] ?? 'Mã lỗi ' . $code . ' từ eSMS');
        write_log('sms', 'eSMS kiểm tra kết nối thất bại: ' . $error . ' (code ' . $code . ').');
        return ['ok' => false, 'message' => $error];
    }

    /**
     * Gửi một tin nhắn SMS qua provider đang cấu hình.
     * @return array{ok:bool,error?:string}
     */
    public static function send(string $phone, string $content): array
    {
        $provider = strtolower(trim((string)get_setting('sms_provider', self::PROVIDER_ESMS)));
        if (!static::isConfigured()) {
            return ['ok' => false, 'error' => 'SMS chưa được cấu hình (cần nhập eSMS API Key vào phần Cài đặt).'];
        }

        if ($provider === self::PROVIDER_ESMS) {
            return static::sendEsms($phone, $content);
        }
        return ['ok' => false, 'error' => 'Provider SMS không được hỗ trợ.'];
    }

    /**
     * Gửi SMS qua eSMS (rest.esms.vn).
     * Docs: https://esms.vn — SendMultipleMessage_V4_get
     */
    protected static function sendEsms(string $phone, string $content): array
    {
        $url = 'https://rest.esms.vn/MainService.svc/json/SendMultipleMessage_V4_get';
        $query = http_build_query([
            'Phone'     => $phone,
            'Content'   => $content,
            'ApiKey'    => trim((string)get_setting('esms_api_key', '')),
            'SecretKey' => trim((string)get_setting('esms_secret', '')),
            'SmsType'   => max(2, (int)get_setting('esms_sms_type', 2)),
            'Brandname' => trim((string)get_setting('esms_brandname', '')),
        ]);

        $ctx = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true]]);
        $res = @file_get_contents($url . '?' . $query, false, $ctx);
        if ($res === false) {
            write_log('error', 'SMS gateway eSMS không phản hồi (' . $phone . ').');
            return ['ok' => false, 'error' => 'Không kết nối được dịch vụ SMS.'];
        }

        $data = json_decode($res, true);
        $code = (int)($data['CodeResult'] ?? 999);
        if ($code === 100) {
            return ['ok' => true];
        }
        $error = (string)($data['ErrorMessage'] ?? 'Mã lỗi ' . $code . ' từ eSMS');
        write_log('otp', 'eSMS thất bại: ' . $error . ' (code ' . $code . ').');
        return ['ok' => false, 'error' => 'Dịch vụ SMS tạm thời lỗi, vui lòng thử lại.'];
    }

    /**
     * Soạn + gửi SMS OTP. Trả về true nếu đã gửi thật thành công.
     */
    public static function sendOtp(string $phone, string $otp, string $purpose = 'order'): bool
    {
        $brand = trim((string)get_setting('esms_brandname', 'WoodCon'));
        $label = match ($purpose) {
            'register' => 'đăng ký tài khoản',
            'reset'    => 'đặt lại mật khẩu',
            default    => 'xác thực đơn hàng',
        };
        $content = "{$brand}: Ma OTP cua ban la {$otp}. Co hieu luc trong 5 phut. Dung cho việc {$label}.";
        // Gửi tiếng Việt không dấu để tránh lỗi encoding SMS
        $content = str_lower_no_accent($content);
        // "Đăng ký tài khoản" -> giữ nghĩa
        $content = str_replace(['dang ky tai khoan', 'dat lai mat khau', 'xac thuc don hang'],
            ['dang ky tai khoan', 'dat lai mat khau', 'xac thuc don hang'], $content);

        $res = static::send($phone, $content);
        return $res['ok'];
    }
}