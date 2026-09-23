<?php
/**
 * WoodCon - Tra cứu đơn hàng (không cần đăng nhập)
 */

declare(strict_types=1);

namespace WoodCon\Controllers;

use WoodCon\Order;

class TrackController extends BaseController
{
    public function index(): void
    {
        $order = null;
        $items = [];
        $found = null;

        if ($this->isPost() || !empty($_GET['code'])) {
            $code = trim($this->post('code', $this->get('code', '')));
            if ($code !== '') {
                $order = Order::byCode($code);
                if (!$order) {
                    $found = 'Không tìm thấy đơn hàng với mã này.';
                } elseif ($this->post('phone') && trim($this->post('phone')) !== $order['customer_phone']) {
                    $order = null;
                    $found = 'Số điện thoại không khớp với đơn hàng.';
                } else {
                    $items = Order::itemsOf((int)$order['id']);
                }
            }
        }

        $this->render('track', [
            'pageTitle' => 'Tra cứu đơn hàng - WoodCon',
            'order'     => $order,
            'items'     => $items,
            'found'     => $found,
        ]);
    }
}