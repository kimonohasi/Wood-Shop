<?php
/**
 * WoodCon - Danh mục sản phẩm
 */

declare(strict_types=1);

namespace WoodCon\Controllers;

use WoodCon\Brand;
use WoodCon\Category;
use WoodCon\Product;

class CategoryController extends BaseController
{
    public function show(string $slug): void
    {
        $category = Category::bySlug($slug);
        if (!$category) {
            http_response_code(404);
            require_once BASE_PATH . '/web/views/404.php';
            exit;
        }

        $f = [
            'q'          => $this->get('q', ''),
            'cat'        => (int)$category['id'],
            'brand'      => (int)$this->get('brand', 0),
            'min_price'  => (int)$this->get('min_price', 0),
            'max_price'  => (int)$this->get('max_price', 0),
            'sale_only'  => (int)$this->get('sale_only', 0),
            'sort'       => $this->get('sort', 'new'),
            'page'       => max(1, (int)$this->get('page', 1)),
            'per_page'   => 12,
        ];

        $result = Product::search($f);

        $this->render('category', [
            'pageTitle'  => $category['name'] . ' - WoodCon',
            'category'   => $category,
            'children'   => Category::getChildren((int)$category['id']),
            'parents'    => Category::getParents(),
            'siblings'   => Category::getChildren((int)$category['parent_id']),
            'brands'     => Brand::active(),
            'products'   => $result['rows'],
            'total'      => $result['total'],
            'pager'      => $result['pager'],
            'f'          => $f,
        ]);
    }
}