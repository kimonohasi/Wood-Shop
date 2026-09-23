<?php
/**
 * WoodCon - Khu vực tài khoản
 * Routes: tai-khoan, /don-hang, /don-hang/{code}, /yeu-thich, /doi-mat-khau, /order/{code} (đảo cũ)
 */

declare(strict_types=1);

namespace WoodCon\Controllers;

use WoodCon\Order;
use WoodCon\User;

class AccountController extends BaseController
{
    public function dispatch(array $segments): void
    {
        require_login();
        $action = $segments[0] ?? 'profile';
        switch ($action) {
            case 'don-hang':
                $code = $segments[1] ?? '';
                if ($code !== '' && ($segments[2] ?? '') === 'huy') {
                    $this->cancel($this->post('_token') ?? '', $code);
                }
                if ($code !== '' && ($segments[2] ?? '') === 'huy-yeu-cau') {
                    $this->cancelRequest($this->post('_token') ?? '', $code);
                }
                if ($code !== '') {
                    $this->orderDetail($code);
                }
                $this->orders();
                // no break
            case 'order': // alias tương thích cho trang sau đặt đơn (guest tự vẫn được redirect ở đó)
                $this->orderDetail($segments[1] ?? '');
                // no break
            case 'yeu-thich':
                $this->wishlist();
                // no break
            case 'diem-thuong':
                $this->points();
                // no break
            case 'doi-mat-khau':
                $this->changePassword();
                // no break
            default:
                $this->profile();
        }
    }

    private function profile(): void
    {
        $user = current_user();
        if ($this->isPost()) {
            if (!verify_csrf($this->post('_token'))) {
                set_flash('error', 'Phiên làm việc hết hạn.');
                redirect(BASE_URL . '/tai-khoan');
            }
            User::updateProfile((int)$user['id'], [
                'name'     => trim($this->post('name')),
                'gender'   => $this->post('gender', 'other'),
                'birthday' => $this->post('birthday') ?: null,
                'address'  => trim($this->post('address')),
                'ward'     => trim($this->post('ward')),
                'district' => trim($this->post('district')),
                'city'     => trim($this->post('city')),
            ]);
            set_flash('success', 'Đã cập nhật thông tin tài khoản.');
            redirect(BASE_URL . '/tai-khoan');
        }
        $this->render('account_profile', [
            'pageTitle' => 'Thông tin tài khoản - WoodCon',
            'user'      => $user,
        ]);
    }

    private function orders(): never
    {
        $page = max(1, (int)$this->get('page', 1));
        $data = Order::paginatedForUser((int)current_user()['id'], $page, 10);
        $this->render('account_orders', [
            'pageTitle' => 'Đơn hàng của tôi - WoodCon',
            'orders'    => $data['rows'],
            'pager'     => $data['pager'],
            'total'     => $data['total'],
        ]);
    }

    private function orderDetail(string $code): never
    {
        $order = Order::byCode($code);
        if (!$order || !Order::belongTo($order, (int)current_user()['id'])) {
            set_flash('error', 'Không tìm thấy đơn hàng.');
            redirect(BASE_URL . '/tai-khoan/don-hang');
        }
        $this->render('account_order_detail', [
            'pageTitle' => 'Đơn hàng ' . $code . ' - WoodCon',
            'order'     => $order,
            'items'     => Order::itemsOf((int)$order['id']),
        ]);
    }

    /** Khách tự hủy trong thời gian chờ xác nhận */
    private function cancel(string $token, string $code): never
    {
        if (!$this->isPost() || !verify_csrf($token)) {
            set_flash('error', 'Yêu cầu không hợp lệ.');
            redirect(BASE_URL . '/tai-khoan/don-hang/' . $code);
        }
        $order = Order::byCode($code);
        if (!$order || !Order::belongTo($order, (int)current_user()['id'])) {
            set_flash('error', 'Không tìm thấy đơn hàng.');
            redirect(BASE_URL . '/tai-khoan/don-hang');
        }
        $res = Order::cancelWithinWait($order);
        set_flash($res['ok'] ? 'success' : 'error', $res['message']);
        redirect(BASE_URL . '/tai-khoan/don-hang/' . $code);
    }

    /** Khách yêu cầu hủy sau khi đơn đã xác nhận (chờ shop duyệt) */
    private function cancelRequest(string $token, string $code): never
    {
        if (!$this->isPost() || !verify_csrf($token)) {
            set_flash('error', 'Yêu cầu không hợp lệ.');
            redirect(BASE_URL . '/tai-khoan/don-hang/' . $code);
        }
        $order = Order::byCode($code);
        if (!$order || !Order::belongTo($order, (int)current_user()['id'])) {
            set_flash('error', 'Không tìm thấy đơn hàng.');
            redirect(BASE_URL . '/tai-khoan/don-hang');
        }
        $reason = trim($this->post('reason', 'Khách yêu cầu hủy.'));
        $res = Order::requestCancel($order, $reason);
        set_flash($res['ok'] ? 'success' : 'error', $res['message']);
        redirect(BASE_URL . '/tai-khoan/don-hang/' . $code);
    }

    private function wishlist(): never
    {
        $userId = (int)current_user()['id'];
        if ($this->isPost() && $this->post('action') === 'toggle') {
            $pid = (int)$this->post('product_id');
            if (User::inWishlist($userId, $pid)) {
                User::removeWishlist($userId, $pid);
                json_response(['ok' => true, 'liked' => false]);
            }
            User::addWishlist($userId, $pid);
            json_response(['ok' => true, 'liked' => true]);
        }
        $page = max(1, (int)$this->get('page', 1));
        $wl = User::wishlist($userId, $page, 12);
        $this->render('account_wishlist', [
            'pageTitle' => 'Yêu thích - WoodCon',
            'items'     => $wl['rows'],
            'pager'     => $wl['pager'],
        ]);
    }

    /** Điểm thưởng & hạng thành viên (GIAI ĐOẠN 3.4) */
    private function points(): never
    {
        $userId = (int)current_user()['id'];
        $tier = User::membershipTier($userId);
        $page = max(1, (int)$this->get('page', 1));
        $hist = User::pointsHistory($userId, $page, 15);
        $this->render('account_points', [
            'pageTitle' => 'Điểm thưởng & hạng thành viên - WoodCon',
            'balance'   => (int)current_user()['points'],
            'tier'      => $tier,
            'history'   => $hist['rows'],
            'pager'     => $hist['pager'],
            'pointValue'=> (int)get_setting('point_value', 1000),
            'pointRate' => (int)get_setting('point_rate', 10000),
            'nextTier'  => User::nextMembershipTier($userId),
        ]);
    }

    private function changePassword(): never
    {
        $user = current_user();
        if ($this->isPost()) {
            if (!verify_csrf($this->post('_token'))) {
                set_flash('error', 'Phiên làm việc hết hạn.');
                redirect(BASE_URL . '/tai-khoan/doi-mat-khau');
            }
            $errors = validate_form($_POST, [
                'old_password' => ['required' => true],
                'password'     => ['required' => true, 'min' => 6],
            ]);
            if ($errors) {
                set_flash('error', reset($errors));
                redirect(BASE_URL . '/tai-khoan/doi-mat-khau');
            }
            $res = User::changePassword((int)$user['id'], $this->post('old_password'), $this->post('password'));
            set_flash($res['ok'] ? 'success' : 'error', $res['message']);
            redirect(BASE_URL . '/tai-khoan/doi-mat-khau');
        }
        $this->render('account_change_password', ['pageTitle' => 'Đổi mật khẩu - WoodCon']);
    }
}