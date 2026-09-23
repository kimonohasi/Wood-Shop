<?php
/**
 * WoodCon - Tìm kiếm sản phẩm
 *
 * index()      : render trang đầy đủ
 * ajaxResults(): trả về HTML partial (grid + active chips + total)
 *                dùng cho auto-submit filter không reload trang.
 */

declare(strict_types=1);

namespace WoodCon\Controllers;

use WoodCon\Brand;
use WoodCon\Category;
use WoodCon\Product;

class SearchController extends BaseController
{
    public function index(): void
    {
        $f = self::buildFilters($this);
        $result = Product::search($f);

        $this->render('search', [
            'pageTitle' => $f['q'] !== '' ? 'Tìm kiếm: "' . $f['q'] . '" - WoodCon' : 'Tất cả sản phẩm - WoodCon',
            'products'  => $result['rows'],
            'total'     => $result['total'],
            'pager'     => $result['pager'],
            'f'         => $f,
            'categories'=> Category::getTree(),
            'brands'    => Brand::active(),
            'priceMax'  => Product::priceRange()['max'],
        ]);
    }

    /** Endpoint AJAX — trả về HTML partial của kết quả. */
    public function ajaxResults(): void
    {
        $f = self::buildFilters($this);
        $result = Product::search($f);

        // Render partial trong layout tối thiểu (chỉ wrapper)
        $this->ajaxRender('partials/search_results', [
            'products'  => $result['rows'],
            'total'     => $result['total'],
            'pager'     => $result['pager'],
            'f'         => $f,
            'categories'=> Category::getTree(),
            'brands'    => Brand::active(),
        ]);
    }

    /**
     * Gom & validate input từ GET (BaseController không cần thiết ở đây, chỉ dùng
     * để truy cập helper ::get() qua $this).
     */
    private static function buildFilters(self $self): array
    {
        $sortAllowed = ['best', 'new', 'price_asc', 'price_desc'];
        $sort = (string)$self->get('sort', 'new');
        if (!in_array($sort, $sortAllowed, true)) {
            $sort = 'best';
        }
        // cat: hỗ trợ cả 'cat' (single hoặc 'cat[]' multi) + 'cat_ids[]' (multi)
        // Hợp nhất tất cả thành 1 mảng duy nhất
        $rawCats = [];
        $singleCat = $self->get('cat', null);
        if ($singleCat !== null && $singleCat !== '' && $singleCat !== '0') {
            $rawCats[] = (int)$singleCat;
        }
        $multiCat = $self->get('cat', []);
        if (is_array($multiCat)) {
            foreach ($multiCat as $__cid) {
                $__cid = (int)$__cid;
                if ($__cid > 0) $rawCats[] = $__cid;
            }
        }
        $catIdsParam = $self->get('cat_ids', []);
        if (is_array($catIdsParam)) {
            foreach ($catIdsParam as $__cid) {
                $__cid = (int)$__cid;
                if ($__cid > 0) $rawCats[] = $__cid;
            }
        } elseif (is_string($catIdsParam) && $catIdsParam !== '') {
            foreach (explode(',', $catIdsParam) as $__cid) {
                $__cid = (int)$__cid;
                if ($__cid > 0) $rawCats[] = $__cid;
            }
        }
        $catIds = array_values(array_unique($rawCats));
        // Thương hiệu — hỗ trợ nhiều (brand[]) hoặc 1 (brand)
        $rawBrands = [];
        $__brandParam = $self->get('brand', []);
        if (is_array($__brandParam)) {
            foreach ($__brandParam as $__bid) {
                $__bid = (int)$__bid;
                if ($__bid > 0) $rawBrands[] = $__bid;
            }
        } elseif ($__brandParam !== '' && $__brandParam !== '0') {
            $__bid = (int)$__brandParam;
            if ($__bid > 0) $rawBrands[] = $__bid;
        }
        $brandIds = array_values(array_unique($rawBrands));
        // Lấy giá trị thô từ query; nếu không có/không hợp lệ → null (bỏ lọc)
        $__minP = $self->get('min_price', null);
        $__maxP = $self->get('max_price', null);
        $__minPrice = ($__minP !== null && $__minP !== '' && (int)$__minP > 0) ? (int)$__minP : null;
        $__maxPrice = ($__maxP !== null && $__maxP !== '' && (int)$__maxP > 0) ? (int)$__maxP : null;

        return [
            'q'             => mb_substr(trim((string)$self->get('q', '')), 0, 100, 'UTF-8'),
            'cat'           => $catIds,    // luôn là mảng
            'cat_ids'       => $catIds,    // giữ alias để tương thích
            'brand'         => $brandIds ? $brandIds[0] : 0,  // giữ tương thích 1 giá trị
            'brand_ids'     => $brandIds,                      // đa thương hiệu
            'min_price'     => $__minPrice,
            'max_price'     => $__maxPrice,
            'sale_only'     => $self->get('sale') === '1' ? 1 : 0,
            'is_new'        => $self->get('new') === '1' ? 1 : 0,
            'is_best_seller'=> $self->get('best') === '1' ? 1 : 0,
            'sort'          => $sort,
            'page'          => max(1, (int)$self->get('page', 1)),
            'per_page'      => 12,
        ];
    }

    /** Render 1 view không layout (dùng cho AJAX). */
    private function ajaxRender(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        header('Content-Type: text/html; charset=UTF-8');
        header('X-WoodCon-Ajax: 1');
        require BASE_PATH . '/web/views/' . $view . '.php';
    }

    /** Endpoint AJAX — gợi ý sản phẩm nhanh cho ô tìm kiếm (JSON). */
    public function ajaxSuggest(): void
    {
        $q = (string)$this->get('q', '');
        $rows = Product::suggest($q, 8);

        // Thêm url + giá hiển thị để client không phải tự tính
        $out = array_map(static function ($r) {
            $price = (int)($r['sale_price'] ?? $r['price'] ?? 0);
            $orig  = (int)($r['price'] ?? 0);
            return [
                'id'        => (int)$r['id'],
                'name'      => $r['name'],
                'slug'      => $r['slug'],
                'url'       => BASE_URL . '/san-pham/' . $r['slug'],
                'price'     => (float)$price,
                'orig'      => (float)$orig,
                'price_txt' => format_money($price),
                'orig_txt'  => format_money($orig),
                'image'     => image_url($r['image'] ?? null),
            ];
        }, $rows);

        header('Content-Type: application/json; charset=UTF-8');
        header('X-WoodCon-Ajax: 1');
        echo json_encode(['items' => $out]);
    }
}