<?php
/**
 * WoodCon - Trang khuyến mãi (liệt kê voucher hiệu lực)
 */

declare(strict_types=1);

namespace WoodCon\Controllers;

use WoodCon\Voucher;

class VoucherController extends BaseController
{
    public function index(): void
    {
        $this->render('vouchers', [
            'pageTitle' => 'Khuyến mãi - WoodCon',
            'discounts' => Voucher::active('discount', 20),
            'freeships' => Voucher::active('freeship', 10),
        ]);
    }
}