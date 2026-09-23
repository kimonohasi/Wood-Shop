<?php
/**
 * WoodCon - Xác thực: đăng nhập / đăng ký / quên mật khẩu / Google OAuth
 * - Form gửi thường (tải lại trang) hoạt động như trước.
 * - Request AJAX (header X-Requested-With: XMLHttpRequest) trả JSON để popup auth
 *   giữ nguyên trang, hiện lỗi inline và reload sau khi thành công.
 */

declare(strict_types=1);

namespace WoodCon\Controllers;

use WoodCon\Cart;
use WoodCon\Otp;
use WoodCon\User;

class AuthController extends BaseController
{
    const FORM_BACK_ROUTES = [
        'login' => BASE_URL . '/dang-nhap', 'register' => BASE_URL . '/dang-ky', 'forgot' => BASE_URL . '/quen-mat-khau',
    ];

    public function login(string $sub = ''): void
    {
        if ($sub === 'google') {
            $this->googleStart();
            return;
        }

        if ($this->isPost()) {
            if (!verify_csrf($this->post('_token'))) {
                $this->fail('Phiên làm việc hết hạn.', self::FORM_BACK_ROUTES['login'], 419);
            }
            // Kiểm tra reCAPTCHA — bỏ qua khi chưa cấu hình (khớp verify_recaptcha ở admin)
            if (RECAPTCHA_SECRET_KEY && !$this->verifyRecaptcha((string)$this->post('g-recaptcha-response'))) {
                $this->fail('Vui lòng xác nhận bạn không phải người máy.', self::FORM_BACK_ROUTES['login']);
            }
            $account = trim((string)$this->post('account'));
            // Rate-limit theo IP + account: tối đa 8 lần / 10 phút
            $rlKey = 'login:' . md5(($_SERVER['REMOTE_ADDR'] ?? '') . '|' . $account);
            if (rate_limit_blocked($rlKey, 8, 600)) {
                $this->fail('Bạn đã thử đăng nhập quá nhiều lần. Vui lòng thử lại sau ít phút.', self::FORM_BACK_ROUTES['login'], 429);
            }
            rate_limit_hit($rlKey, 8, 600);

            $result = User::login($account, (string)$this->post('password'));
            if (!$result['ok']) {
                // Trả thông báo chung chung để không lộ email tồn tại / không tồn tại
                $this->fail('Tài khoản hoặc mật khẩu không đúng.', self::FORM_BACK_ROUTES['login']);
            }
            rate_limit_clear($rlKey);
            $_SESSION['user_id'] = (int)$result['user']['id'];
            // Gộp giỏ vãng lai (nếu có) vào tài khoản
            Cart::mergeGuestToUser((int)$result['user']['id'], $_SESSION['cart_token'] ?? null);
            unset($_SESSION['cart_token']);

            $back = trim((string)$this->post('next')) ?: ($_SESSION['login_redirect'] ?? BASE_URL);
            unset($_SESSION['login_redirect']);
            if ($this->isAjax()) {
                $this->json(['ok' => true, 'redirect' => $back]);
            }
            redirect($back);
        }
        $this->renderAuth('auth_login', [
            'pageTitle' => 'Đăng nhập - WoodCon',
            'next'      => $this->get('next', BASE_URL),
        ]);
    }

    public function register(): void
    {
        if ($this->isPost()) {
            if (!verify_csrf($this->post('_token'))) {
                $this->fail('Phiên làm việc hết hạn.', self::FORM_BACK_ROUTES['register'], 419);
            }
            $errors = validate_form($_POST, [
                'name'     => ['required' => true, 'max' => 120],
                'username' => ['required' => true, 'username' => true],
                'email'    => ['required' => true, 'email' => true],
                'phone'    => ['required' => true, 'max' => 20],
                'password' => ['required' => true, 'min' => 6],
            ]);
            if ($errors) {
                $this->fail(reset($errors), self::FORM_BACK_ROUTES['register']);
            }
            $result = User::register([
                'name'     => trim((string)$this->post('name')),
                'username' => trim((string)$this->post('username')),
                'email'    => trim((string)$this->post('email')),
                'phone'    => trim((string)$this->post('phone')),
                'password' => (string)$this->post('password'),
                'gender'   => 'other',
            ]);
            if (!$result['ok']) {
                $this->fail($result['message'], self::FORM_BACK_ROUTES['register']);
            }
            $_SESSION['user_id'] = $result['id'];
            Cart::mergeGuestToUser($result['id'], $_SESSION['cart_token'] ?? null);
            unset($_SESSION['cart_token']);
            set_flash('success', 'Chào mừng bạn đến với WoodCon!');

            $back = trim((string)$this->post('next')) ?: BASE_URL;
            if ($this->isAjax()) {
                $this->json(['ok' => true, 'redirect' => $back]);
            }
            redirect($back);
        }
        $this->renderAuth('auth_register', ['pageTitle' => 'Đăng ký - WoodCon']);
    }

    public function forgot(): void
    {
        if ($this->isPost()) {
            if (!verify_csrf($this->post('_token'))) {
                $this->fail('Phiên làm việc hết hạn.', self::FORM_BACK_ROUTES['forgot'], 419);
            }
            $step   = $this->post('step', 'send');
            $method = $this->post('method', 'email');

            if ($step === 'send') {
                if ($method === 'email') {
                    $email = trim(strtolower((string)$this->post('email', '')));
                    $u = User::findBy('email', $email);
                    if (!$u) {
                        $this->fail('Không tìm thấy email trong hệ thống.', self::FORM_BACK_ROUTES['forgot']);
                    }
                    Otp::sendToEmail($email, Otp::PURPOSE_RESET);
                    if ($this->isAjax()) {
                        $this->json([
                            'ok'      => true,
                            'step'    => 'verify',
                            'method'  => 'email',
                            'account' => $email,
                            'message' => 'Đã gửi mã xác thực vào email của bạn. Vui lòng kiểm tra hộp thư.',
                        ]);
                    }
                    set_flash('success', 'Đã gửi mã xác thực vào email của bạn. Vui lòng kiểm tra hộp thư.');
                    $this->renderAuth('auth_forgot', [
                        'pageTitle' => 'Đặt lại mật khẩu - WoodCon',
                        'account'   => $email,
                        'method'    => 'email',
                        'step'      => 'verify',
                    ]);
                    return;
                } else {
                    $phone = trim((string)$this->post('phone', ''));
                    $u = User::findBy('phone', $phone);
                    if (!$u) {
                        $this->fail('Không tìm thấy số điện thoại trong hệ thống.', self::FORM_BACK_ROUTES['forgot']);
                    }
                    Otp::send($phone, Otp::PURPOSE_RESET);
                    if ($this->isAjax()) {
                        $this->json([
                            'ok'      => true,
                            'step'    => 'verify',
                            'method'  => 'phone',
                            'account' => $phone,
                            'message' => 'Đã gửi mã xác thực. (Mô phỏng SMS: mã hiển thị trên màn hình.)',
                        ]);
                    }
                    set_flash('success', 'Đã gửi mã xác thực. (Mô phỏng SMS: mã hiển thị trên màn hình.)');
                    $this->renderAuth('auth_forgot', [
                        'pageTitle' => 'Đặt lại mật khẩu - WoodCon',
                        'account'   => $phone,
                        'method'    => 'phone',
                        'step'      => 'verify',
                    ]);
                    return;
                }
            }
            if ($step === 'reset') {
                $account = trim((string)$this->post('account', ''));
                $method  = (string)$this->post('method', 'email');
                $res = User::resetPassword($account, trim((string)$this->post('otp')), (string)$this->post('password'), $method);
                if (!$res['ok']) {
                    $this->fail($res['message'], self::FORM_BACK_ROUTES['forgot']);
                }
                if ($this->isAjax()) {
                    $this->json(['ok' => true, 'message' => $res['message'] . ' Vui lòng đăng nhập lại.']);
                }
                set_flash('success', $res['message'] . ' Vui lòng đăng nhập lại.');
                redirect(BASE_URL . '/dang-nhap');
            }
        }
        $this->renderAuth('auth_forgot', ['pageTitle' => 'Đặt lại mật khẩu - WoodCon', 'account' => '', 'method' => 'email', 'step' => 'send']);
    }

    // ---------------- Google OAuth (cURL thuần, không thư viện) ----------------

    /** Bước 1: điều hướng sang Google để cấp quyền */
    public function googleStart(): never
    {
        if (!defined('GOOGLE_CLIENT_ID') || !GOOGLE_CLIENT_ID || !GOOGLE_CLIENT_SECRET) {
            set_flash('error', 'Đăng nhập bằng Google chưa được cấu hình. Hãy đăng nhập bằng email/SĐT.');
            redirect(BASE_URL . '/dang-nhap');
        }
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;
        $_SESSION['oauth_next']  = trim((string)$this->get('next')) ?: ($_SESSION['login_redirect'] ?? '');

        $params = http_build_query([
            'client_id'     => GOOGLE_CLIENT_ID,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'prompt'        => 'select_account',
        ]);
        redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    }

    /** Bước 2: callback từ Google - trao đổi code, lấy thông tin, login/link/create */
    public function googleCallback(): never
    {
        $state    = (string)$this->get('state', '');
        $sessState = (string)($_SESSION['oauth_state'] ?? '');
        unset($_SESSION['oauth_state']);
        if ($state === '' || $sessState === '' || !hash_equals($sessState, $state)) {
            set_flash('error', 'Phiên xác thực Google không hợp lệ. Vui lòng thử lại.');
            redirect(BASE_URL . '/dang-nhap');
        }

        $code = (string)$this->get('code', '');
        if ($code === '') {
            set_flash('error', 'Đã huỷ đăng nhập bằng Google.');
            redirect(BASE_URL . '/dang-nhap');
        }

        $token = $this->googleExchangeCode($code);
        if (empty($token['access_token'])) {
            write_log('oauth', 'Trao đổi mã Google thất bại: ' . json_encode($token, JSON_UNESCAPED_UNICODE));
            set_flash('error', 'Không thể đăng nhập bằng Google. Vui lòng thử lại.');
            redirect(BASE_URL . '/dang-nhap');
        }
        $info = $this->googleFetchUserInfo((string)$token['access_token']);

        $email = strtolower(trim((string)($info['email'] ?? '')));
        if ($email === '') {
            write_log('oauth', 'Không lấy được email: ' . json_encode($info, JSON_UNESCAPED_UNICODE));
            set_flash('error', 'Không lấy được email từ tài khoản Google. Vui lòng thử lại.');
            redirect(BASE_URL . '/dang-nhap');
        }
        $gid     = (string)($info['id'] ?? '');
        $name    = trim((string)($info['name'] ?? '')) ?: ucfirst((string)strstr($email, '@', true)) ?: $email;
        $picture = !empty($info['picture']) ? (string)$info['picture'] : null;

        // Ưu tiên tìm theo google_id; nếu chưa có, link vào tài khoản email đã đăng ký (nếu có)
        $user = $gid !== '' ? User::findBy('google_id', $gid) : null;
        if (!$user) {
            $user = User::findBy('email', $email);
            if ($user && $gid !== '') {
                User::update((int)$user['id'], ['google_id' => $gid]);
            }
        }
        if (!$user) {
            $id = User::createWithGoogle($email, $name, $gid, $picture);
            $user = User::find($id);
        }
        if (!$user || (int)$user['status'] !== 1) {
            set_flash('error', 'Tài khoản đã bị khóa. Vui lòng liên hệ hỗ trợ.');
            redirect(BASE_URL . '/dang-nhap');
        }

        $_SESSION['user_id'] = (int)$user['id'];
        Cart::mergeGuestToUser((int)$user['id'], $_SESSION['cart_token'] ?? null);
        unset($_SESSION['cart_token']);

        $back = trim((string)($_SESSION['oauth_next'] ?? '')) ?: ($_SESSION['login_redirect'] ?? BASE_URL);
        unset($_SESSION['oauth_next'], $_SESSION['login_redirect']);
        set_flash('success', 'Đăng nhập bằng Google thành công. Chào mừng đến với WoodCon!');
        redirect($back);
    }

    private function googleExchangeCode(string $code): array
    {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS     => http_build_query([
                'code'          => $code,
                'client_id'     => GOOGLE_CLIENT_ID,
                'client_secret' => GOOGLE_CLIENT_SECRET,
                'redirect_uri'  => GOOGLE_REDIRECT_URI,
                'grant_type'    => 'authorization_code',
            ]),
            // XAMPP chưa cấu hình CA bundle -> tắt verify SSL để chạy dev localhost
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error !== '') {
            write_log('oauth', 'cURL token: ' . $error);
            return [];
        }
        $data = json_decode((string)$body, true);
        return is_array($data) ? $data : [];
    }

    private function googleFetchUserInfo(string $accessToken): array
    {
        $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo?alt=json');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error !== '') {
            write_log('oauth', 'cURL userinfo: ' . $error);
            return [];
        }
        $data = json_decode((string)$body, true);
        return is_array($data) ? $data : [];
    }

    /** Trả lỗi: nếu AJAX -> JSON, nếu không -> flash + redirect về trang tương ứng */
    private function verifyRecaptcha(string $response): bool
    {
        if (!RECAPTCHA_SECRET_KEY) {
            return true; // Bỏ qua nếu chưa cấu hình
        }
        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'secret'   => RECAPTCHA_SECRET_KEY,
                'response' => $response,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        $data = json_decode((string)$body, true);
        return is_array($data) && ($data['success'] ?? false) === true;
    }

    private function fail(string $message, string $back, int $code = 200): never
    {
        if ($this->isAjax()) {
            $this->json(['ok' => false, 'message' => $message], $code);
        }
        set_flash('error', $message);
        redirect($back);
    }

    public function logout(): void
    {
        unset($_SESSION['user_id']);
        redirect(BASE_URL);
    }
}