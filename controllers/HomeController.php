<?php
/**
 * WoodCon - Trang chủ
 */

declare(strict_types=1);

namespace WoodCon\Controllers;

use WoodCon\Banner;
use WoodCon\Category;
use WoodCon\News;
use WoodCon\Product;

class HomeController extends BaseController
{
    public function index(): void
    {
        $data = [
            'pageTitle'   => 'WoodCon - Nội thất gỗ cao cấp',
            'sliders'     => Banner::byPosition('slider'),
            'banners'     => Banner::byPosition('banner'),
            'flashSale'   => Banner::byPosition('flash_sale'),
            'featured'    => Product::featured(8),
            'bestSellers' => Product::bestSellers(8),
            'newest'      => Product::newest(8),
            'categories'  => Category::getTree(),
            'latestNews'  => News::latest(3),
        ];
        $this->render('home', $data);
    }
}