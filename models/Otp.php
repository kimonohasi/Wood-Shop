<?php
/**
 * WoodCon - Dịch vụ OTP (mô phỏng)
 * - Gửi OTP qua SMS là MÔ PHỎNG: mã được ghi log + trả về trong response (dev).
 * - Dùng khi: đặt hàng COD tier Vàng, đăng ký số điện thoại, xác thực.
 */

declare(strict_types=1);

namespace WoodCon;

class Otp
{
    public const PURPOSE_ORDER = 'order';
    public const PURPOSE_REGISTER = 'register';
    public const PURPOSE_RESET = 'reset';
    public const TTL_MINUTES = 5;

    /** Tạo + "gửi" OTP. Trả về mã để dev xem (mô phỏng SMS/Email). */
    public static function send(string $phone, string $purpose = self::PURPOSE_ORDER): string
    {
        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expired = date('Y-m-d H:i:s', strtotime('+' . self::TTL_MINUTES . ' minutes'));

        OtpVerification::insert([
            'phone'     => $phone,
            'purpose'   => $purpose,
            'otp'       => $otp,
            'is_verified' => 0,
            'expired_at' => $expired,
        ]);

        // Đồng thời cập nhật cho tài khoản (nếu có) để tái sử dụng khi quên mật khẩu
        Database::connect()->prepare('UPDATE users SET otp_code = ?, otp_purpose = ?, otp_expired = ? WHERE phone = ?')
            ->execute([$otp, $purpose, $expired, $phone]);

        // Gửi SMS THẬT nếu đã cấu hình cổng SMS (Phần 3); ngược lại mô phỏng (ghi log + trả mã dev)
        if (Sms::isConfigured()) {
            $sent = Sms::sendOtp($phone, $otp, $purpose);
            write_log('otp', ($sent ? 'SMS đã gửi tới ' : 'Gửi SMS thất bại tới ') . "{$phone} (mục đích {$purpose}): mã OTP = {$otp}");
        } else {
            write_log('otp', "SMS mô phỏng tới {$phone} (mục đích {$purpose}): mã OTP = {$otp}");
        }

        // Ghi ra file error_log PHP (storage/php-error.log) để dễ tra mã OTP khi test (sms_enabled = 0)
        error_log("[OTP TEST] Phone: {$phone} - Code: {$otp} - Purpose: {$purpose}");

        return $otp;
    }

    /** Gửi OTP qua email (dùng PHPMailer). Trả về mã OTP. */
    public static function sendToEmail(string $email, string $purpose = self::PURPOSE_RESET): string
    {
        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expired = date('Y-m-d H:i:s', strtotime('+' . self::TTL_MINUTES . ' minutes'));

        OtpVerification::insert([
            'phone'     => null,
            'email'     => $email,
            'purpose'   => $purpose,
            'otp'       => $otp,
            'is_verified' => 0,
            'expired_at' => $expired,
        ]);

        Database::connect()->prepare('UPDATE users SET otp_code = ?, otp_purpose = ?, otp_expired = ? WHERE email = ?')
            ->execute([$otp, $purpose, $expired, $email]);

        $user = User::findBy('email', $email);
        $userName = $user['name'] ?? 'Quý khách';
        $purposeLabel = $purpose === self::PURPOSE_RESET ? 'đặt lại mật khẩu' : 'xác thực tài khoản';
        \Mailer::sendOtp($email, $userName, $otp, $purposeLabel);

        // Ghi ra file error_log PHP (storage/php-error.log) để dễ tra mã OTP khi test (sms_enabled = 0)
        error_log("[OTP TEST] Email: {$email} - Code: {$otp} - Purpose: {$purpose}");

        return $otp;
    }

    /** Xác thực OTP. Xoá số lần nhập sai bằng cách chỉ cho phép mã mới nhất. */
    public static function verify(string $phone, string $inputOtp, string $purpose = self::PURPOSE_ORDER): bool
    {
        $row = OtpVerification::first(
            "SELECT * FROM otp_verifications WHERE phone = ? AND purpose = ? AND is_verified = 0
             ORDER BY id DESC LIMIT 1",
            [$phone, $purpose]
        );
        if (!$row) {
            return false;
        }
        if ($row['expired_at'] < date('Y-m-d H:i:s')) {
            return false;
        }
        if (!hash_equals((string)$row['otp'], (string)$inputOtp)) {
            return false;
        }

        OtpVerification::update((int)$row['id'], ['is_verified' => 1]);
        Database::connect()->prepare('UPDATE users SET phone_verify = 1, otp_code = NULL, otp_expired = NULL WHERE phone = ?')
            ->execute([$phone]);
        return true;
    }
}

/** Model lưu OTP */
class OtpVerification extends Base
{
    protected static string $table = 'otp_verifications';
}