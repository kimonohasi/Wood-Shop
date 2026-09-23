<?php
/**
 * WoodCon - Tra cứu bảo hành (không cần đăng nhập)
 * Nhánh C: kiểm tra tem/serial + số điện thoại nhận hàng -> phiếu bảo hành + lịch sử.
 */

declare(strict_types=1);

namespace WoodCon\Controllers;

use WoodCon\Order;

class WarrantyController extends BaseController
{
    public function index(): void
    {
        $item = null;
        $found = null;

        if ($this->isPost() || !empty($_GET['serial'])) {
            $serial = trim($this->post('serial', $this->get('serial', '')));
            if ($serial === '') {
                $found = 'Vui lòng nhập mã tem/serial bảo hành.';
            } else {
                $item = Order::warrantyBySerial($serial);
                if (!$item) {
                    $found = 'Không tìm thấy phiếu bảo hành với mã này.';
                } else {
                    $phone = trim((string)$this->post('phone', $this->get('phone', '')));
                    if ($phone !== '' && $phone !== $item['customer_phone']) {
                        $item = null;
                        $found = 'Số điện thoại không khớp với phiếu bảo hành.';
                    } else {
                        Order::syncWarrantyStatus((int)$item['id']);
                        $item = Order::warrantyBySerial($serial);
                        $item['history'] = Order::warrantyHistory((int)$item['id']);
                    }
                }
            }
        }

        $this->render('warranty', [
            'pageTitle' => 'Tra cứu bảo hành - WoodCon',
            'item'      => $item,
            'found'     => $found,
        ]);
    }
}