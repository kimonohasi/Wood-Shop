<?php
/**
 * WoodCon - Giỏ hàng (trang + AJAX)
 * Các action: add, update, remove, count, mini (trả HTML dòng giỏ)
 */

declare(strict_types=1);

namespace WoodCon\Controllers;

use WoodCon\Cart;

class CartController extends BaseController
{
    public function index(): void
    {
        // AJAX / cập nhật trực tiếp qua form
        if ($this->isPost()) {
            $this->ajax();
        }

        $this->render('cart', [
            'pageTitle' => 'Giỏ hàng - WoodCon',
            'items'     => Cart::items(),
        ]);
    }

    /** Xử lý action POST (gọi từ AJAX hoặc form) */
    public function ajax(string $action = ''): never
    {
        $action = $action !== '' ? $action : $this->post('action', '');
        $pid = (int)$this->post('product_id', 0);

        if (!$this->isPost() || !verify_csrf($this->post('_token', $this->post('csrf_token')))) {
            $this->json(['ok' => false, 'message' => 'Phiên làm việc hết hạn, vui lòng tải lại trang.'], 403);
        }

        try {
            switch ($action) {
                case 'add':
                    if (!$pid) {
                        $this->json(['ok' => false, 'message' => 'Sản phẩm không hợp lệ.']);
                    }
                    $variant = $this->post('variant_id') !== '' && $this->post('variant_id') !== null ? (int)$this->post('variant_id') : null;
                    $qty = max(1, (int)$this->post('quantity', 1));
                    if (!Cart::add($pid, $qty, $variant)) {
                        $this->json(['ok' => false, 'message' => 'Sản phẩm không còn bán.']);
                    }
                    $this->json(['ok' => true, 'message' => 'Đã thêm vào giỏ hàng.', 'count' => Cart::countItems(), 'gross' => Cart::gross()]);

                case 'update':
                    $cartId = (int)$this->post('cart_id', 0);
                    $qty = max(0, (int)$this->post('quantity', 1));
                    if (!$cartId || !Cart::updateQty($cartId, $qty)) {
                        $this->json(['ok' => false, 'message' => 'Không thể cập nhật số lượng sản phẩm.'], 404);
                    }
                    $this->json(['ok' => true, 'count' => Cart::countItems(), 'gross' => Cart::gross()]);

                case 'remove':
                    $cartId = (int)$this->post('cart_id', 0);
                    if (!$cartId || !Cart::remove($cartId)) {
                        $this->json(['ok' => false, 'message' => 'Không tìm thấy sản phẩm trong giỏ hàng.'], 404);
                    }
                    $this->json(['ok' => true, 'message' => 'Đã xóa sản phẩm khỏi giỏ hàng.', 'count' => Cart::countItems(), 'gross' => Cart::gross()]);

                case 'count':
                    $this->json(['ok' => true, 'count' => Cart::countItems()]);

                default:
                    $this->json(['ok' => false, 'message' => 'Hành động không hợp lệ.'], 400);
            }
        } catch (\Throwable $e) {
            write_log('error', 'Cart ajax [' . $action . ']: ' . $e->getMessage());
            $this->json(['ok' => false, 'message' => 'Lỗi hệ thống khi xử lý giỏ hàng.'], 500);
        }
    }
}
