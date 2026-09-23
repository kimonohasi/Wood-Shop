<?php
/**
 * WoodCon - Chi tiết sản phẩm
 */

declare(strict_types=1);

namespace WoodCon\Controllers;

use WoodCon\Product;
use WoodCon\User;

class ProductController extends BaseController
{
    public function show(string $slug): void
    {
        $product = Product::bySlug($slug);
        if (!$product) {
            http_response_code(404);
            require_once BASE_PATH . '/web/views/404.php';
            exit;
        }

        // Gửi đánh giá (POST từ trang sản phẩm)
        if ($this->isPost()) {
            $this->submitReview($product);
        }

        Product::bumpView((int)$product['id']);

        $dealLine = null;
        $lowest = Product::effectivePrice($product);
        foreach (Product::variants((int)$product['id']) as $v) {
            $lowest = min($lowest, (float)$lowest + (float)($v['price_adjust'] ?? 0));
        }
        $dealLine = $product['sale_price'] ?? null;

        $me = current_user();
        $canReview = false;
        $hasReviewed = false;
        if ($me !== null) {
            $canReview = User::canReview((int)$me['id'], (int)$product['id']);
            $hasReviewed = User::hasReviewed((int)$me['id'], (int)$product['id']);
        }

        $pageTitleValue = $product['meta_title'] ?: ($product['name'] . ' - WoodCon');
        $metaDescValue  = $product['meta_description'] ?: ($product['summary'] ?: null);

        $this->render('product', [
            'pageTitle'      => $pageTitleValue,
            'metaDescription'=> $metaDescValue,
            'product'   => $product,
            'images'    => Product::images((int)$product['id']),
            'variants'  => Product::variants((int)$product['id']),
            'videos'    => Product::videos((int)$product['id']),
            'related'   => Product::related((int)$product['category_id'], (int)$product['id'], 4),
            'lowestVariant' => $lowest,
            'dealLine'  => $dealLine,
            'reviews'   => Product::reviewsOf((int)$product['id']),
            'reviewStats' => Product::reviewStats((int)$product['id']),
            'canReview' => $canReview,
            'hasReviewed' => $hasReviewed,
        ]);
    }

    /** Xử lý form gửi đánh giá sản phẩm */
    private function submitReview(array $product): void
    {
        require_login();

        $back = BASE_URL . '/san-pham/' . $product['slug'];
        if (!verify_csrf($this->post('_token'))) {
            set_flash('error', 'Phiên làm việc hết hạn.');
            redirect($back);
        }
        $rating = (int)$this->post('rating', 0);
        if ($rating < 1 || $rating > 5) {
            set_flash('error', 'Vui lòng chọn số sao (1-5).');
            redirect($back);
        }
        $res = User::addReview((int)current_user()['id'], [
            'product_id' => (int)$product['id'],
            'rating'     => $rating,
            'title'      => $this->post('title', ''),
            'content'    => $this->post('content', ''),
        ]);
        set_flash($res['ok'] ? 'success' : 'error', $res['ok']
            ? 'Cảm ơn bạn! Đánh giá đang chờ kiểm duyệt.'
            : $res['message'] ?? 'Không thể gửi đánh giá.');
        redirect($back);
    }
}