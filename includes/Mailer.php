<?php
/**
 * WoodCon - Mailer service (wrapper PHPMailer)
 * Gửi email qua SMTP. Nếu chưa cấu hình SMTP → ghi log mô phỏng (dev mode).
 */

declare(strict_types=1);

class Mailer
{
    /**
     * Gửi email OTP (đặt lại mật khẩu, xác minh, etc.)
     * @return array{ok: bool, message: string}
     */
    public static function sendOtp(string $to, string $name, string $otp, string $purposeLabel = 'đặt lại mật khẩu'): array
    {
        $subject = "Mã xác thực WoodCon - {$purposeLabel}";
        $htmlBody = self::otpTemplate($name, $otp, $purposeLabel);

        return self::send($to, $name, $subject, $htmlBody);
    }

    /**
     * Gửi email generic
     * @return array{ok: bool, message: string}
     */
    public static function send(string $to, string $name, string $subject, string $htmlBody): array
    {
        $host = (string)get_setting('smtp_host', '');
        $port = (int)get_setting('smtp_port', 587);
        $user = (string)get_setting('smtp_user', '');
        $pass = (string)get_setting('smtp_pass', '');
        $secure = (string)get_setting('smtp_secure', 'tls');
        $fromEmail = (string)get_setting('site_email', 'lienhe@woodcon.vn');
        $fromName = (string)get_setting('site_name', 'WoodCon');

        // Fallback: nếu chưa cấu hình SMTP.
        // - APP_DEBUG=1 (dev): mô phỏng — ghi log, coi như gửi thành công.
        // - Production (APP_DEBUG=0): KHÔNG mô phỏng — báo lỗi thay vì "giả vờ gửi được mail".
        if ($host === '' || $user === '') {
            if (APP_DEBUG) {
                write_log('mail', "MÔ PHỎNG gửi mail tới {$to} - Subject: {$subject} | OTP nếu có trong body.");
                return ['ok' => true, 'message' => '(Dev) Mail đã được ghi log.'];
            }
            write_log('mail', "CẢNH BÁO: SMTP chưa cấu hình nhưng đang chạy PRODUCTION — KHÔNG gửi được mail tới {$to} (Subject: {$subject}).");
            return ['ok' => false, 'message' => 'Không thể gửi email. Vui lòng thử lại sau.'];
        }

        static::loadPhpMailer();

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port;
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = $pass;

            if ($secure === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($secure === 'tls') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to, $name);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);

            $mail->send();
            write_log('mail', "Gửi mail thành công tới {$to} - Subject: {$subject}");
            return ['ok' => true, 'message' => 'Đã gửi email.'];
        } catch (\Throwable $e) {
            write_log('mail', "LỖI gửi mail tới {$to}: " . $e->getMessage());
            return ['ok' => false, 'message' => 'Không thể gửi email. Vui lòng thử lại sau.'];
        }
    }

    /**
     * Kiểm tra kết nối SMTP: thực hiện SMTP handshake + xác thực, KHÔNG gửi mail.
     * @return array{ok: bool, message: string}
     */
    public static function testConnection(): array
    {
        $host   = (string)get_setting('smtp_host', '');
        $port   = (int)get_setting('smtp_port', 587);
        $user   = (string)get_setting('smtp_user', '');
        $pass   = (string)get_setting('smtp_pass', '');
        $secure = (string)get_setting('smtp_secure', 'tls');

        if ($host === '' || $user === '' || $pass === '') {
            return ['ok' => false, 'message' => 'Chưa cấu hình đủ SMTP (host + username + password).'];
        }

        static::loadPhpMailer();

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port;
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = $pass;

            if ($secure === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($secure === 'tls') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }

            $ok = $mail->smtpConnect();
            $mail->smtpClose();
            if ($ok) {
                write_log('mail', 'Kiểm tra kết nối SMTP thành công: ' . $host . ':' . $port . ' (' . $user . ')');
                return ['ok' => true, 'message' => 'Kết nối SMTP thành công: ' . $host . ':' . $port . ' (' . $user . ').'];
            }
            write_log('mail', 'Kiểm tra kết nối SMTP thất bại: xác thực không thành công (' . $host . ':' . $port . ')');
            return ['ok' => false, 'message' => 'Xác thực SMTP không thành công — kiểm tra host/cổng/tài khoản/mật khẩu.'];
        } catch (\Throwable $e) {
            write_log('mail', 'Kiểm tra kết nối SMTP lỗi: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Không kết nối được máy chủ SMTP: ' . $e->getMessage()];
        }
    }

    /** Nạp composer autoload (PHPMailer) nếu chưa được load (export.php chỉ nạp khi xuất .xlsx) */
    private static function loadPhpMailer(): void
    {
        if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            require_once BASE_PATH . '/vendor/autoload.php';
        }
    }

    /** HTML template cho OTP */
    private static function otpTemplate(string $name, string $otp, string $purposeLabel): string
    {
        $siteName = (string)get_setting('site_name', 'WoodCon');
        $sitePhone = (string)get_setting('site_phone', '');
        $siteEmail = (string)get_setting('site_email', '');

        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f5f1eb;font-family:'Segoe UI',Tahoma,sans-serif;">
<div style="max-width:520px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">

  <div style="background:#2d241e;padding:28px 32px;text-align:center;">
    <h1 style="margin:0;color:#f9f3ea;font-size:22px;font-weight:700;">{$siteName}</h1>
  </div>

  <div style="padding:32px;">
    <p style="color:#4e4540;font-size:15px;margin:0 0 12px;">Xin chào <strong>{$name}</strong>,</p>
    <p style="color:#4e4540;font-size:15px;margin:0 0 24px;">Bạn vừa yêu cầu <strong>{$purposeLabel}</strong>. Sử dụng mã dưới đây:</p>

    <div style="text-align:center;margin:24px 0;">
      <span style="display:inline-block;font-size:32px;font-weight:800;letter-spacing:8px;color:#2d241e;background:#f5f1eb;padding:16px 36px;border-radius:8px;">{$otp}</span>
    </div>

    <p style="color:#7f756f;font-size:13px;margin:0 0 8px;">Mã có hiệu lực trong <strong>5 phút</strong>. Nếu bạn không yêu cầu, vui lòng bỏ qua email này.</p>
  </div>

  <div style="background:#f9f3ea;padding:20px 32px;text-align:center;">
    <p style="margin:0;color:#7f756f;font-size:12px;">{$siteName} &mdash; Nội thất gỗ, tinh tế cho người nhà</p>
    <p style="margin:4px 0 0;color:#7f756f;font-size:12px;">Hotline: {$sitePhone} &bull; Email: {$siteEmail}</p>
  </div>

</div>
</body>
</html>
HTML;
    }
}