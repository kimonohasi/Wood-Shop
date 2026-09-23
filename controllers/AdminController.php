<?php
/**
 * WoodCon - Controller khu vực quản trị (/quan-tri)
 * Đảm bảo mọi trang yêu cầu đăng nhập admin. Giai đoạn 5: chức năng trước, giao diện sau.
 */

declare(strict_types=1);

namespace WoodCon\Controllers;

use WoodCon\Admin;
use WoodCon\AuditLogger;
use WoodCon\Banner;
use WoodCon\Brand;
use WoodCon\Category;
use WoodCon\Contact;
use WoodCon\Invoice;
use WoodCon\InvoiceSetting;
use WoodCon\News;
use WoodCon\Order;
use WoodCon\Permission;
use WoodCon\Product;
use WoodCon\Staff;
use WoodCon\TaxRate;
use WoodCon\User;
use WoodCon\Voucher;
use PDO;

class AdminController extends BaseController
{
    /** Điều phối theo segment sau /quan-tri */
    public function dispatch(array $seg): void
    {
        $action = $seg[0] ?? '';

        // Đăng nhập / đăng xuất không cần phiên
        if ($action === 'dang-nhap') {
            $this->login();
            return;
        }
        if ($action === 'dang-xuat') {
            Admin::logout();
            redirect(BASE_URL . '/quan-tri/dang-nhap');
        }

        // Mọi trang khác phải đăng nhập admin
        if (!Admin::current()) {
            redirect(BASE_URL . '/quan-tri/dang-nhap');
        }

        switch ($action) {
            case '':            $this->dashboard(); break;
            case 'doanh-thu-ajax': $this->dashboardRevenueAjax(); break;
            case 'don-hang-theo-trang-thai-ajax': $this->ordersByStatusAjax(); break;
            case 'tim-kiem-nhanh': $this->quickSearch(); break;
            case 'san-pham':    $this->products($seg[1] ?? '', $seg[2] ?? ''); break;
            case 'danh-muc':    $this->categories($seg[1] ?? '', $seg[2] ?? ''); break;
            case 'don-hang':    $this->orders($seg[1] ?? '', $seg[2] ?? ''); break;
            case 'hoa-don':     $this->invoices($seg[1] ?? '', $seg[2] ?? ''); break;
            case 'duyet':       $this->approvals($seg[1] ?? '', $seg[2] ?? ''); break;
            case 'danh-gia':    $this->reviews($seg[1] ?? '', $seg[2] ?? ''); break;
            case 'voucher':     $this->vouchers($seg[1] ?? '', $seg[2] ?? ''); break;
            case 'khach-hang':  $this->users($seg[1] ?? '', $seg[2] ?? ''); break;
            case 'banner':      $this->banners($seg[1] ?? '', $seg[2] ?? ''); break;
            case 'tin-tuc':     $this->news($seg[1] ?? '', $seg[2] ?? ''); break;
            case 'thuong-hieu': $this->brands($seg[1] ?? '', $seg[2] ?? ''); break;
            case 'bao-cao':     $this->reports($seg[1] ?? ''); break;
            case 'thue':        $this->taxes($seg[1] ?? '', $seg[2] ?? ''); break;
            case 'bao-hanh':    $this->warranties($seg[1] ?? '', $seg[2] ?? ''); break;
            case 'lien-he':     $this->contacts($seg[1] ?? ''); break;
            case 'cai-dat':     $this->settings(); break;
            case 'van-chuyen':  $this->shippingAdmin(); break;
            case 'nhan-su':     $this->staffs($seg[1] ?? ''); break;
            case 'phan-quyen':  $this->roles($seg[1] ?? ''); break;
            case 'nhat-ky':     $this->auditLogs($seg[1] ?? ''); break;
            default:
                http_response_code(404);
                $this->adminRender('404', ['pageTitle' => 'Không tìm thấy']);
        }
    }

    // ==================== AUTH ====================

    public function login(): void
    {
        if (Admin::current()) {
            redirect(BASE_URL . '/quan-tri');
        }
        if ($this->isPost()) {
            if (!verify_csrf($this->post('_token'))) {
                set_flash('error', 'Phiên hết hạn, thử lại.');
            } elseif (!verify_recaptcha((string)$this->post('g-recaptcha-response'))) {
                // GIỮ NGUYÊN - LOGIC ĐĂNG NHẬP: thêm xác thực reCAPTCHA
                // (cơ chế giống hệt trang đăng nhập người dùng).
                set_flash('error', 'Vui lòng xác nhận bạn không phải người máy.');
            } else {
                $email = trim((string)$this->post('email', ''));
                $rlKey = 'admin_login:' . md5(($_SERVER['REMOTE_ADDR'] ?? '') . '|' . $email);
                if (rate_limit_blocked($rlKey, 6, 600)) {
                    set_flash('error', 'Bạn đã thử đăng nhập quá nhiều lần. Vui lòng thử lại sau ít phút.');
                } else {
                    rate_limit_hit($rlKey, 6, 600);
                    $admin = Admin::attempt($email, $this->post('password', ''));
                    if (!$admin) {
                        set_flash('error', 'Email hoặc mật khẩu không đúng.');
                    } else {
                        rate_limit_clear($rlKey);
                        // Regenerate session ID sau đăng nhập (chống session fixation)
                        session_regenerate_id(true);
                        write_log('admin', 'Đăng nhập admin ' . $admin['email'], (int)$admin['id']);
                        \WoodCon\AuditLogger::log(
                            \WoodCon\AuditLogger::A_LOGIN,
                            'Auth',
                            'Đăng nhập hệ thống quản trị.',
                            (int)$admin['id']
                        );
                        redirect(BASE_URL . '/quan-tri');
                    }
                }
            }
        }
        $this->adminLoginRender();
    }

    // ==================== DASHBOARD ====================

    /** Trang đăng nhập admin - giao diện độc lập, không dùng layout chung */
    private function adminLoginRender(): never
    {
        $pageTitle = 'Đăng nhập quản trị - WoodCon';
        $error = get_flash('error');
        require BASE_PATH . '/admin/views/login.php';
        exit;
    }

    private function dashboard(): void
    {
        Permission::require('dashboard', 'view');
        $this->adminRender('dashboard', [
            'pageTitle' => 'Dashboard - WoodCon Admin',
            'data'      => Admin::dashboard(),
        ]);
    }

    /**
     * AJAX: dữ liệu doanh thu (đơn giao thành công, theo delivered_at) cho biểu đồ.
     * period = day|week|month|year. Trả về labels + tổng doanh thu + dataset.
     * Công thức doanh thu dùng REVENUESERVICE (nguồn duy nhất); chỉ KHUNG bucket ở đây.
     */
    private function dashboardRevenueAjax(): never
    {
        Permission::require('dashboard', 'view');
        $period = (string)$this->get('period', 'day');
        $counts = \WoodCon\RevenueService::BUCKET_COUNTS;
        $bucketCount = $counts[$period] ?? $counts['day'];

        // Biểu đồ xu hướng = chuỗi bucket liên tục (RevenueService - nguồn duy nhất).
        $buckets = \WoodCon\RevenueService::buckets(
            'delivered', $period, \WoodCon\RevenueService::COL_DELIVERED, $bucketCount
        );
        // Con số lớn = ĐÚNG MỘT kỳ hiện tại (hôm nay/tuần này/tháng này/năm nay),
        // KHÁC với tổng cửa sổ biểu đồ bên dưới.
        $current = \WoodCon\RevenueService::currentPeriodTotal(
            'delivered', $period, \WoodCon\RevenueService::COL_DELIVERED
        );

        $this->json([
            'labels'      => $buckets['labels'],
            'values'      => $buckets['values'],
            'total'       => array_sum($buckets['values']),
            'periodTotal' => $current['total'],
            'periodLabel' => $current['label'],
        ]);
    }

    /** AJAX — danh sách đơn hàng theo trạng thái cho popup tra cứu nhanh trên Dashboard
     *  (khách gọi hỏi đơn → mở nhanh đúng trạng thái, gõ mã đơn/tên/SĐT lọc realtime). */
    private function ordersByStatusAjax(): never
    {
        Permission::require('orders', 'view');
        $status = (string)$this->get('status', 'pending');
        if (!isset(Order::STATUS_LABEL[$status])) {
            $this->json(['ok' => false, 'message' => 'Trạng thái không hợp lệ']);
        }

        // Thời gian hiển thị theo trạng thái: delivered → delivered_at; còn lại → updated_at
        // (thời điểm chuyển trạng thái gần nhất), fallback created_at.
        $stmt = Admin::db()->prepare(
            "SELECT o.order_code, o.customer_name, o.customer_phone, o.total_amount,
                    o.delivered_at, o.updated_at, o.created_at,
                    (SELECT t.cover_image FROM order_items t WHERE t.order_id = o.id ORDER BY t.id ASC LIMIT 1) AS first_image,
                    (SELECT COUNT(*) FROM order_items t WHERE t.order_id = o.id) AS item_count,
                    CASE WHEN o.order_status = 'delivered' THEN o.delivered_at ELSE o.updated_at END AS ts
             FROM orders o
             WHERE o.order_status = ?
             ORDER BY ts DESC"
        );
        $stmt->execute([$status]);
        $items = $stmt->fetchAll();

        $orders = [];
        foreach ($items as $o) {
            $time = $o['ts'] ?: $o['created_at'];
            $orders[] = [
                'order_code'     => (string)$o['order_code'],
                'customer_name'  => (string)$o['customer_name'],
                'customer_phone' => (string)$o['customer_phone'],
                'total_amount'   => format_money((float)$o['total_amount']),
                'time'           => format_date($time),
                'first_image'    => image_url((string)$o['first_image']),
                'item_count'     => (int)$o['item_count'],
                'plus'           => max((int)$o['item_count'] - 1, 0),
            ];
        }

        $this->json([
            'ok'         => true,
            'statusName' => Order::STATUS_LABEL[$status],
            'count'      => count($orders),
            'orders'     => $orders,
        ]);
    }

    /** AJAX — tìm kiếm nhanh đa loại (Ctrl+K) */
    private function quickSearch(): never
    {
        $q = mb_substr(trim((string)$this->get('q', '')), 0, 80, 'UTF-8');
        $results = [];
        if ($q === '') {
            $this->json(['ok' => true, 'results' => $results]);
        }

        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';

        // Sản phẩm
        if (Permission::allows('products', 'view')) {
            $rows = Admin::query("SELECT id, name, slug, price, sale_price, cover_image AS image
                FROM products WHERE status = 1 AND (name LIKE ? OR sku LIKE ?)
                ORDER BY is_best_seller DESC, sold_count DESC LIMIT 6", [$like, $like]);
            foreach ($rows as $r) {
                $results[] = [
                    'type' => 'product', 'label' => 'Sản phẩm',
                    'title' => (string)$r['name'],
                    'subtitle' => format_money((int)($r['sale_price'] ?: $r['price'])),
                    'image' => image_url((string)$r['image']),
                    'url' => BASE_URL . '/quan-tri/san-pham',
                ];
            }
        }

        // Đơn hàng
        if (Permission::allows('orders', 'view')) {
            $rows = Admin::query("SELECT id, order_code, customer_name, customer_phone, total_amount, order_status,
                (SELECT cover_image FROM order_items WHERE order_id = orders.id ORDER BY id ASC LIMIT 1) AS first_image
                FROM orders WHERE order_code LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?
                ORDER BY created_at DESC LIMIT 6", [$like, $like, $like]);
            foreach ($rows as $o) {
                $results[] = [
                    'type' => 'order', 'label' => 'Đơn hàng',
                    'title' => '#' . (string)$o['order_code'],
                    'subtitle' => (string)$o['customer_name'] . ' · ' . format_money((float)$o['total_amount']),
                    'image' => image_url((string)$o['first_image']),
                    'url' => BASE_URL . '/quan-tri/don-hang',
                ];
            }
        }

        // Voucher
        if (Permission::allows('vouchers', 'view')) {
            $rows = Admin::query("SELECT id, code, name, discount_value, discount_type
                FROM vouchers WHERE code LIKE ? OR name LIKE ? ORDER BY id DESC LIMIT 6", [$like, $like]);
            foreach ($rows as $v) {
                $discount = $v['discount_type'] === 'percent'
                    ? (int)$v['discount_value'] . '%'
                    : format_money((int)$v['discount_value']);
                $results[] = [
                    'type' => 'voucher', 'label' => 'Voucher',
                    'title' => (string)$v['code'],
                    'subtitle' => (string)$v['name'] . ' · Giảm ' . $discount,
                    'image' => null,
                    'url' => BASE_URL . '/quan-tri/voucher',
                ];
            }
        }

        // Khách hàng
        if (Permission::allows('customers', 'view')) {
            $rows = Admin::query("SELECT id, name, email, phone, avatar
                FROM users WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?
                ORDER BY id DESC LIMIT 6", [$like, $like, $like]);
            foreach ($rows as $u) {
                $results[] = [
                    'type' => 'user', 'label' => 'Khách hàng',
                    'title' => (string)$u['name'],
                    'subtitle' => (string)($u['email'] ?: $u['phone']),
                    'image' => image_url((string)$u['avatar']),
                    'url' => BASE_URL . '/quan-tri/khach-hang',
                ];
            }
        }

        // Nhân sự
        if (Permission::allows('staffs', 'view')) {
            $rows = Admin::query("SELECT id, name, email FROM admins
                WHERE is_deleted = 0 AND (name LIKE ? OR email LIKE ?)
                ORDER BY id DESC LIMIT 6", [$like, $like]);
            foreach ($rows as $s) {
                $results[] = [
                    'type' => 'staff', 'label' => 'Nhân sự',
                    'title' => (string)$s['name'],
                    'subtitle' => (string)$s['email'],
                    'image' => null,
                    'url' => BASE_URL . '/quan-tri/nhan-su',
                ];
            }
        }

        $this->json(['ok' => true, 'results' => $results]);
    }

    // ==================== SẢN PHẨM ====================

    private function products(string $mode, string $id): void
    {
        Permission::require('products', 'view');
        // Xuất danh sách sản phẩm (CSV / Excel)
        if ($mode === 'xuat') {
            Permission::require('products', 'export');
            $this->productExport();
        }
        // save (POST tạo/sửa)
        if ($mode === 'luu' && $this->isPost()) {
            $this->productSave((int)$this->post('id', 0));
        }
        // Xóa
        if ($mode === 'xoa' && $id !== '') {
            Permission::require('products', 'delete');
            $this->confirmThen('xóa sản phẩm');
            $p = Product::find((int)$id);
            Product::delete((int)$id);
            AuditLogger::log(AuditLogger::A_DELETE, 'Products', 'Xóa sản phẩm "' . ($p['name'] ?? '#' . $id) . '" (ID ' . $id . ')', admin_id());
            write_log('product', 'Xóa sản phẩm #' . $id, admin_id());
            set_flash('success', 'Đã xóa sản phẩm.');
            redirect(BASE_URL . '/quan-tri/san-pham');
        }
        // Chuyển trạng thái (active/inactive)
        if ($mode === 'trang-thai' && $id !== '') {
            Permission::require('products', 'edit');
            $p = Product::find((int)$id);
            if ($p) {
                $newStatus = $p['status'] ? 0 : 1;
                Product::update((int)$id, ['status' => $newStatus]);
                AuditLogger::log(AuditLogger::A_UPDATE, 'Products', 'Đổi trạng thái sản phẩm "' . $p['name'] . '" từ ' . ($p['status'] ? 'Hoạt động' : 'Ẩn') . ' thành ' . ($newStatus ? 'Hoạt động' : 'Ẩn'), admin_id());
            }
            redirect(BASE_URL . '/quan-tri/san-pham');
        }

        // Form sửa (có id) hoặc tạo mới
        if (in_array($mode, ['tao', 'sua'], true)) {
            $product = $id !== '' ? Product::find((int)$id) : null;
            if ($mode === 'sua' && !$product) {
                set_flash('error', 'Không tìm thấy sản phẩm.');
                redirect(BASE_URL . '/quan-tri/san-pham');
            }
            $this->adminRender('product_form', [
                'pageTitle'  => ($product ? 'Sửa' : 'Thêm') . ' sản phẩm - WoodCon Admin',
                'product'    => $product,
                'categories' => Category::getTree(),
                'brands'     => Brand::active(),
                'taxRates'   => \WoodCon\TaxRate::where('status = 1', [], '*', 'rate ASC'),
                'images'     => $product ? Product::images((int)$product['id']) : [],
                'variants'   => $product ? Product::variants((int)$product['id']) : [],
            ]);
            return;
        }

        // Danh sách
        $q = trim($this->get('q', ''));
        $cat = (int)$this->get('cat', 0);
        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(p.name LIKE ? OR p.sku LIKE ?)';
            array_push($params, "%$q%", "%$q%");
        }
        if ($cat) {
            // Lọc theo danh mục cha => gồm cả sản phẩm của các danh mục con
            $catIds = [$cat];
            foreach (Category::getChildren($cat) as $__child) {
                $catIds[] = (int)$__child['id'];
            }
            $where[] = 'p.category_id IN (' . implode(',', array_fill(0, count($catIds), '?')) . ')';
            $params   = array_merge($params, $catIds);
        }
        $whereStr = implode(' AND ', $where);

        $stmt = Admin::db()->prepare("SELECT COUNT(*) AS c FROM products p WHERE {$whereStr}");
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['c'];

        $perPage = 15;
        $pager = paginate($total, $perPage, max(1, (int)$this->get('page', 1)));

        $sql = "SELECT p.*, c.name AS category_name, b.name AS brand_name
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN brands b ON b.id = p.brand_id
                WHERE {$whereStr} ORDER BY p.id DESC LIMIT {$perPage} OFFSET {$pager['offset']}";
        $stmt = Admin::db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $this->adminRender('products', [
            'pageTitle'  => 'Sản phẩm - WoodCon Admin',
            'rows'       => $rows,
            'total'      => $total,
            'pager'      => $pager,
            'categories' => Category::getTree(),
            'q'          => $q,
            'cat'        => $cat,
            'stats'      => Admin::productStats(),
        ]);
    }

    /** Xuất danh sách sản phẩm theo bộ lọc hiện tại (giá/tồn kho) */
    private function productExport(): never
    {
        $where = ['1=1'];
        $params = [];
        $q = trim($this->get('q', ''));
        $cat = (int)$this->get('cat', 0);
        if ($q !== '') {
            $where[] = '(p.name LIKE ? OR p.sku LIKE ?)';
            array_push($params, "%$q%", "%$q%");
        }
        if ($cat) {
            $catIds = [$cat];
            foreach (Category::getChildren($cat) as $__child) {
                $catIds[] = (int)$__child['id'];
            }
            $where[] = 'p.category_id IN (' . implode(',', array_fill(0, count($catIds), '?')) . ')';
            $params   = array_merge($params, $catIds);
        }
        $whereStr = implode(' AND ', $where);
        $stmt = Admin::db()->prepare(
            "SELECT p.id, p.name, p.sku, COALESCE(c.name,'') AS category_name, p.price, p.sale_price,
                    p.cost, p.quantity, p.sold_count, p.status, p.is_featured
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE {$whereStr} ORDER BY p.id"
        );
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        write_log('product', 'Xuất danh sách sản phẩm', admin_id());
        AuditLogger::log(AuditLogger::A_EXPORT, 'Products', 'Xuất danh sách sản phẩm (' . count($items) . ' sản phẩm)', admin_id());

        $rows = [];
        foreach ($items as $p) {
            $rows[] = [
                (int)$p['id'],
                $p['name'],
                $p['sku'],
                $p['category_name'],
                (float)$p['price'],
                $p['sale_price'] ? (float)$p['sale_price'] : '',
                $p['cost'] ? (float)$p['cost'] : '',
                (int)$p['quantity'],
                (int)$p['sold_count'],
                $p['status'] ? 'Hoạt động' : 'Ẩn',
                $p['is_featured'] ? 'Có' : 'Không',
            ];
        }

        export_file($this->get('export', 'csv'), 'san-pham-' . date('Y-m-d'), [
            'ID', 'Tên sản phẩm', 'SKU', 'Danh mục', 'Giá niêm yết', 'Giá khuyến mãi', 'Giá vốn',
            'Tồn kho', 'Đã bán', 'Trạng thái', 'Nổi bật',
        ], $rows);
    }

    private function productSave(int $id): void
    {
        Permission::require('products', $id ? 'edit' : 'add');
        if (!verify_csrf($this->post('_token'))) {
            set_flash('error', 'Phiên hết hạn.');
            redirect(BASE_URL . '/quan-tri/san-pham');
        }
        $errors = validate_form($_POST, [
            'name'       => ['required' => true, 'max' => 255],
            'sku'        => ['required' => true, 'max' => 60],
            'category_id'=> ['required' => true],
            'price'      => ['required' => true],
        ]);
        if ($errors) {
            set_flash('error', reset($errors));
            redirect(BASE_URL . '/quan-tri/san-pham/' . ($id ? 'sua/' . $id : 'tao'));
        }

        // --- Biến thể: đọc + validate (trước khi lưu, tránh nửa chừng) ---
        $vIds    = array_map('intval', (array)$this->post('variant_id', []));
        $vNames  = array_map('trim', (array)$this->post('variant_name', []));
        $vVals   = array_map('trim', (array)$this->post('variant_value', []));
        $vPrices = array_map('floatval', (array)$this->post('variant_price_adjust', []));
        $vStocks = array_map('intval', (array)$this->post('variant_stock', []));
        $vSkus   = array_map('trim', (array)$this->post('variant_sku', []));
        $vFiles  = $_FILES['variant_image'] ?? null;
        $vCount  = count($vNames);
        if ($vCount >= 2) {
            for ($i = 0; $i < $vCount; $i++) {
                $hasFile = $vFiles && !empty($vFiles['name'][$i]) && (int)$vFiles['error'][$i] === UPLOAD_ERR_OK;
                if (!$hasFile && $vIds[$i] <= 0) {
                    set_flash('error', 'Mỗi biến thể cần có ảnh riêng khi có từ 2 biến thể trở lên.');
                    redirect(BASE_URL . '/quan-tri/san-pham/' . ($id ? 'sua/' . $id : 'tao'));
                }
            }
        }

        $categoryId = (int)$this->post('category_id');
        $price = (float)$this->post('price');
        $salePrice = $this->post('sale_price') !== '' ? (float)$this->post('sale_price') : null;

        $taxRateId = (int)$this->post('tax_rate_id', 0);

        // Slug: dùng thủ công nếu nhập, ngược lại tự sinh từ tên; đảm bảo duy nhất
        $slugIn = trim((string)$this->post('slug', ''));
        $baseSlug = $slugIn !== '' ? slugify($slugIn) : slugify($this->post('name'));
        $slug = unique_slug((int)$id, $baseSlug);

        // Meta SEO: để trống thì mặc định theo Tên sản phẩm / Mô tả ngắn
        $metaTitle = trim((string)$this->post('meta_title', ''));
        $metaTitle = $metaTitle !== '' ? $metaTitle : trim($this->post('name'));
        $metaDescription = trim((string)$this->post('meta_description', ''));
        $metaDescription = $metaDescription !== '' ? $metaDescription : (trim($this->post('summary', '')) ?: null);

        // Nghị định 81/2018: giảm giá không quá 50% giá niêm yết (cảnh báo khi KM >50%)
        $data = [
            'category_id'   => $categoryId,
            'brand_id'      => (int)$this->post('brand_id') ?: null,
            'tax_rate_id'   => $taxRateId ?: null,
            'name'          => trim($this->post('name')),
            'slug'          => $slug,
            'sku'           => trim($this->post('sku')),
            'summary'       => trim($this->post('summary', '')) ?: null,
            'description'   => trim($this->post('description', '')) ?: null,
            'material'      => trim($this->post('material', '')) ?: null,
            'dimension'     => trim($this->post('dimension', '')) ?: null,
            'warranty_months'=> (int)$this->post('warranty_months', 12),
            'meta_title'    => mb_substr($metaTitle, 0, 255, 'UTF-8'),
            'meta_description' => mb_substr($metaDescription ?? '', 0, 500, 'UTF-8') ?: null,
            'price'         => $price,
            'sale_price'    => $salePrice,
            'cost'          => (float)$this->post('cost', 0),
            'quantity'      => (int)$this->post('quantity', 0),
            'weight_kg'     => (float)$this->post('weight_kg', 1),
            'dim_l'         => $this->post('dim_l') !== '' ? (float)$this->post('dim_l') : null,
            'dim_w'         => $this->post('dim_w') !== '' ? (float)$this->post('dim_w') : null,
            'dim_h'         => $this->post('dim_h') !== '' ? (float)$this->post('dim_h') : null,
            'status'        => (int)(bool)$this->post('status', 0),
            'is_featured'   => (int)(bool)$this->post('is_featured', 0),
            'is_new'        => (int)(bool)$this->post('is_new', 0),
            'is_best_seller'=> (int)(bool)$this->post('is_best_seller', 0),
            'install_fee'        => $this->post('install_fee', '') !== '' ? (float)$this->post('install_fee') : 0,
            'ship_supports_type1'=> $this->post('ship_supports_type1', '') === '' ? null : (int)$this->post('ship_supports_type1'),
            'ship_supports_type2'=> $this->post('ship_supports_type2', '') === '' ? null : (int)$this->post('ship_supports_type2'),
        ];

        // Nghiệp vụ chống bùng: cảnh báo giảm giá >50% (NĐ 81/2018)
        if ($salePrice !== null && $price > 0 && $salePrice / $price < 0.5) {
            set_flash('warning', 'Giảm giá vượt quá 50% giá niêm yết — vui lòng kiểm tra theo NĐ 81/2018.');
        }

        // Upload ảnh cover
        if (!empty($_FILES['cover_image']['name'])) {
            $img = upload_image($_FILES['cover_image'], 'products');
            if ($img) {
                $data['cover_image'] = $img;
            }
        }

        if ($id) {
            Product::update($id, $data);
            $productId = $id;
            AuditLogger::log(AuditLogger::A_UPDATE, 'Products', 'Cập nhật sản phẩm "' . $data['name'] . '" (ID ' . $id . ')', admin_id());
            write_log('product', 'Cập nhật sản phẩm #' . $id, admin_id());
        } else {
            $productId = Product::insert($data);
            AuditLogger::log(AuditLogger::A_CREATE, 'Products', 'Tạo sản phẩm "' . $data['name'] . '" (ID ' . $productId . ')', admin_id());
            write_log('product', 'Thêm sản phẩm ' . $data['name'], admin_id());
        }

        // --- Ảnh phụ (gallery): xóa ảnh được đánh dấu, rồi thêm ảnh mới tải lên ---
        $toDelete = array_filter(array_map('intval', (array)$this->post('delete_image', [])));
        if ($toDelete) {
            foreach ($toDelete as $__imgId) {
                Product::removeImage($__imgId);
            }
        }
        if (!empty($_FILES['gallery_images']['name'])) {
            $galleryFiles = $_FILES['gallery_images'];
            $count = is_array($galleryFiles['name']) ? count($galleryFiles['name']) : 0;
            for ($__i = 0; $__i < $count; $__i++) {
                $one = [
                    'name'     => $galleryFiles['name'][$__i],
                    'type'     => $galleryFiles['type'][$__i],
                    'tmp_name' => $galleryFiles['tmp_name'][$__i],
                    'error'    => $galleryFiles['error'][$__i],
                    'size'     => $galleryFiles['size'][$__i],
                ];
                $img = upload_image($one, 'products');
                if ($img) {
                    Product::addImage((int)$productId, $img);
                }
            }
        }

        // --- Biến thể: lưu (thêm / sửa / xóa) ---
        $keepIds = [];
        for ($i = 0; $i < $vCount; $i++) {
            $rowId = $vIds[$i];
            $img = null;
            if ($vFiles && !empty($vFiles['name'][$i]) && (int)$vFiles['error'][$i] === UPLOAD_ERR_OK) {
                $one = [
                    'name'     => $vFiles['name'][$i],
                    'type'     => $vFiles['type'][$i],
                    'tmp_name' => $vFiles['tmp_name'][$i],
                    'error'    => $vFiles['error'][$i],
                    'size'     => $vFiles['size'][$i],
                ];
                $img = upload_image($one, 'variants');
            }
            $row = [
                'product_id'   => $productId,
                'name'         => $vNames[$i] ?: 'Màu sắc',
                'value'        => $vVals[$i],
                'price_adjust' => $vPrices[$i],
                'stock'        => $vStocks[$i],
                'sku'          => $vSkus[$i] ?: null,
            ];
            if ($img) {
                $row['image_url'] = $img;
            }
            if ($rowId > 0) {
                Product::updateVariant($rowId, $row);
                $keepIds[] = $rowId;
            } else {
                $keepIds[] = Product::addVariant($row);
            }
        }
        Product::deleteVariants((int)$productId, $keepIds);

        set_flash('success', 'Đã lưu sản phẩm.');
        redirect(BASE_URL . '/quan-tri/san-pham/sua/' . $productId);
    }

    // ==================== DANH MỤC ====================

    private function categories(string $mode, string $id): void
    {
        Permission::require('categories', 'view');
        if ($mode === 'luu' && $this->isPost()) {
            Permission::require('categories', $this->post('id') ? 'edit' : 'add');
            if (!verify_csrf($this->post('_token'))) {
                set_flash('error', 'Phiên hết hạn.');
            } else {
                $catId = (int)$this->post('id', 0);
                $d = [
                    'name'      => trim($this->post('name')),
                    'slug'      => slugify($this->post('name')),
                    'parent_id' => (int)$this->post('parent_id', 0) ?: null,
                    'status'    => (int)(bool)$this->post('status', 0),
                    'sort_order'=> (int)$this->post('sort_order', 0),
                    'image'     => trim($this->post('image', '')) ?: null,
                    'install_fee'        => $this->post('install_fee', '') !== '' ? (float)$this->post('install_fee') : 0,
                    'ship_supports_type1'=> $this->post('ship_supports_type1', '') === '' ? 1 : (int)$this->post('ship_supports_type1'),
                    'ship_supports_type2'=> $this->post('ship_supports_type2', '') === '' ? 1 : (int)$this->post('ship_supports_type2'),
                ];
                if (!empty($_FILES['image_upload']['name'])) {
                    $img = upload_image($_FILES['image_upload'], 'categories');
                    if ($img) {
                        $d['image'] = $img;
                    }
                }
                if ($catId) {
                    // chống vòng lặp cha=con
                    if ((int)$d['parent_id'] === $catId) {
                        $d['parent_id'] = 0;
                    }
                    Category::update($catId, $d);
                    AuditLogger::log(AuditLogger::A_UPDATE, 'Categories', 'Cập nhật danh mục "' . $d['name'] . '" (ID ' . $catId . ')', admin_id());
                } else {
                    $catId = Category::insert($d);
                    AuditLogger::log(AuditLogger::A_CREATE, 'Categories', 'Tạo danh mục "' . $d['name'] . '" (ID ' . $catId . ')', admin_id());
                }
                set_flash('success', 'Đã lưu danh mục.');
            }
            redirect(BASE_URL . '/quan-tri/danh-muc');
        }
        if ($mode === 'xoa' && $id !== '') {
            Permission::require('categories', 'delete');
            $cat = Category::find((int)$id);
            Category::delete((int)$id);
            AuditLogger::log(AuditLogger::A_DELETE, 'Categories', 'Xóa danh mục "' . ($cat['name'] ?? '#' . $id) . '" (ID ' . $id . ')', admin_id());
            set_flash('success', 'Đã xóa danh mục.');
            redirect(BASE_URL . '/quan-tri/danh-muc');
        }

        $edit = $mode === 'sua' && $id !== '' ? Category::find((int)$id) : null;
        if ($mode === 'sua' && $id !== '' && !$edit) {
            redirect(BASE_URL . '/quan-tri/danh-muc');
        }

        // Sản phẩm đang hoạt động, nhóm theo danh mục để hiển thị trong accordion
        $productsByCat = [];
        foreach (Admin::db()->query("SELECT id, name, category_id, price, sale_price, status FROM products WHERE status = 1 ORDER BY name ASC") as $__p) {
            $productsByCat[(int)$__p['category_id']][] = $__p;
        }

        $this->adminRender('categories', [
            'pageTitle'    => 'Danh mục - WoodCon Admin',
            'tree'         => Category::getTree(),
            'parents'      => Category::where('parent_id IS NULL', [], '*', 'sort_order'),
            'productsByCat'=> $productsByCat,
            'edit'         => $edit,
            'catStats'     => Admin::categoryStats(),
        ]);
    }

    // ==================== ĐƠN HÀNG ====================

    private function orders(string $mode, string $id): void
    {
        Permission::require('orders', 'view');
        // Xuất danh sách đơn hàng (CSV / Excel)
        if ($mode === 'xuat') {
            Permission::require('orders', 'export');
            $this->orderExport();
        }
        // Hard Delete đơn hàng đã HỦY (chỉ Chủ hệ thống, phải nhập lại mật khẩu)
        if ($mode === 'xoa-huy' && $id !== '' && $this->isPost()) {
            Permission::requireOwner(); // vùng cấm
            if (!verify_csrf($this->post('_token'))) {
                json_response(['ok' => false, 'message' => 'Phiên hết hạn.'], 400);
            }
            $order = Order::byCode($id);
            if (!$order) {
                json_response(['ok' => false, 'message' => 'Không tìm thấy đơn.'], 404);
            }
            $pass = (string)$this->post('super_password', '');
            $acting = Admin::current();
            if (!$acting || !password_verify($pass, $acting['password'])) {
                json_response(['ok' => false, 'message' => 'Mật khẩu Chủ hệ thống không đúng.'], 403);
            }
            $res = Staff::hardDeleteCancelledOrder((int)$order['id'], (int)$acting['id']);
            json_response(['ok' => $res['ok'], 'message' => $res['message']], $res['ok'] ? 200 : 400);
        }

        // Chuyển trạng thái
        if ($mode === 'trang-thai' && $id !== '' && $this->isPost()) {
            Permission::require('orders', 'edit');
            $order = Order::byCode($id);
            $res = ['ok' => false, 'message' => 'Không tìm thấy đơn.'];
            if ($order) {
                $res = Order::transition(
                    $order,
                    $this->post('order_status', ''),
                    trim($this->post('note', ''))
                );
                Order::update((int)$order['id'], [
                    'manual_verify_note' => trim((string)$this->post('manual_verify_note')) ?: null,
                ]);
            }
            set_flash($res['ok'] ? 'success' : 'error', $res['message']);
            redirect(BASE_URL . '/quan-tri/don-hang/xem/' . $id);
        }

        if ($mode === 'xem' && $id !== '') {
            $order = Order::byCode($id);
            if (!$order) {
                set_flash('error', 'Không tìm thấy đơn.');
                redirect(BASE_URL . '/quan-tri/don-hang');
            }
            $this->adminRender('order_detail', [
                'pageTitle' => 'Đơn ' . $order['order_code'] . ' - WoodCon Admin',
                'order'     => $order,
                'items'     => Order::itemsOf((int)$order['id']),
                'transitions' => [
                    'pending'          => ['manual_verifying', 'confirmed', 'cancelled'],
                    'manual_verifying' => ['confirmed', 'cancelled'],
                    'confirmed'        => ['preparing', 'cancelled'],
                    'preparing'        => ['shipping', 'cancelled'],
                    'shipping'         => ['delivery_failed', 'delivered', 'returned'],
                    'delivery_failed'  => ['shipping', 'returned', 'delivered'],
                    'delivered'        => [],
                    'returned'         => [],
                    'cancelled'        => [],
                ][$order['order_status']] ?? [],
            ]);
            return;
        }

        // In hóa đơn (POS 80mm / A5-A4)
        if ($mode === 'hoa-don' && $id !== '') {
            $order = Order::byCode($id);
            if (!$order) {
                set_flash('error', 'Không tìm thấy đơn.');
                redirect(BASE_URL . '/quan-tri/don-hang');
            }
            $type = $this->get('type', 'a4') === 'pos' ? 'pos' : 'a4';
            $pageTitle   = 'Hóa đơn ' . $order['order_code'] . ' - WoodCon Admin';
            $order       = Order::find((int)$order['id']);
            $items       = Order::itemsOf((int)$order['id']);
            $invoiceCfg  = InvoiceSetting::all();
            $invoiceNumber = InvoiceSetting::ensureNumberForOrder($order);
            $invoiceDate = date('d/m/Y H:i');
            require BASE_PATH . '/admin/views/invoice.php';
            exit;
        }

        // Danh sách
        $f = [
            'q'             => trim($this->get('q', '')),
            'status'        => trim($this->get('status', '')),
            'payment'       => trim($this->get('payment', '')),
            'payment_status'=> trim($this->get('payment_status', '')),
            'page'          => max(1, (int)$this->get('page', 1)),
        ];
        $data = Admin::ordersFiltered($f);
        $this->adminRender('orders', [
            'pageTitle' => 'Đơn hàng - WoodCon Admin',
            'rows'      => $data['rows'],
            'total'     => $data['total'],
            'pager'     => $data['pager'],
            'f'         => $f,
            'orderStats'=> Admin::orderStats(),
        ]);
    }

    // ==================== HÓA ĐƠN (LEGAL INVOICES) ====================
    // Mục "Hóa đơn" là chứng từ kế toán TÁCH RIÊNG với "Quản lý đơn hàng".
    // Mọi bản ghi TẠI ĐÂY KHÔNG ĐƯỢC XÓA (immutable) — không có route delete.

    private function invoices(string $mode, string $id): void
    {
        Permission::require('invoices', 'view');
        // Xuất danh sách hóa đơn theo bộ lọc hiện tại (CSV / Excel)
        if ($mode === 'xuat') {
            Permission::require('invoices', 'export');
            $this->invoiceExport();
        }
        // Xem chi tiết / in chứng từ hóa đơn
        if ($mode === 'xem' && $id !== '') {
            $inv = Invoice::byCode($id);
            if (!$inv) {
                set_flash('error', 'Không tìm thấy hóa đơn.');
                redirect(BASE_URL . '/quan-tri/hoa-don');
            }
            $this->adminRender('invoice_detail', [
                'pageTitle' => 'Hóa đơn ' . $inv['invoice_number'] . ' - WoodCon Admin',
                'invoice'   => $inv,
                'items'     => Invoice::itemsOfInvoice((int)$inv['id']),
                'order'     => Order::find((int)$inv['order_id']) ?: [],
                'original'  => !empty($inv['original_invoice_id'])
                    ? Invoice::find((int)$inv['original_invoice_id'])
                    : null,
            ]);
            return;
        }

        // In chứng từ hóa đơn (A4 / POS)
        if ($mode === 'in' && $id !== '') {
            $inv = Invoice::byCode($id);
            if (!$inv) {
                set_flash('error', 'Không tìm thấy hóa đơn.');
                redirect(BASE_URL . '/quan-tri/hoa-don');
            }
            $type = $this->get('type', 'a4') === 'pos' ? 'pos' : 'a4';
            $invoice      = $inv;
            $items        = Invoice::itemsOfInvoice((int)$inv['id']);
            $invoiceCfg   = InvoiceSetting::all();
            $invoiceType  = $inv['type'];
            $order        = Order::find((int)$inv['order_id']) ?: [];
            require BASE_PATH . '/admin/views/invoice.php';
            exit;
        }

        // Danh sách hóa đơn
        $f = [
            'q'    => trim($this->get('q', '')),
            'type' => trim($this->get('type', '')),
            'from' => trim($this->get('from', '')),
            'to'   => trim($this->get('to', '')),
            'page' => max(1, (int)$this->get('page', 1)),
        ];
        $data = Invoice::listInvoices($f);
        $this->adminRender('invoices', [
            'pageTitle' => 'Hóa đơn - WoodCon Admin',
            'rows'      => $data['rows'],
            'total'     => $data['total'],
            'pager'     => $data['pager'],
            'f'         => $f,
            'stats'     => Admin::invoiceStats(),
        ]);
    }

    /** Xuất danh sách hóa đơn theo bộ lọc hiện tại (CSV / Excel) */
    private function invoiceExport(): never
    {
        $where = ['1=1'];
        $params = [];
        $q = trim($this->get('q', ''));
        if ($q !== '') {
            $where[] = '(i.invoice_number LIKE ? OR i.invoice_code LIKE ? OR i.customer_name LIKE ? OR i.customer_phone LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }
        $type = trim($this->get('type', ''));
        if ($type !== '') {
            $where[] = 'i.type = ?';
            $params[] = $type;
        }
        $from = trim($this->get('from', ''));
        $to = trim($this->get('to', ''));
        if ($from !== '' && $to !== '') {
            $where[] = 'i.invoice_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY) - INTERVAL 1 SECOND';
            array_push($params, $from . ' 00:00:00', $to);
        }
        $whereStr = implode(' AND ', $where);
        $stmt = Admin::db()->prepare(
            "SELECT i.*, o.order_code
             FROM invoices i
             LEFT JOIN orders o ON o.id = i.order_id
             WHERE {$whereStr} ORDER BY i.invoice_date DESC, i.id DESC"
        );
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        write_log('invoice', 'Xuất danh sách hóa đơn', admin_id());
        AuditLogger::log(AuditLogger::A_EXPORT, 'Invoices', 'Xuất danh sách hóa đơn (' . count($items) . ' hóa đơn, bộ lọc q=' . ($q ?: '—') . ' type=' . ($type ?: '—') . ')');

        $typeLabel = \WoodCon\Invoice::TYPE_LABEL;
        $rows = [];
        foreach ($items as $iv) {
            $rows[] = [
                $iv['invoice_number'],
                $typeLabel[$iv['type']] ?? $iv['type'],
                $iv['invoice_date'],
                $iv['order_code'] ?? '',
                $iv['customer_name'] ?? '',
                $iv['customer_phone'] ?? '',
                (float)$iv['subtotal'],
                $iv['discount'] ? (float)$iv['discount'] : 0,
                $iv['shipping_fee'] ? (float)$iv['shipping_fee'] : 0,
                (float)$iv['taxable'],
                (float)$iv['vat'],
                (float)$iv['grand_total'],
            ];
        }

        export_file($this->get('export', 'csv'), 'hoa-don-' . date('Y-m-d'), [
            'Số hóa đơn', 'Loại', 'Ngày HĐ', 'Mã đơn', 'Khách hàng', 'SĐT',
            'Tạm tính', 'Chiết khấu', 'Phí ship', 'Chịu thuế', 'VAT', 'Tổng cộng',
        ], $rows);
    }

    /** Xuất danh sách đơn hàng theo bộ lọc hiện tại (kèm VAT + COD trust) */
    private function orderExport(): never
    {
        $where = ['1=1'];
        $params = [];
        $q = trim($this->get('q', ''));
        if ($q !== '') {
            $where[] = '(order_code LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        $status = trim($this->get('status', ''));
        if ($status !== '') {
            $where[] = 'order_status = ?';
            $params[] = $status;
        }
        $payment = trim($this->get('payment', ''));
        if ($payment !== '') {
            $where[] = 'payment_method = ?';
            $params[] = $payment;
        }
        $paymentStatus = trim($this->get('payment_status', ''));
        if ($paymentStatus !== '') {
            $where[] = 'payment_status = ?';
            $params[] = $paymentStatus;
        }
        $from = trim($this->get('from', ''));
        $to = trim($this->get('to', ''));
        if ($from !== '' && $to !== '') {
            $where[] = 'created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY) - INTERVAL 1 SECOND';
            array_push($params, $from . ' 00:00:00', $to);
        }
        $whereStr = implode(' AND ', $where);
        $stmt = Admin::db()->prepare("SELECT * FROM orders WHERE {$whereStr} ORDER BY created_at DESC");
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        write_log('order', 'Xuất danh sách đơn hàng', admin_id());
        AuditLogger::log(
            AuditLogger::A_EXPORT,
            'Orders',
            'Xuất danh sách đơn hàng (' . count($items) . ' đơn, bộ lọc q=' . ($q ?: '—') . ' status=' . ($status ?: '—') . ')'
        );

        $statusLabel = Order::STATUS_LABEL;
        $paymentLabel = ['cod' => 'COD', 'bank' => 'Bank chuyển khoản', 'qr' => 'QR', 'wallet' => 'Ví'];
        $trustLabel = ['green' => 'Xanh', 'yellow' => 'Vàng', 'red' => 'Đỏ'];

        $rows = [];
        foreach ($items as $o) {
            $rows[] = [
                $o['order_code'],
                $o['customer_name'],
                $o['customer_phone'],
                $o['customer_email'] ?? '',
                $paymentLabel[$o['payment_method']] ?? strtoupper((string)$o['payment_method']),
                $o['payment_status'] === 'paid' ? 'Đã thanh toán' : ($o['payment_status'] === 'refunded' ? 'Đã hoàn tiền' : 'Chưa thanh toán'),
                $statusLabel[$o['order_status']] ?? $o['order_status'],
                (float)$o['subtotal'],
                (float)$o['discount_amount'],
                (float)$o['shipping_fee'],
                (float)$o['vat_amount'],
                (float)$o['total_amount'],
                $trustLabel[$o['trust_level_at_order'] ?? ''] ?? '',
                $o['created_at'],
            ];
        }

        export_file($this->get('export', 'csv'), 'don-hang-' . date('Y-m-d'), [
            'Mã đơn', 'Khách hàng', 'SĐT', 'Email', 'Phương thức', 'Thanh toán', 'Trạng thái',
            'Tiền hàng', 'Giảm giá', 'Phí ship', 'VAT', 'Tổng tiền', 'COD Trust', 'Ngày đặt',
        ], $rows);
    }

    // ==================== KIỂM DUYỆT HỦY / HOÀN TIỀN ====================

    private function approvals(string $mode, string $id): void
    {
        Permission::require('orders', 'edit');
        // Xuất yêu cầu hủy / hoàn tiền chờ xử lý (CSV / Excel)
        if ($mode === 'xuat') {
            Permission::require('approvals', 'export');
            $this->approvalsExport();
        }
        // Duyệt / từ chối yêu cầu hủy đơn từ khách
        if ($mode === 'huy' && $id !== '' && $this->isPost()) {
            if (!verify_csrf($this->post('_token'))) {
                set_flash('error', 'Phiên hết hạn.');
            } else {
                $decision = $this->post('decision', 'reject') === 'approve' ? 'approve' : 'reject';
                $res = Order::handleCancelRequest((int)$id, $decision, trim($this->post('note', '')));
                AuditLogger::log($decision === 'approve' ? AuditLogger::A_UPDATE : AuditLogger::A_UPDATE, 'Approvals', 'Xử lý yêu cầu hủy #' . $id . ' -> ' . $decision, admin_id());
                write_log('order', 'Xử lý yêu cầu hủy #' . $id . ' -> ' . $decision, admin_id());
                set_flash($res['ok'] ? 'success' : 'error', $res['message']);
                if ($res['ok'] && $decision === 'approve') {
                    set_flash('success', 'Đã duyệt: ' . $res['message']);
                }
            }
            redirect(BASE_URL . '/quan-tri/duyet');
        }
        // Hoàn tất hoàn tiền
        if ($mode === 'hoan-tien' && $id !== '' && $this->isPost()) {
            if (!verify_csrf($this->post('_token'))) {
                set_flash('error', 'Phiên hết hạn.');
            } else {
                $ok = Order::completeRefund((int)$id, admin_id());
                if ($ok) {
                    AuditLogger::log(AuditLogger::A_UPDATE, 'Approvals', 'Hoàn tất hoàn tiền yêu cầu #' . $id, admin_id());
                }
                set_flash($ok ? 'success' : 'error', $ok ? 'Đã hoàn tất hoàn tiền, đơn đánh dấu đã hoàn.' : 'Không tìm thấy yêu cầu hoàn tiền.');
            }
            redirect(BASE_URL . '/quan-tri/duyet');
        }

        $q = trim((string)$this->get('q', ''));
        $like = '%' . $q . '%';

        $cancelWhere = "r.status = 'pending'";
        $cancelParams = [];
        if ($q !== '') {
            $cancelWhere .= " AND (o.order_code LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ? OR r.reason LIKE ?)";
            array_push($cancelParams, $like, $like, $like, $like);
        }
        $stmt = Admin::db()->prepare(
            "SELECT r.id, r.reason, r.admin_note, r.created_at, o.order_code, o.customer_name, o.customer_phone,
                    o.total_amount, o.payment_method, o.payment_status
             FROM order_cancel_requests r
             JOIN orders o ON o.id = r.order_id
             WHERE {$cancelWhere}
             ORDER BY r.id DESC"
        );
        $stmt->execute($cancelParams);
        $cancels = $stmt->fetchAll();

        $refundWhere = "rf.status = 'pending'";
        $refundParams = [];
        if ($q !== '') {
            $refundWhere .= " AND (o.order_code LIKE ? OR o.customer_name LIKE ? OR rf.reason LIKE ?)";
            array_push($refundParams, $like, $like, $like);
        }
        $stmt = Admin::db()->prepare(
            "SELECT rf.id, rf.amount, rf.method, rf.reason, rf.created_at, o.order_code, o.customer_name, o.payment_method
             FROM refunds rf
             JOIN orders o ON o.id = rf.order_id
             WHERE {$refundWhere}
             ORDER BY rf.id DESC"
        );
        $stmt->execute($refundParams);
        $refunds = $stmt->fetchAll();

        // Thống kê tổng quan (2 bảng tách biệt, không gộp chung)
        $cancelStats = Admin::db()->query(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(status = 'pending'),  0) AS pending,
                COALESCE(SUM(status = 'approved'), 0) AS approved,
                COALESCE(SUM(status = 'rejected'), 0) AS rejected
             FROM order_cancel_requests"
        )->fetch();
        $refundStats = Admin::db()->query(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(status = 'pending'),   0) AS pending,
                COALESCE(SUM(status = 'completed'), 0) AS completed,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) AS pending_amount
             FROM refunds"
        )->fetch();
        $stats = [
            'cancels' => [
                'total'    => (int)($cancelStats['total'] ?? 0),
                'pending'  => (int)($cancelStats['pending'] ?? 0),
                'approved' => (int)($cancelStats['approved'] ?? 0),
                'rejected' => (int)($cancelStats['rejected'] ?? 0),
            ],
            'refunds' => [
                'total'          => (int)($refundStats['total'] ?? 0),
                'pending'        => (int)($refundStats['pending'] ?? 0),
                'completed'      => (int)($refundStats['completed'] ?? 0),
                'pending_amount' => (float)($refundStats['pending_amount'] ?? 0),
            ],
        ];

        $pgCancels = $this->paginateRows($cancels, 15);
        $pgRefunds = $this->paginateRows($refunds, 15);

        $this->adminRender('approvals', [
            'pageTitle'    => 'Kiểm duyệt hủy / hoàn tiền - WoodCon Admin',
            'cancels'      => $pgCancels['rows'],
            'refunds'      => $pgRefunds['rows'],
            'cancelsPager' => $pgCancels['pager'],
            'refundsPager' => $pgRefunds['pager'],
            'stats'        => $stats,
            'q'            => $q,
        ]);
    }

    /** Xuất yêu cầu hủy đơn + hoàn tiền đang chờ xử lý (CSV / Excel) */
    private function approvalsExport(): never
    {
        $methodLabel = ['cod' => 'COD', 'bank' => 'Bank chuyển khoản', 'qr' => 'QR', 'wallet' => 'Ví'];

        $q = trim((string)$this->get('q', ''));
        $like = '%' . $q . '%';

        $cancelWhere = "r.status = 'pending'";
        $cancelParams = [];
        if ($q !== '') {
            $cancelWhere .= " AND (o.order_code LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ? OR r.reason LIKE ?)";
            array_push($cancelParams, $like, $like, $like, $like);
        }
        $stmt = Admin::db()->prepare(
            "SELECT r.reason, r.created_at, o.order_code, o.customer_name, o.customer_phone,
                    o.total_amount, o.payment_method
             FROM order_cancel_requests r
             JOIN orders o ON o.id = r.order_id
             WHERE {$cancelWhere} ORDER BY r.id DESC"
        );
        $stmt->execute($cancelParams);
        $cancels = $stmt->fetchAll();

        $refundWhere = "rf.status = 'pending'";
        $refundParams = [];
        if ($q !== '') {
            $refundWhere .= " AND (o.order_code LIKE ? OR o.customer_name LIKE ? OR rf.reason LIKE ?)";
            array_push($refundParams, $like, $like, $like);
        }
        $stmt = Admin::db()->prepare(
            "SELECT rf.amount, rf.method, rf.reason, rf.created_at, o.order_code, o.customer_name, o.customer_phone
             FROM refunds rf
             JOIN orders o ON o.id = rf.order_id
             WHERE {$refundWhere} ORDER BY rf.id DESC"
        );
        $stmt->execute($refundParams);
        $refunds = $stmt->fetchAll();

        write_log('order', 'Xuất yêu cầu hủy/hoàn tiền chờ xử lý', admin_id());
        AuditLogger::log(AuditLogger::A_EXPORT, 'Approvals', 'Xuất yêu cầu hủy/hoàn tiền (' . (count($cancels) + count($refunds)) . ' yêu cầu chờ xử lý)');

        $rows = [];
        foreach ($cancels as $c) {
            $rows[] = [
                $c['order_code'],
                $c['customer_name'],
                $c['customer_phone'],
                'Hủy đơn',
                (float)$c['total_amount'],
                $methodLabel[$c['payment_method']] ?? strtoupper((string)$c['payment_method']),
                $c['reason'],
                $c['created_at'],
            ];
        }
        foreach ($refunds as $rf) {
            $rows[] = [
                $rf['order_code'],
                $rf['customer_name'],
                $rf['customer_phone'],
                'Hoàn tiền',
                (float)$rf['amount'],
                $methodLabel[$rf['method']] ?? strtoupper((string)$rf['method']),
                $rf['reason'],
                $rf['created_at'],
            ];
        }

        export_file($this->get('export', 'csv'), 'kiem-duyet-huy-hoan-' . date('Y-m-d'), [
            'Mã đơn', 'Khách hàng', 'SĐT', 'Loại yêu cầu', 'Số tiền', 'Phương thức', 'Lý do', 'Ngày yêu cầu',
        ], $rows);
    }

    // ==================== KIỂM DUYỆT ĐÁNH GIÁ SẢN PHẨM ====================

    private function reviews(string $mode, string $id): void
    {
        Permission::require('reviews', 'view');
        $allowed = ['approved', 'hidden', 'pending'];
        $status = in_array($mode, $allowed, true) ? $mode : 'pending';

        if ($id !== '') {
            Permission::require('reviews', 'edit');
            if (!$this->isPost() || !verify_csrf($this->post('_token'))) {
                set_flash('error', 'Phiên hết hạn.');
            } else {
                $res = Admin::db()->prepare('UPDATE reviews SET status = ? WHERE id = ?');
                $res->execute([$status, (int)$id]);
                $label = $status === 'approved' ? 'đã duyệt' : ($status === 'hidden' ? 'đã ẩn' : 'đã khôi phục');
                AuditLogger::log(AuditLogger::A_UPDATE, 'Reviews', 'Đánh giá #' . $id . ' -> ' . $label, admin_id());
                write_log('review', 'Cập nhật đánh giá #' . $id . ' -> ' . $label, admin_id());
                set_flash('success', 'Đánh giá #' . $id . ' ' . $label . '.');
            }
            redirect(BASE_URL . '/quan-tri/danh-gia');
        }

        $f = [
            'q'      => trim((string)$this->get('q', '')),
            'rating' => (int)$this->get('rating', 0),
            'page'   => max(1, (int)$this->get('page', 1)),
        ];
        $where = ['r.status = ?'];
        $params = [$status];
        if ($f['q'] !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR p.name LIKE ?)';
            $like = '%' . $f['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        if ($f['rating'] >= 1 && $f['rating'] <= 5) {
            $where[] = 'r.rating = ?';
            $params[] = $f['rating'];
        }
        $whereStr = implode(' AND ', $where);

        $reviews = Admin::db()->prepare(
            'SELECT r.id, r.rating, r.title, r.content, r.status, r.created_at,
                    u.name AS user_name, u.email, p.name AS product_name, p.slug
             FROM reviews r
             JOIN users u ON u.id = r.user_id
             JOIN products p ON p.id = r.product_id
             WHERE ' . $whereStr . '
             ORDER BY r.id DESC'
        );
        $reviews->execute($params);
        $rows = $reviews->fetchAll();

        $counts = Admin::db()->query(
            'SELECT status, COUNT(*) AS total FROM reviews GROUP BY status'
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        $pg = $this->paginateRows($rows);
        $pager = $pg['pager'];
        $rows = $pg['rows'];

        $this->adminRender('reviews', [
            'pageTitle' => 'Kiểm duyệt đánh giá - WoodCon Admin',
            'rows'      => $rows,
            'pager'     => $pager,
            'current'   => $status,
            'counts'    => $counts,
            'f'         => $f,
        ]);
    }

    // ==================== VOUCHER ====================

    private function vouchers(string $mode, string $id): void
    {
        Permission::require('vouchers', 'view');
        if ($mode === 'luu' && $this->isPost()) {
            Permission::require('vouchers', $this->post('id') ? 'edit' : 'add');
            if (!verify_csrf($this->post('_token'))) {
                set_flash('error', 'Phiên hết hạn.');
            } else {
                $vid = (int)$this->post('id', 0);
                $type = $this->post('type', 'discount');
                $rawEnd = trim((string)$this->post('end_date'));
                $d = [
                    'code'             => strtoupper(trim($this->post('code'))),
                    'name'             => trim($this->post('name')),
                    'description'      => trim($this->post('description', '')) ?: null,
                    'type'             => $type,
                    'discount_type'    => $this->post('discount_type') === 'fixed' ? 'fixed' : 'percent',
                    'discount_value'   => (float)$this->post('discount_value', 0),
                    'max_discount'     => $this->post('max_discount') !== '' ? (float)$this->post('max_discount') : null,
                    'min_order_value'  => (float)$this->post('min_order_value', 0),
                    'total_quantity'   => (int)$this->post('total_quantity', 0),
                    'per_user_limit'   => (int)$this->post('per_user_limit', 1),
                    'start_date'       => $this->post('start_date') ?: date('Y-m-d'),
                    'end_date'         => $rawEnd === '' ? date('Y-m-d', strtotime('+1 year')) . ' 23:59:59' : (strlen($rawEnd) <= 10 ? $rawEnd . ' 23:59:59' : $rawEnd),
                    'status'           => (int)(bool)$this->post('status', 0),
                    'scope_type'       => 'all',
                    'required_tier_id' => (int)$this->post('required_tier_id', 0) > 0 ? (int)$this->post('required_tier_id') : null,
                ];
                if ($vid) {
                    Voucher::update($vid, $d);
                    AuditLogger::log(AuditLogger::A_UPDATE, 'Vouchers', 'Cập nhật mã khuyến mãi "' . $d['code'] . '" (ID ' . $vid . ')', admin_id());
                } else {
                    $vid = Voucher::insert($d);
                    AuditLogger::log(AuditLogger::A_CREATE, 'Vouchers', 'Tạo mã khuyến mãi "' . $d['code'] . '" (ID ' . $vid . ')', admin_id());
                }
                set_flash('success', 'Đã lưu mã khuyến mãi.');
            }
            redirect(BASE_URL . '/quan-tri/voucher');
        }
        if ($mode === 'xoa' && $id !== '') {
            Permission::require('vouchers', 'delete');
            $v = Voucher::find((int)$id);
            Voucher::delete((int)$id);
            AuditLogger::log(AuditLogger::A_DELETE, 'Vouchers', 'Xóa mã khuyến mãi "' . ($v['code'] ?? '#' . $id) . '"', admin_id());
            set_flash('success', 'Đã xóa mã.');
            redirect(BASE_URL . '/quan-tri/voucher');
        }

        $edit = $mode === 'sua' && $id !== '' ? Voucher::find((int)$id) : null;
        $q = trim((string)$this->get('q', ''));
        $vouchers = Admin::db()->query("SELECT * FROM vouchers ORDER BY start_date DESC, id DESC")->fetchAll();
        $now = date('Y-m-d H:i:s');
        $grouped = [
            'discount' => [],
            'freeship' => [],
        ];
        $typeCount = ['discount' => 0, 'freeship' => 0];
        $statusCount = ['active' => 0, 'expired' => 0, 'out' => 0, 'turned_off' => 0];
        $currentMonth = null;
        // Thống kê toàn cục không đổi, chỉ danh sách bị lọc khi có từ khóa
        foreach ($vouchers as $__v) {
            $__tmpType = $__v['type'] === 'freeship' ? 'freeship' : 'discount';
            $typeCount[$__tmpType]++;
            $__tmpSt = 'turned_off';
            if ((int)$__v['status'] === 1) {
                if ($__v['end_date'] < $now) {
                    $__tmpSt = 'expired';
                } elseif ((int)$__v['total_quantity'] > 0 && (int)$__v['used_quantity'] >= (int)$__v['total_quantity']) {
                    $__tmpSt = 'out';
                } else {
                    $__tmpSt = 'active';
                }
            }
            $statusCount[$__tmpSt]++;
        }
        if ($q !== '') {
            $vouchers = array_values(array_filter($vouchers, fn($__v) => stripos((string)$__v['code'], $q) !== false || stripos((string)$__v['name'], $q) !== false));
        }
        foreach ($vouchers as $__v) {
            $type = $__v['type'] === 'freeship' ? 'freeship' : 'discount';
            // Trạng thái thời gian thực
            $st = 'turned_off';
            if ((int)$__v['status'] === 1) {
                if ($__v['end_date'] < $now) {
                    $st = 'expired';
                } elseif ((int)$__v['total_quantity'] > 0 && (int)$__v['used_quantity'] >= (int)$__v['total_quantity']) {
                    $st = 'out';
                } else {
                    $st = 'active';
                }
            }
            $month = ($__v['start_date'] ? date('Y-m', strtotime($__v['start_date'])) : 'now') . '|' . ($__v['start_date'] ? date('m/Y', strtotime($__v['start_date'])) : 'Khác');
            [$ym, $label] = explode('|', $month);
            $row = $__v;
            $row['_status'] = $st;
            $grouped[$type][$ym][] = [$row, $label];
        }
        // Sắp tháng mới nhất trước
        foreach ($grouped as $type => &$months) {
            krsort($months);
            foreach ($months as &$list) {
                usort($list, fn($a, $b) => (int)$b[0]['id'] <=> (int)$a[0]['id']);
            }
        }
        unset($months, $list);
        $this->adminRender('vouchers', [
            'pageTitle'   => 'Khuyến mãi - WoodCon Admin',
            'grouped'     => $grouped,
            'edit'        => $edit,
            'typeCount'   => $typeCount,
            'statusCount' => $statusCount,
            'tiers'       => Admin::db()->query('SELECT id, name, min_total_spent FROM membership_tiers WHERE status = 1 ORDER BY min_total_spent ASC')->fetchAll(),
            'q'           => $q,
        ]);
    }

    // ==================== KHÁCH HÀNG ====================

    private function users(string $mode, string $id): void
    {
        Permission::require('customers', 'view');
        // Xuất danh sách khách hàng (CSV / Excel)
        if ($mode === 'xuat') {
            Permission::require('customers', 'export');
            $this->userExport();
        }
        if ($mode === 'xem' && $id !== '') {
            $user = User::find((int)$id);
            if (!$user) {
                redirect(BASE_URL . '/quan-tri/khach-hang');
            }
            $orders = Order::forUser((int)$id);
            $this->adminRender('user_detail', [
                'pageTitle' => 'Khách hàng - WoodCon Admin',
                'user'      => $user,
                'orders'    => $orders,
            ]);
            return;
        }
        if ($mode === 'khoa' && $id !== '' && $this->isPost()) {
            Permission::require('customers', 'edit');
            $u = User::find((int)$id);
            if ($u) {
                $newStatus = $u['status'] ? 0 : 1;
                User::update((int)$id, ['status' => $newStatus]);
                $action = $newStatus ? AuditLogger::A_LOCK : AuditLogger::A_UNLOCK;
                $desc = ($newStatus ? 'Khóa' : 'Mở khóa') . ' tài khoản khách hàng ' . $u['name'] . ' (ID ' . $id . ')';
                AuditLogger::log($action, 'Customers', $desc, admin_id());
                json_response(['ok' => true, 'message' => 'Đã ' . ($newStatus ? 'khóa' : 'mở khóa') . ' tài khoản.', 'locked' => !$newStatus]);
            }
            json_response(['ok' => false, 'message' => 'Không tìm thấy khách hàng.'], 404);
        }

        $f = [
            'q'      => trim($this->get('q', '')),
            'status' => trim((string)$this->get('status', '')),
            'page'   => max(1, (int)$this->get('page', 1)),
        ];
        $data = Admin::usersFiltered($f);
        $this->adminRender('users', [
            'pageTitle' => 'Khách hàng - WoodCon Admin',
            'rows'      => $data['rows'],
            'total'     => $data['total'],
            'pager'     => $data['pager'],
            'f'         => $f,
        ]);
    }

    /** Xuất danh sách khách hàng theo bộ lọc hiện tại */
    private function userExport(): never
    {
        $where = ['1=1'];
        $params = [];
        $q = trim($this->get('q', ''));
        if ($q !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        $fStatus = trim((string)$this->get('status', ''));
        if ($fStatus === '1' || $fStatus === '0') {
            $where[] = 'u.status = ?';
            $params[] = (int)$fStatus;
        }
        $whereStr = implode(' AND ', $where);
        $stmt = Admin::db()->prepare(
            "SELECT u.id, u.name, u.email, u.phone, u.points, u.trust_level, u.status, u.created_at,
                    COALESCE(mt.name,'') AS tier_name,
                    (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
                    (SELECT COALESCE(SUM(o.total_amount),0) FROM orders o
                     WHERE o.user_id = u.id AND o.order_status='delivered') AS total_spent
             FROM users u
             LEFT JOIN membership_tiers mt ON mt.id = u.membership_tier_id
             WHERE {$whereStr} ORDER BY u.created_at DESC"
        );
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        write_log('user', 'Xuất danh sách khách hàng', admin_id());
        AuditLogger::log(AuditLogger::A_EXPORT, 'Customers', 'Xuất danh sách khách hàng (' . count($items) . ' khách hàng)', admin_id());

        $trustLabel = ['green' => 'Xanh', 'yellow' => 'Vàng', 'red' => 'Đỏ'];
        $rows = [];
        foreach ($items as $u) {
            $rows[] = [
                (int)$u['id'],
                $u['name'],
                $u['email'],
                $u['phone'],
                (int)$u['points'],
                $u['tier_name'],
                $trustLabel[$u['trust_level']] ?? $u['trust_level'],
                (int)$u['order_count'],
                (float)$u['total_spent'],
                $u['status'] ? 'Hoạt động' : 'Khóa',
                $u['created_at'],
            ];
        }

        export_file($this->get('export', 'csv'), 'khach-hang-' . date('Y-m-d'), [
            'ID', 'Tên khách hàng', 'Email', 'SĐT', 'Điểm tích lũy', 'Hạng thành viên',
            'COD Trust', 'Số đơn', 'Tổng chi tiêu (đã giao)', 'Trạng thái', 'Ngày tham gia',
        ], $rows);
    }

    // ==================== BANNER ====================

    private function banners(string $mode, string $id): void
    {
        Permission::require('banners', 'view');
        if ($mode === 'luu' && $this->isPost()) {
            Permission::require('banners', $this->post('id') ? 'edit' : 'add');
            if (!verify_csrf($this->post('_token'))) {
                set_flash('error', 'Phiên hết hạn.');
            } else {
                $bid = (int)$this->post('id', 0);
                $position = $this->post('position', 'banner');
                $intervalRaw = $this->post('interval_ms', '');
                // Chỉ áp dụng interval cho slider; các vị trí khác luôn NULL.
                $intervalMs = null;
                if ($position === 'slider' && $intervalRaw !== '' && is_numeric($intervalRaw)) {
                    $intervalMs = max(1000, min(60000, (int)$intervalRaw));
                }
                $d = [
                    'title'       => trim($this->post('title')) ?: null,
                    'subtitle'    => trim($this->post('subtitle', '')) ?: null,
                    'link'        => trim($this->post('link', '')) ?: null,
                    'position'    => $position,
                    'sort_order'  => (int)$this->post('sort_order', 0),
                    'interval_ms' => $intervalMs,
                    'status'      => (int)(bool)$this->post('status', 0),
                ];
                if (!empty($_FILES['image']['name'])) {
                    $img = upload_image($_FILES['image'], 'banners');
                    if ($img) {
                        $d['image'] = $img;
                    }
                }
                if ($bid) {
                    Banner::update($bid, $d);
                    AuditLogger::log(AuditLogger::A_UPDATE, 'Banners', 'Cập nhật banner "' . ($d['title'] ?? '#' . $bid) . '"', admin_id());
                } else {
                    $bid = Banner::insert($d);
                    AuditLogger::log(AuditLogger::A_CREATE, 'Banners', 'Tạo banner "' . ($d['title'] ?? '#' . $bid) . '"', admin_id());
                }
                set_flash('success', 'Đã lưu banner.');
            }
            redirect(BASE_URL . '/quan-tri/banner');
        }
        if ($mode === 'xoa' && $id !== '') {
            Permission::require('banners', 'delete');
            $b = Banner::find((int)$id);
            Banner::delete((int)$id);
            AuditLogger::log(AuditLogger::A_DELETE, 'Banners', 'Xóa banner "' . ($b['title'] ?? '#' . $id) . '"', admin_id());
            set_flash('success', 'Đã xóa banner.');
            redirect(BASE_URL . '/quan-tri/banner');
        }

        $edit = $mode === 'sua' && $id !== '' ? Banner::find((int)$id) : null;
        $banners = Admin::db()->query("SELECT * FROM banners ORDER BY sort_order")->fetchAll();
        $pg = $this->paginateRows($banners, 12);
        $this->adminRender('banners', [
            'pageTitle' => 'Banner - WoodCon Admin',
            'rows'      => $pg['rows'],
            'pager'     => $pg['pager'],
            'edit'      => $edit,
            'positions' => ['slider', 'banner', 'flash_sale'],
        ]);
    }

    // ==================== TIN TỨC ====================

    private function news(string $mode, string $id): void
    {
        Permission::require('news', 'view');
        if ($mode === 'luu' && $this->isPost()) {
            Permission::require('news', $this->post('id') ? 'edit' : 'add');
            if (!verify_csrf($this->post('_token'))) {
                set_flash('error', 'Phiên hết hạn.');
            } else {
                $nid = (int)$this->post('id', 0);
                $d = [
                    'title'   => trim($this->post('title')),
                    'slug'    => slugify($this->post('title')),
                    'summary' => trim($this->post('summary')) ?: null,
                    'content' => trim($this->post('content')) ?: null,
                    'author'  => trim($this->post('author')) ?: null,
                    'status'  => (int)(bool)$this->post('status', 0),
                ];
                if (!empty($_FILES['image']['name'])) {
                    $img = upload_image($_FILES['image'], 'news');
                    if ($img) {
                        $d['image'] = $img;
                    }
                }
                if ($nid) {
                    News::update($nid, $d);
                    AuditLogger::log(AuditLogger::A_UPDATE, 'News', 'Cập nhật bài viết "' . $d['title'] . '"', admin_id());
                } else {
                    $nid = News::insert($d);
                    AuditLogger::log(AuditLogger::A_CREATE, 'News', 'Tạo bài viết "' . $d['title'] . '"', admin_id());
                }
                set_flash('success', 'Đã lưu bài viết.');
            }
            redirect(BASE_URL . '/quan-tri/tin-tuc');
        }
        if ($mode === 'xoa' && $id !== '') {
            Permission::require('news', 'delete');
            $n = News::find((int)$id);
            News::delete((int)$id);
            AuditLogger::log(AuditLogger::A_DELETE, 'News', 'Xóa bài viết "' . ($n['title'] ?? '#' . $id) . '"', admin_id());
            set_flash('success', 'Đã xóa bài viết.');
            redirect(BASE_URL . '/quan-tri/tin-tuc');
        }

        $edit = $mode === 'sua' && $id !== '' ? News::find((int)$id) : null;
        $f = [
            'q'      => trim((string)$this->get('q', '')),
            'status' => trim((string)$this->get('status', '')),
        ];
        $where = ['1=1'];
        $params = [];
        if ($f['q'] !== '') {
            $where[] = '(title LIKE ? OR slug LIKE ?)';
            $like = '%' . $f['q'] . '%';
            array_push($params, $like, $like);
        }
        if ($f['status'] === '1' || $f['status'] === '0') {
            $where[] = 'status = ?';
            $params[] = (int)$f['status'];
        }
        $whereStr = implode(' AND ', $where);
        $stmt = Admin::db()->prepare("SELECT * FROM news WHERE {$whereStr} ORDER BY created_at DESC");
        $stmt->execute($params);
        $news = $stmt->fetchAll();
        $pg = $this->paginateRows($news, 12);
        $this->adminRender('news', [
            'pageTitle' => 'Tin tức - WoodCon Admin',
            'rows'      => $pg['rows'],
            'pager'     => $pg['pager'],
            'edit'      => $edit,
            'f'         => $f,
        ]);
    }

    // ==================== THƯƠNG HIỆU ====================

    private function brands(string $mode, string $id): void
    {
        Permission::require('brands', 'view');
        if ($mode === 'luu' && $this->isPost()) {
            Permission::require('brands', $this->post('id') ? 'edit' : 'add');
            if (!verify_csrf($this->post('_token'))) {
                set_flash('error', 'Phiên hết hạn.');
            } else {
                $bid = (int)$this->post('id', 0);
                $d = [
                    'name' => trim($this->post('name')),
                    'slug' => slugify($this->post('name')),
                    'status' => (int)(bool)$this->post('status', 0),
                ];
                if ($bid) {
                    Brand::update($bid, $d);
                    AuditLogger::log(AuditLogger::A_UPDATE, 'Brands', 'Cập nhật thương hiệu "' . $d['name'] . '"', admin_id());
                } else {
                    $bid = Brand::insert($d);
                    AuditLogger::log(AuditLogger::A_CREATE, 'Brands', 'Tạo thương hiệu "' . $d['name'] . '"', admin_id());
                }
                set_flash('success', 'Đã lưu thương hiệu.');
            }
            redirect(BASE_URL . '/quan-tri/thuong-hieu');
        }
        if ($mode === 'xoa' && $id !== '') {
            Permission::require('brands', 'delete');
            $b = Brand::find((int)$id);
            Brand::delete((int)$id);
            AuditLogger::log(AuditLogger::A_DELETE, 'Brands', 'Xóa thương hiệu "' . ($b['name'] ?? '#' . $id) . '"', admin_id());
            set_flash('success', 'Đã xóa thương hiệu.');
            redirect(BASE_URL . '/quan-tri/thuong-hieu');
        }
        $f = [
            'q'      => trim((string)$this->get('q', '')),
            'status' => trim((string)$this->get('status', '')),
        ];
        $where = ['1=1'];
        $params = [];
        if ($f['q'] !== '') {
            $where[] = '(name LIKE ? OR slug LIKE ?)';
            $like = '%' . $f['q'] . '%';
            array_push($params, $like, $like);
        }
        if ($f['status'] === '1' || $f['status'] === '0') {
            $where[] = 'status = ?';
            $params[] = (int)$f['status'];
        }
        $whereStr = implode(' AND ', $where);
        $stmt = Admin::db()->prepare("SELECT * FROM brands WHERE {$whereStr} ORDER BY id");
        $stmt->execute($params);
        $brands = $stmt->fetchAll();
        $pg = $this->paginateRows($brands, 15);
        $this->adminRender('brands', [
            'pageTitle' => 'Thương hiệu - WoodCon Admin',
            'rows'      => $pg['rows'],
            'pager'     => $pg['pager'],
            'f'         => $f,
            'brandStats'=> Admin::brandStats(),
        ]);
    }

    // ==================== BÁO CÁO ====================

    private function reports(string $mode): void
    {
        Permission::require('reports', 'view');
        if ($mode === 'xuat') {
            Permission::require('reports', 'export');
            $this->exportReport();
        }
        $from = $this->get('from', date('Y-m-01'));
        $to = $this->get('to', date('Y-m-d'));
        $group = $this->get('group', 'day');
        $rmode = in_array($this->get('mode', '1'), ['1', '2', '3'], true) ? $this->get('mode', '1') : '1';

        // AJAX: đổi mode không tải lại trang -> trả HTML vùng nội dung (reports_region.php)
        if ($this->get('frag') === '1') {
            $report = Admin::report($from, $to, $group, $rmode);
            $r = $report;
            ob_start();
            require __DIR__ . '/../admin/views/reports_region.php';
            $html = ob_get_clean();
            $this->json(['ok' => true, 'html' => $html]);
        }

        $this->adminRender('reports', [
            'pageTitle' => 'Báo cáo tài chính - WoodCon Admin',
            'report'    => Admin::report($from, $to, $group, $rmode),
        ]);
    }

    /** Xuất báo cáo tài chính (CSV / Excel theo ?export=) */
    private function exportReport(): never
    {
        $from = $this->get('from', date('Y-m-01'));
        $to = $this->get('to', date('Y-m-d'));
        $format = $this->get('export', 'csv');
        $group = $this->get('group', 'day');
        $rmode = in_array($this->get('mode', '1'), ['1', '2', '3'], true) ? $this->get('mode', '1') : '1';
        $data = Admin::report($from, $to, $group, $rmode);

        // Ghi log đối soát
        write_log('report', 'Xuất báo cáo ' . strtoupper($format) . ' ' . $from . ' -> ' . $to, admin_id());
        AuditLogger::log(AuditLogger::A_EXPORT, 'Reports', 'Xuất báo cáo ' . strtoupper($format) . ' ' . $from . ' -> ' . $to, admin_id());

        $rows = [];
        if ($rmode === '2' && !empty($data['legal']['rows'])) {
            foreach ($data['legal']['rows'] as $r) {
                $rows[] = [
                    $r['period'],
                    (int)$r['num_invoices'],
                    (float)$r['sale_subtotal'],
                    (float)$r['sale_vat'],
                    (float)$r['refund_subtotal'],
                    (float)$r['refund_vat'],
                    (float)$r['taxable'],
                    (float)$r['vat'],
                ];
            }
            export_file($format, 'bao-cao-hoadon-' . $from . '_' . $to, [
                'Kỳ', 'Số hóa đơn', 'Bán (chưa thuế)', 'VAT bán', 'Trả hàng (chưa thuế)', 'VAT trả hàng', 'Số chịu thuế ròng', 'VAT phải nộp',
            ], $rows);
            exit;
        }
        foreach ($data['rows'] as $r) {
            $rows[] = [
                $r['period'],
                (int)$r['orders'],
                (float)$r['revenue'],
                (float)$r['goods_value'],
                (float)$r['discount'],
                (float)$r['shipping'],
                (float)$r['vat'],
            ];
        }

        export_file($format, 'bao-cao-' . $from . '_' . $to, [
            'Kỳ', 'Tổng đơn', 'Doanh thu (đã giao)', 'Giá trị hàng', 'Giảm giá', 'Phí ship', 'VAT',
        ], $rows);
    }

    // ==================== LIÊN HỆ / CSKH ====================

    private function contacts(string $mode): void
    {
        Permission::require('contacts', 'view');
        if ($mode === 'done' && $this->isPost() && isset($_POST['id'])) {
            Permission::require('contacts', 'edit');
            Contact::update((int)$this->post('id'), ['status' => 'done']);
            AuditLogger::log(AuditLogger::A_UPDATE, 'Contacts', 'Đánh dấu liên hệ #' . $this->post('id') . ' đã xử lý', admin_id());
            set_flash('success', 'Đã đánh dấu xử lý.');
            redirect(BASE_URL . '/quan-tri/lien-he');
        }
        $f = [
            'q'      => trim((string)$this->get('q', '')),
            'status' => trim((string)$this->get('status', '')),
        ];
        $where = ['1=1'];
        $params = [];
        if ($f['q'] !== '') {
            $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR subject LIKE ? OR message LIKE ?)';
            $like = '%' . $f['q'] . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if ($f['status'] === 'done' || $f['status'] === 'new') {
            $where[] = ($f['status'] === 'done') ? "status = 'done'" : "status != 'done'";
        }
        $whereStr = implode(' AND ', $where);
        $stmt = Admin::db()->prepare("SELECT * FROM contacts WHERE {$whereStr} ORDER BY status ASC, created_at DESC");
        $stmt->execute($params);
        $contacts = $stmt->fetchAll();
        $pg = $this->paginateRows($contacts, 15);
        $this->adminRender('contacts', [
            'pageTitle' => 'Liên hệ - WoodCon Admin',
            'rows'      => $pg['rows'],
            'pager'     => $pg['pager'],
            'f'         => $f,
        ]);
    }

    // ==================== BẢO HÀNH ====================

    private function warranties(string $mode, string $id): void
    {
        Permission::require('warranties', 'view');
        // Xuất danh sách phiếu bảo hành (CSV / Excel)
        if ($mode === 'xuat') {
            Permission::require('warranties', 'export');
            $this->warrantyExport();
        }
        // Cập nhật trạng thái + ghi lịch sử
        if ($mode === 'log' && $id !== '' && $this->isPost()) {
            Permission::require('warranties', 'edit');
            if (!verify_csrf($this->post('_token'))) {
                set_flash('error', 'Phiên hết hạn.');
            } else {
                $status = $this->post('status', 'active');
                $allowed = ['active', 'expired', 'used', 'rejected'];
                if (!in_array($status, $allowed, true)) {
                    $status = 'active';
                }
                $db = Admin::db();
                $oldRow = $db->query('SELECT status FROM warranties WHERE id = ' . (int)$id)->fetch();
                $oldStatus = (string)($oldRow['status'] ?? '');
                $db->prepare('UPDATE warranties SET status = ? WHERE id = ?')->execute([$status, (int)$id]);
                $__wStatusLabels = ['active' => 'Còn bảo hành', 'expired' => 'Hết hạn', 'used' => 'Đã sử dụng', 'rejected' => 'Từ chối'];
                if ($status !== $oldStatus && $oldStatus !== '') {
                    Order::logWarranty((int)$id, 'Đổi trạng thái: ' . ($__wStatusLabels[$oldStatus] ?? $oldStatus) . ' → ' . ($__wStatusLabels[$status] ?? $status));
                }
                $note = trim((string)$this->post('note', ''));
                if ($note !== '') {
                    Order::logWarranty((int)$id, $note);
                }
                write_log('warranty', 'Cập nhật bảo hành #' . $id . ' -> ' . $status, admin_id());
                AuditLogger::log(AuditLogger::A_UPDATE, 'Warranties', 'Cập nhật bảo hành #' . $id . ' -> ' . $status, admin_id());
                set_flash('success', 'Đã cập nhật phiếu bảo hành #' . $id . '.');
            }
            redirect(BASE_URL . '/quan-tri/bao-hanh');
        }

        $r = Admin::db()->query(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'used') AS used,
                SUM(status = 'rejected') AS rejected,
                SUM(status = 'expired' OR (status = 'active' AND warranty_end < CURDATE())) AS expired,
                SUM(status = 'active' AND warranty_end >= CURDATE()) AS active,
                SUM(status = 'active' AND warranty_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)) AS expiring
             FROM warranties"
        )->fetch();
        $stats = [
            'total'    => (int)($r['total'] ?? 0),
            'active'   => (int)($r['active'] ?? 0),
            'expired'  => (int)($r['expired'] ?? 0),
            'used'     => (int)($r['used'] ?? 0),
            'rejected' => (int)($r['rejected'] ?? 0),
            'expiring' => (int)($r['expiring'] ?? 0),
        ];

        $q = trim((string)$this->get('q'));
        $listSql = "SELECT w.*, o.order_code, o.customer_name, o.customer_phone, oi.product_name, oi.variant_value
                FROM warranties w
                JOIN order_items oi ON oi.id = w.order_item_id
                JOIN orders o ON o.id = w.order_id"
            . ($q !== '' ? ' WHERE w.serial_no LIKE ? OR o.order_code LIKE ? OR o.customer_phone LIKE ? OR oi.product_name LIKE ?' : '')
            . ' ORDER BY w.id DESC';

        $like = '%' . $q . '%';
        $params = $q !== '' ? [$like, $like, $like, $like] : [];
        $stmt = Admin::db()->prepare($listSql);
        $stmt->execute($params);
        $warranties = $stmt->fetchAll();
        foreach ($warranties as &$__w) {
            $__w['history'] = Order::warrantyHistory((int)$__w['id']);
        }
        unset($__w);
        $pg = $this->paginateRows($warranties, 15);

        $this->adminRender('warranties', [
            'pageTitle' => 'Bảo hành - WoodCon Admin',
            'rows'      => $pg['rows'],
            'pager'     => $pg['pager'],
            'q'         => $q,
            'stats'     => $stats,
        ]);
    }

    /** Xuất danh sách phiếu bảo hành theo bộ lọc hiện tại (CSV / Excel) */
    private function warrantyExport(): never
    {
        $q = trim((string)$this->get('q'));
        $sql = "SELECT w.serial_no, w.purchase_date, w.warranty_end, w.status,
                       oi.product_name, oi.variant_value, o.order_code, o.customer_name, o.customer_phone
                FROM warranties w
                JOIN order_items oi ON oi.id = w.order_item_id
                JOIN orders o ON o.id = w.order_id"
            . ($q !== '' ? ' WHERE w.serial_no LIKE ? OR o.order_code LIKE ? OR o.customer_phone LIKE ? OR oi.product_name LIKE ?' : '')
            . ' ORDER BY w.id DESC';
        $like = '%' . $q . '%';
        $params = $q !== '' ? [$like, $like, $like, $like] : [];
        $stmt = Admin::db()->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        write_log('warranty', 'Xuất danh sách phiếu bảo hành', admin_id());
        AuditLogger::log(AuditLogger::A_EXPORT, 'Warranties', 'Xuất danh sách phiếu bảo hành (' . count($items) . ' phiếu, q=' . ($q ?: '—') . ')');

        $statusLabel = ['active' => 'Còn bảo hành', 'expired' => 'Hết hạn', 'used' => 'Đã sử dụng', 'rejected' => 'Từ chối'];
        $rows = [];
        foreach ($items as $w) {
            $rows[] = [
                $w['serial_no'],
                $w['product_name'],
                $w['variant_value'] ?? '',
                $w['order_code'],
                $w['customer_name'],
                $w['customer_phone'],
                $w['purchase_date'],
                $w['warranty_end'],
                $statusLabel[$w['status']] ?? $w['status'],
            ];
        }

        export_file($this->get('export', 'csv'), 'bao-hanh-' . date('Y-m-d'), [
            'Serial', 'Sản phẩm', 'Biến thể', 'Mã đơn', 'Khách hàng', 'SĐT',
            'Ngày mua', 'Hạn bảo hành', 'Trạng thái',
        ], $rows);
    }

    // ==================== THUẾ (VAT) ====================

    /** Quản lý mức thuế VAT + báo cáo + gán thuế cho sản phẩm. */
    private function taxes(string $mode, string $id): void
    {
        Permission::require('taxes', 'view');
        if ($mode === 'luu' && $this->isPost()) {
            Permission::require('taxes', $this->post('id') ? 'edit' : 'add');
            if (!verify_csrf($this->post('_token'))) {
                set_flash('error', 'Phiên hết hạn.');
            } else {
                $rate = max(0, (float)$this->post('rate', 0));
                if ($rate > 100) {
                    $rate = 100;
                }
                $name = trim((string)$this->post('name', ''));
                if ($name === '') {
                    $name = 'Thuế GTGT ' . rtrim(rtrim((string)$rate, '0'), '.') . '%';
                }
                $d = [
                    'name'        => $name,
                    'rate'        => $rate,
                    'description' => trim((string)$this->post('description', '')) ?: null,
                    'status'      => (int)(bool)$this->post('status', 0),
                ];
                $rateId = (int)$this->post('id', 0);
                if ($rateId) {
                    TaxRate::update($rateId, $d);
                    AuditLogger::log(AuditLogger::A_UPDATE, 'Taxes', 'Cập nhật mức thuế #' . $rateId . ' ' . $name . ' (' . $rate . '%)', admin_id());
                } else {
                    $rateId = TaxRate::insert($d);
                    AuditLogger::log(AuditLogger::A_CREATE, 'Taxes', 'Tạo mức thuế #' . $rateId . ' ' . $name . ' (' . $rate . '%)', admin_id());
                }
                if ((bool)$this->post('make_default', 0)) {
                    TaxRate::setDefault($rateId);
                }
                write_log('tax', 'Lưu mức thuế #' . $rateId . ' ' . $name . ' (' . $rate . '%)', admin_id());
                set_flash('success', 'Đã lưu mức thuế.');
            }
            redirect(BASE_URL . '/quan-tri/thue');
            return;
        }

        // Đặt làm mức mặc định
        if ($mode === 'mac-dinh' && $id !== '' && $this->isPost()) {
            if (verify_csrf($this->post('_token'))) {
                TaxRate::setDefault((int)$id);
                set_flash('success', 'Đã đặt làm mức thuế mặc định.');
            }
            redirect(BASE_URL . '/quan-tri/thue');
            return;
        }

        // Bật/tắt hoạt động
        if ($mode === 'trang-thai' && $id !== '') {
            Permission::require('taxes', 'edit');
            $t = TaxRate::find((int)$id);
            if ($t) {
                TaxRate::update((int)$id, ['status' => $t['status'] ? 0 : 1]);
            }
            redirect(BASE_URL . '/quan-tri/thue');
            return;
        }

        // Xóa mức thuế (chặn nếu còn sản phẩm đang dùng)
        if ($mode === 'xoa' && $id !== '') {
            Permission::require('taxes', 'delete');
            $this->confirmThen('xóa mức thuế');
            $usedStmt = Admin::db()->prepare('SELECT COUNT(*) AS c FROM products WHERE tax_rate_id = ?');
            $usedStmt->execute([(int)$id]);
            $used = (int)$usedStmt->fetch()['c'];
            $def = TaxRate::defaultRate();
            if ((int)$id === (int)($def['id'] ?? 0)) {
                set_flash('error', 'Không thể xóa mức thuế mặc định. Hãy chuyển mặc định sang mức khác trước.');
            } elseif ($used > 0) {
                set_flash('error', 'Không thể xóa: còn ' . $used . ' sản phẩm đang dùng mức thuế này. Hãy gán lại thuế cho chúng trước.');
            } else {
                $t = TaxRate::find((int)$id);
                TaxRate::delete((int)$id);
                AuditLogger::log(AuditLogger::A_DELETE, 'Taxes', 'Xóa mức thuế "' . ($t['name'] ?? '#' . $id) . '"', admin_id());
                set_flash('success', 'Đã xóa mức thuế.');
            }
            redirect(BASE_URL . '/quan-tri/thue');
            return;
        }

        // Gán hàng loạt một mức thuế cho sản phẩm chưa có thuế (hoặc tất cả sản phẩm)
        if ($mode === 'gan' && $id !== '' && $this->isPost()) {
            if (!verify_csrf($this->post('_token'))) {
                set_flash('error', 'Phiên hết hạn.');
            } else {
                $rateId = (int)$this->post('rate_id', 0);
                $applyTo = (string)$this->post('apply_to', 'empty');
                $rate = TaxRate::find($rateId);
                if (!$rate || !$rate['status']) {
                    set_flash('error', 'Mức thuế không hợp lệ.');
                } else {
                    $where = $applyTo === 'all' ? '1=1' : 'tax_rate_id IS NULL';
                    $n = Admin::db()->prepare("UPDATE products SET tax_rate_id = ? WHERE {$where}")->execute([$rateId]);
                    write_log('tax', 'Gán mức thuế #' . $rateId . ' cho sản phẩm ('. $applyTo . ')', admin_id());
                    set_flash('success', 'Đã áp dụng mức thuế "' . $rate['name'] . '" cho sản phẩm.');
                }
            }
            redirect(BASE_URL . '/quan-tri/thue');
            return;
        }

        // Lưu mức thuế cho Phí vận chuyển / Phí lắp đặt (cấu hình ĐỘC LẬP với thuế sản phẩm)
        if ($mode === 'dich-vu' && $this->isPost()) {
            Permission::require('taxes', 'edit');
            if (!verify_csrf($this->post('_token'))) {
                set_flash('error', 'Phiên hết hạn.');
            } else {
                $shipRate     = min(100, max(0, (float)$this->post('tax_shipping_rate', 5)));
                $installRate  = min(100, max(0, (float)$this->post('tax_install_rate', 5)));
                set_setting('tax_shipping_rate', $shipRate, 'tax');
                set_setting('tax_install_rate', $installRate, 'tax');
                AuditLogger::log(AuditLogger::A_UPDATE, 'Settings', "Thuế dịch vụ: vận chuyển {$shipRate}%, lắp đặt {$installRate}%", admin_id());
                write_log('tax', 'Lưu thuế dịch vụ: vận chuyển ' . $shipRate . '%, lắp đặt ' . $installRate . '%', admin_id());
                set_flash('success', 'Đã lưu mức thuế cho vận chuyển & lắp đặt.');
            }
            redirect(BASE_URL . '/quan-tri/thue');
            return;
        }

        // Báo cáo VAT theo kỳ + theo mức (dữ liệu snapshot từ order_items)
        $from = (string)$this->get('from', '');
        $to = (string)$this->get('to', '');
        $where = ['1=1'];
        $params = [];
        if ($from !== '') {
            $where[] = 'o.created_at >= ?';
            $params[] = $from . ' 00:00:00';
        }
        if ($to !== '') {
            $where[] = 'o.created_at <= ?';
            $params[] = $to . ' 23:59:59';
        }
        $whereStr = implode(' AND ', $where);

        // Doanh thu & VAT tổng (đơn đã giao) theo mức thuế trong order_items
        $reportRows = Admin::db()->prepare(
            "SELECT oi.vat_rate,
                    COUNT(DISTINCT o.id) AS orders,
                    ROUND(SUM(oi.subtotal),0) AS goods_value,
                    ROUND(SUM(oi.quantity * oi.price * oi.vat_rate / (100 + oi.vat_rate)),0) AS vat
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE {$whereStr}
             GROUP BY oi.vat_rate
             ORDER BY oi.vat_rate ASC"
        );
        $reportRows->execute($params);
        $reportRows = $reportRows->fetchAll();

        $reportTotals = ['orders' => 0, 'goods_value' => 0, 'vat' => 0];
        foreach ($reportRows as $r) {
            $reportTotals['orders']    += (int)$r['orders'];
            $reportTotals['goods_value'] += (float)$r['goods_value'];
            $reportTotals['vat']       += (float)$r['vat'];
        }

        $rates = TaxRate::where('1=1', [], '*', 'rate ASC');
        $default = TaxRate::defaultRate();

        // Số sản phẩm chưa gán thuế
        $unassigned = (int)Admin::db()->query('SELECT COUNT(*) AS c FROM products WHERE tax_rate_id IS NULL')->fetch()['c'];

        $edit = ($mode === 'sua' && $id !== '') ? TaxRate::find((int)$id) : null;

        $this->adminRender('taxes', [
            'pageTitle'     => 'Thuế (VAT) - WoodCon Admin',
            'rates'         => $rates,
            'default'       => $default,
            'edit'          => $edit,
            'reportRows'    => $reportRows,
            'reportTotals'  => $reportTotals,
            'unassigned'    => $unassigned,
            'from'          => $from,
            'to'            => $to,
            'taxShipRate'   => (float)get_setting('tax_shipping_rate', 5),
            'taxInstallRate'=> (float)get_setting('tax_install_rate', 5),
        ]);
    }

    // ==================== CÀI ĐẶT ====================

    // ==================== VẬN CHUYỂN (2 LOẠI) ====================

    /**
     * Trang quản lý vận chuyển 2 loại (chỉ super admin):
     *  - Loại 1 (shop): nhãn, kho, khoảng cách 63 tỉnh, bậc phí theo km.
     *  - Loại 2 (carrier): nhãn, hệ số quy đổi thể tích, đơn giá cước theo vùng.
     */
    private function shippingAdmin(): void
    {
        if (!Admin::isSuper()) {
            redirect(BASE_URL . '/quan-tri');
        }

        if ($this->isPost()) {
            if (!verify_csrf($this->post('_token'))) {
                set_flash('error', 'Phiên hết hạn, vui lòng thử lại.');
                redirect(BASE_URL . '/quan-tri/van-chuyen');
            }
            $sub = $this->post('sub', '');

            if ($sub === 'vtp_provinces' || $sub === 'vtp_districts') {
                // AJAX: tải danh mục Tỉnh/Quận Viettel Post cho dropdown (không redirect)
                $this->handleVtpCatalogAjax($sub);
                return;
            }

            if ($sub === 'options') {
                // Cài đặt nhãn + kho + hệ số quy đổi + ngưỡng freeship chung
                $map = [
                    'ship_label_type1' => 'shipping', 'ship_label_type2' => 'shipping',
                    'ship_label_install' => 'shipping', 'ship_default_install_fee' => 'shipping',
                    'ship_volumetric_divisor' => 'shipping',
                    'ship_warehouse_city' => 'shipping', 'ship_warehouse_address' => 'shipping',
                    'ship_min_km' => 'shipping', 'ship_min_fee' => 'shipping',
                    'free_ship_threshold' => 'shipping',
                ];
                $saved = 0;
                foreach ($map as $k => $g) {
                    $v = $this->post('setting_' . $k);
                    if ($v === null) continue;
                    if (set_setting($k, $v, $g)) $saved++;
                }
                AuditLogger::log(AuditLogger::A_UPDATE, 'Shipping', "Cập nhật tùy chọn vận chuyển ($saved)", admin_id());
                set_flash('success', "Đã lưu $saved tùy chọn vận chuyển.");
                redirect(BASE_URL . '/quan-tri/van-chuyen#options');
            }

            if ($sub === 'province') {
                // Thêm mới khoảng cách tỉnh
                $prov = trim($this->post('province', ''));
                $km = (float)$this->post('distance_km', 0);
                if ($prov !== '') {
                    $db = Admin::db();
                    $db->prepare('INSERT INTO province_distances (province, distance_km, sort_order) VALUES (?, ?, 99) ON DUPLICATE KEY UPDATE distance_km = VALUES(distance_km)')
                        ->execute([$prov, $km]);
                    AuditLogger::log(AuditLogger::A_CREATE, 'Shipping', "Thêm/Cập nhật tỉnh $prov ($km km)", admin_id());
                    set_flash('success', 'Đã lưu khoảng cách tỉnh.');
                } else {
                    set_flash('error', 'Chưa nhập tên tỉnh.');
                }
                redirect(BASE_URL . '/quan-tri/van-chuyen#loai1');
            }

            if ($sub === 'province_del') {
                $prov = trim($this->post('province', ''));
                if ($prov !== '') {
                    Admin::db()->prepare('DELETE FROM province_distances WHERE province = ?')->execute([$prov]);
                    AuditLogger::log(AuditLogger::A_DELETE, 'Shipping', "Xóa tỉnh $prov", admin_id());
                    set_flash('success', 'Đã xóa tỉnh.');
                }
                redirect(BASE_URL . '/quan-tri/van-chuyen#loai1');
            }

            if ($sub === 'bracket') {
                // Thêm mới bậc phí
                $min = (int)$this->post('min_km', 0);
                $max = $this->post('max_km') === '' ? null : (int)$this->post('max_km');
                $fee = (float)$this->post('fee', 0);
                $fst = trim((string)$this->post('free_ship_threshold', '')) === '' ? null : (float)$this->post('free_ship_threshold');
                $db = Admin::db();
                $db->prepare('INSERT INTO ship_fee_brackets (min_km, max_km, fee, free_ship_threshold, sort_order) VALUES (?, ?, ?, ?, 99)')->execute([$min, $max, $fee, $fst]);
                set_flash('success', 'Đã thêm bậc phí.');
                redirect(BASE_URL . '/quan-tri/van-chuyen#loai1');
            }

            if ($sub === 'bracket_update' && $this->post('id') !== null) {
                // Cập nhật phí + ngưỡng miễn phí vận chuyển của bậc đã có
                $id = (int)$this->post('id', 0);
                $fee = (float)$this->post('fee', 0);
                $fst = trim((string)$this->post('free_ship_threshold', '')) === '' ? null : (float)$this->post('free_ship_threshold');
                $db = Admin::db();
                $db->prepare('UPDATE ship_fee_brackets SET fee = ?, free_ship_threshold = ? WHERE id = ?')->execute([$fee, $fst, $id]);
                set_flash('success', 'Đã cập nhật bậc phí.');
                redirect(BASE_URL . '/quan-tri/van-chuyen#loai1');
            }

            if ($sub === 'bracket_del') {
                $id = (int)$this->post('id', 0);
                Admin::db()->prepare('DELETE FROM ship_fee_brackets WHERE id = ?')->execute([$id]);
                set_flash('success', 'Đã xóa bậc phí.');
                redirect(BASE_URL . '/quan-tri/van-chuyen#loai1');
            }

            if ($sub === 'vtp_test' && $this->post('_ajax', '0') === '1') {
                // AJAX: kiểm tra kết nối, trả kết quả ngay dưới nút (không redirect)
                $this->handleVtpTestAjax();
                return;
            }

            if (in_array($sub, ['vtp_save', 'vtp_test', 'vtp_token_fetch', 'vtp_sync'], true)) {
                // Cấu hình / kiểm tra / đồng bộ Loại 2 - Viettel Post
                $this->handleVtpSub($sub);
                return;
            }

            set_flash('error', 'Thao tác không hợp lệ.');
            redirect(BASE_URL . '/quan-tri/van-chuyen');
        }

        $vtpForce = \WoodCon\ViettelPost::configured() && \WoodCon\ViettelPost::cacheStale();

        $this->adminRender('shipping_admin', [
            'pageTitle' => 'Vận chuyển - WoodCon Admin',
            'provinces' => \WoodCon\Shipping::provinceDistances(),
            'brackets'  => \WoodCon\Shipping::feeBrackets(),
            'typeLabels'=> \WoodCon\Shipping::typeLabels(),
            'vtp'       => \WoodCon\ViettelPost::config(),
            'vtpCatalog'=> [
                'provinces' => \WoodCon\ViettelPost::getProvinces($vtpForce),
            ],
            'vtpSenderDistricts' => \WoodCon\ViettelPost::getDistricts((int)get_setting('vtp_sender_province_id', 0), $vtpForce),
            'curlOk'    => \WoodCon\ViettelPost::curlAvailable(),
            'active'    => 'settings',
            'activeSub' => 'van-chuyen',
        ]);
    }

    /** Ghi nhận cấu hình Loại 2 (Viettel Post) rồi thực hiện hành động theo sub */
    private function handleVtpSub(string $sub): void
    {
        $saved = $this->saveVtpConfig();
        $tok = trim((string)$this->post('setting_vtp_token', ''));
        $loginUser = trim((string)$this->post('vtp_login_username', ''));
        $loginPass = (string)$this->post('vtp_login_password', '');

        if ($sub === 'vtp_save' || $sub === 'vtp_test') {
            AuditLogger::log(AuditLogger::A_UPDATE, 'Shipping', "Lưu cấu hình Viettel Post ($saved mục)", admin_id());
        }

        if ($sub === 'vtp_save') {
            set_flash('success', empty($tok) ? "Đã lưu $saved cấu hình Loại 2." : "Đã lưu $saved cấu hình Loại 2 (kèm Token mới).");
        } elseif ($sub === 'vtp_test') {
            $res = $loginPass !== '' && $loginUser !== '' ? \WoodCon\ViettelPost::fetchToken($loginUser, $loginPass) : ['ok' => false, 'message' => ''];
            if ($res['ok']) {
                $res = \WoodCon\ViettelPost::testConnection();
            }
            if (!($res['ok'] ?? false) && $res['message'] === '') {
                $res = \WoodCon\ViettelPost::testConnection();
            }
            $res['ok'] ??= false;
            set_flash($res['ok'] ? 'success' : 'error', $res['message']);
        } elseif ($sub === 'vtp_token_fetch') {
            $res = \WoodCon\ViettelPost::fetchToken($loginUser, $loginPass);
            set_flash($res['ok'] ? 'success' : 'error', $res['message']);
        } elseif ($sub === 'vtp_sync') {
            $res = \WoodCon\ViettelPost::syncCategories((int)$this->post('vtp_sender_province_id', 0));
            set_flash($res['ok'] ? 'success' : 'error', $res['message']);
            redirect(BASE_URL . '/quan-tri/van-chuyen#loai2');
        }

        redirect(BASE_URL . '/quan-tri/van-chuyen#loai2');
    }

    /** Lưu tất cả trường cấu hình Loại 2 từ POST (không redirect). Trả về số mục đã lưu. */
    private function saveVtpConfig(): int
    {
        $map = [
            'vtp_enabled' => 'shipping',
            'vtp_sender_province_id' => 'shipping', 'vtp_sender_province_name' => 'shipping',
            'vtp_sender_district_id' => 'shipping', 'vtp_sender_district_name' => 'shipping',
            'vtp_service' => 'shipping', 'vtp_product_type' => 'shipping',
            'vtp_cod_enabled' => 'shipping', 'vtp_surcharge' => 'shipping',
            'vtp_freeship_threshold' => 'shipping', 'ship_volumetric_divisor' => 'shipping',
            'ship_label_type2' => 'shipping',
        ];
        $saved = 0;
        foreach ($map as $k => $g) {
            $v = $this->post('setting_' . $k);
            if ($v === null) continue;
            if (set_setting($k, $v, $g)) $saved++;
        }

        // Thông tin đăng nhập lấy Token (vtp_username luôn ghi đè; vtp_password để trống = giữ nguyên)
        $loginUser = trim((string)$this->post('vtp_login_username', ''));
        if ($loginUser !== '' && set_setting('vtp_username', $loginUser, 'shipping')) $saved++;
        $loginPass = (string)$this->post('vtp_login_password', '');
        if ($loginPass !== '' && set_setting('vtp_password', $loginPass, 'shipping')) $saved++;

        // Token: chỉ ghi đè khi người dùng nhập mã mới (để trống = giữ nguyên)
        $tok = trim((string)$this->post('setting_vtp_token', ''));
        if ($tok !== '') {
            \WoodCon\ViettelPost::saveToken($tok);
            $saved++;
        }
        return $saved;
    }

    /** AJAX: kiểm tra kết nối — trả JSON cập nhật ngay dưới nút, không redirect */
    private function handleVtpTestAjax(): void
    {
        $this->saveVtpConfig();
        $loginUser = trim((string)$this->post('vtp_login_username', ''));
        $loginPass = (string)$this->post('vtp_login_password', '');

        $res = $loginUser !== '' && $loginPass !== '' ? \WoodCon\ViettelPost::fetchToken($loginUser, $loginPass) : ['ok' => false, 'message' => ''];
        if ($res['ok'] ?? false) {
            $res = \WoodCon\ViettelPost::testConnection();
        }
        if (!($res['ok'] ?? false) && ($res['message'] ?? '') === '') {
            $res = \WoodCon\ViettelPost::testConnection();
        }
        $res['ok'] ??= false;
        json_response(['ok' => (bool)$res['ok'], 'message' => (string)($res['message'] ?? '')]);
    }

    /** AJAX: tải danh mục Tỉnh/Quận Viettel Post cho dropdown (cache trước, API khi thiếu). Không redirect. */
    private function handleVtpCatalogAjax(string $sub): void
    {
        if (!\WoodCon\ViettelPost::configured()) {
            json_response(['ok' => false, 'message' => 'Cần nhập Token (Bước 1) trước khi tải danh mục.']);
            return;
        }
        if ($sub === 'vtp_provinces') {
            json_response(['ok' => true, 'provinces' => \WoodCon\ViettelPost::getProvinces(\WoodCon\ViettelPost::cacheStale())]);
            return;
        }
        $pid = (int)$this->post('province_id', 0);
        if ($pid <= 0) {
            json_response(['ok' => false, 'message' => 'Chưa chọn tỉnh/thành.']);
            return;
        }
        json_response(['ok' => true, 'districts' => \WoodCon\ViettelPost::getDistricts($pid)]);
    }

    private function settings(): void
    {
        // Lưu cài đặt (chỉ super admin, POST + CSRF)
        if ($this->isPost()) {
            if (!Admin::isSuper()) {
                redirect(BASE_URL . '/quan-tri/cai-dat');
            }
            if (!verify_csrf($this->post('_token'))) {
                set_flash('error', 'Phiên hết hạn, vui lòng thử lại.');
                redirect(BASE_URL . '/quan-tri/cai-dat');
            }

            // AJAX: kiểm tra kết nối tích hợp (SMTP / eSMS / Gemini / Goong) — trả JSON, không redirect
            if ($this->post('settings_test') === '1') {
                $section = (string)$this->post('section', '');
                if ($section === 'ai') {
                    require_once dirname(__DIR__) . '/config/ai-provider.php';
                }
                $res = match ($section) {
                    'smtp' => \Mailer::testConnection(),
                    'sms'  => \WoodCon\Sms::testConnection(),
                    'ai'   => kiem_tra_ai(),
                    'maps' => \WoodCon\GoongService::testConnection(),
                    default => ['ok' => false, 'message' => 'Nhóm này chưa hỗ trợ kiểm tra kết nối.'],
                };
                $res['ok'] ??= false;
                json_response(['ok' => (bool)$res['ok'], 'message' => (string)($res['message'] ?? '')]);
            }

            $groupMap = [
                'order_wait_confirm_minutes' => 'general',
                'max_delivery_fail'          => 'general',
                'cod_manual_verify_amount'   => 'general',
                'cod_rules'                  => 'general',
                'free_ship_threshold'        => 'shipping',
                'ship_fee_noithanh'          => 'shipping',
                'ship_fee_lientinh'          => 'shipping',
                'ship_fee_lienmien'          => 'shipping',
                'ship_min_fee'               => 'shipping',
                'ship_min_km'                => 'shipping',
                'ship_divisor'               => 'shipping',
                'ship_label_type1'           => 'shipping',
                'ship_label_type2'           => 'shipping',
                'ship_label_install'         => 'shipping',
                'ship_default_install_fee'   => 'shipping',
                'ship_volumetric_divisor'    => 'shipping',
                'ship_warehouse_city'        => 'shipping',
                'ship_warehouse_address'     => 'shipping',
                'sms_enabled'                => 'sms',
                'sms_provider'               => 'sms',
                'esms_api_key'               => 'sms',
                'esms_secret'                => 'sms',
                'esms_brandname'             => 'sms',
                'esms_sms_type'              => 'sms',
                'otp_resend_limit_per_hour'  => 'sms',
                'point_rate'                 => 'member',
                'point_value'                => 'member',
                'hotline'                    => 'contact',
                'shop_email'                 => 'contact',
                'shop_address'               => 'contact',
                'site_phone'                 => 'contact',
                'site_zalo'                  => 'contact',
                'site_facebook'              => 'contact',
                'smtp_host'                  => 'smtp',
                'smtp_port'                  => 'smtp',
                'smtp_user'                  => 'smtp',
                'smtp_pass'                  => 'smtp',
                'smtp_secure'                => 'smtp',
                'invoice_company_name'       => 'invoice',
                'invoice_tax_code'           => 'invoice',
                'invoice_address'            => 'invoice',
                'invoice_phone'              => 'invoice',
                'invoice_email'              => 'invoice',
                'invoice_number_prefix'      => 'invoice',
                'invoice_footer'             => 'invoice',
                'invoice_legal_note'         => 'invoice',
                'ai_api_key'                 => 'ai',
                'ai_models'                  => 'ai',
                'ai_provider'                => 'ai',
                'goong_api_key'              => 'maps',
                'goong_autocomplete_enabled' => 'maps',
                'goong_location_lat'         => 'maps',
                'goong_location_lng'         => 'maps',
            ];
            // Nhóm cài đặt: slug => danh sách khóa thuộc nhóm
            $allowedGroups = [
                'operation' => ['order_wait_confirm_minutes','max_delivery_fail','cod_manual_verify_amount'],
                'shipping'  => ['free_ship_threshold','ship_fee_noithanh','ship_fee_lientinh','ship_fee_lienmien',
                                'ship_min_fee','ship_min_km','ship_divisor',
                                'ship_label_type1','ship_label_type2','ship_label_install','ship_default_install_fee',
                                'ship_volumetric_divisor','ship_warehouse_city','ship_warehouse_address'],
                'finance'   => ['point_rate','point_value','cod_rules'],
                'contact'   => ['hotline','shop_email','shop_address','site_phone','site_zalo','site_facebook'],
                'sms'       => ['sms_enabled','sms_provider','esms_api_key','esms_secret','esms_brandname','esms_sms_type','otp_resend_limit_per_hour'],
                'smtp'      => ['smtp_host','smtp_port','smtp_user','smtp_pass','smtp_secure'],
                'invoice'   => ['invoice_company_name','invoice_tax_code','invoice_address','invoice_phone',
                                'invoice_email','invoice_number_prefix','invoice_footer','invoice_legal_note'],
                'ai'        => ['ai_api_key','ai_models','ai_provider'],
                'maps'      => ['goong_api_key','goong_autocomplete_enabled','goong_location_lat','goong_location_lng'],
            ];
            // Các khóa bí mật: nếu submit rỗng thì GIỮ NGUYÊN giá trị đã lưu (không ghi đè).
            $secretKeys = ['smtp_pass', 'ai_api_key', 'esms_secret', 'goong_api_key'];
            // Chỉ lưu các field thuộc đúng nhóm (section) được submit
            $section = $this->post('section', '');
            if ($section === '' || !isset($allowedGroups[$section])) {
                set_flash('error', 'Nhóm cài đặt không hợp lệ.');
                redirect(BASE_URL . '/quan-tri/cai-dat');
            }
            $allowed = $allowedGroups[$section];
            $saved = 0;
            foreach ($allowed as $key) {
                $val = $this->post('setting_' . $key, null);
                if ($val === null) continue;
                // Bí mật để trống → bỏ qua, bảo toàn giá trị cũ
                if (in_array($key, $secretKeys, true) && trim((string)$val) === '') {
                    continue;
                }
                $group = $groupMap[$key] ?? 'general';
                if (set_setting($key, $val, $group)) $saved++;
            }
            write_log('settings', "Cập nhật $saved cài đặt (nhóm: $section)", admin_id());
            AuditLogger::log(AuditLogger::A_UPDATE, 'Settings', "Cập nhật $saved cài đặt (nhóm: $section)", admin_id());
            set_flash('success', "Đã lưu $saved cài đặt.");
            redirect(BASE_URL . '/quan-tri/cai-dat#' . $section);
        }

        // Trạng thái kết nối của các nhóm tích hợp bên ngoài (badge ✓/✗ + nút "Kiểm tra kết nối")
        $statusOf = static function (string $section): array {
            switch ($section) {
                case 'smtp':
                    $ready = trim((string)get_setting('smtp_host', '')) !== ''
                        && trim((string)get_setting('smtp_user', '')) !== ''
                        && trim((string)get_setting('smtp_pass', '')) !== '';
                    return ['ok' => $ready, 'message' => $ready ? 'Đã cấu hình SMTP.' : 'Chưa cấu hình đủ SMTP (host + username + password).'];
                case 'sms':
                    $ready = \WoodCon\Sms::isConfigured();
                    return ['ok' => $ready, 'message' => $ready ? 'Đã bật gửi SMS thật và có eSMS API Key.' : 'Chưa bật gửi SMS thật hoặc thiếu eSMS API Key.'];
                case 'ai':
                    $ready = trim((string)get_setting('ai_api_key', '')) !== '';
                    return ['ok' => $ready, 'message' => $ready ? 'Đã cấu hình Google API Key (Gemini).' : 'Chưa nhập Google API Key (Gemini).'];
                case 'maps': {
                    $ready = \WoodCon\GoongService::enabled();
                    $last = \WoodCon\GoongService::lastCallInfo();
                    $msg = $ready ? 'Goong Maps đã bật và có API Key.' : 'Chưa bật gợi ý địa chỉ hoặc thiếu Goong API Key.';
                    if (trim((string)$last['time']) !== '') {
                        $msg .= ' Lần gọi gần nhất (' . $last['time'] . '): ' . (trim((string)$last['message']) !== '' ? $last['message'] : 'chưa có');
                    }
                    return ['ok' => $ready, 'message' => $msg];
                }
            }
            return ['ok' => false, 'message' => ''];
        };

        $this->adminRender('settings', [
            'pageTitle' => 'Cài đặt - WoodCon Admin',
            'settingsSections' => [
                'operation' => ['title' => 'Vận hành & giao dịch',   'fields' => ['order_wait_confirm_minutes' => 'Thời gian chờ xác nhận (phút)', 'max_delivery_fail' => 'Số lần giao thất bại tối đa (COD)', 'cod_manual_verify_amount' => 'Đơn COD trên giá trị này phải đối soát thủ công (VNĐ)']],
                'finance'   => ['title' => 'Tài chính & khuyến mãi', 'fields' => ['point_rate' => 'Số VNĐ để tích 1 điểm', 'point_value' => 'Giá trị 1 điểm khi khách dùng (VNĐ)', 'cod_rules' => 'Quy định COD (logic chống bùng)']],
                'contact'   => ['title' => 'Thông tin liên hệ',      'fields' => ['hotline' => 'Hotline', 'shop_email' => 'Email cửa hàng', 'shop_address' => 'Địa chỉ cửa hàng', 'site_phone' => 'SĐT hiển thị nút gọi nổi', 'site_zalo' => 'Số/ID Zalo (dùng cho link zalo.me)', 'site_facebook' => 'Link Facebook Messenger']],
                'smtp'      => ['title' => 'SMTP - Gửi email (PHPMailer)', 'fields' => ['smtp_host' => 'SMTP Host (vd: smtp.gmail.com)', 'smtp_port' => 'SMTP Port (vd: 587)', 'smtp_user' => 'SMTP Username (email gửi)', 'smtp_pass' => 'SMTP Password / App Password', 'smtp_secure' => 'Bảo mật (tls / ssl / none)'], 'status' => $statusOf('smtp'), 'test' => true],
                'sms'       => ['title' => 'SMS - Gửi mã OTP (eSMS)', 'fields' => ['sms_enabled' => 'Bật gửi SMS thật (1 = bật, 0 = mô phỏng ghi log)', 'sms_provider' => 'Nhà cung cấp (esms)', 'esms_api_key' => 'eSMS API Key', 'esms_secret' => 'eSMS Secret Key', 'esms_brandname' => 'Brandname (VD: WoodCon)', 'esms_sms_type' => 'SmsType (2 = quảng cáo / 5 = CSKH)', 'otp_resend_limit_per_hour' => 'Giới hạn gửi lại OTP/giờ mỗi SĐT'], 'status' => $statusOf('sms'), 'test' => true],
                'invoice'   => ['title' => 'Hóa đơn (in cho khách)', 'fields' => ['invoice_company_name' => 'Tên công ty / cửa hàng', 'invoice_tax_code' => 'Mã số thuế (MST)', 'invoice_address' => 'Địa chỉ xuất hóa đơn', 'invoice_phone' => 'Số điện thoại', 'invoice_email' => 'Email', 'invoice_number_prefix' => 'Tiền tố số hóa đơn (VD: HD)', 'invoice_footer' => 'Chân trang hóa đơn', 'invoice_legal_note' => 'Ghi chú pháp lý (hóa đơn nội bộ)']],
                'ai'        => ['title' => 'AI Chatbot (Gemini)',    'fields' => ['ai_api_key' => 'Google API Key (Gemini)', 'ai_models' => 'Danh sách model (phẩy phân cách, ưu tiên từ trái sang phải)', 'ai_provider' => 'Provider hiện tại (gemini)'], 'status' => $statusOf('ai'), 'test' => true],
                'maps'      => ['title' => 'Bản đồ & gợi ý địa chỉ (Goong Maps)', 'fields' => [
                                        'goong_api_key' => 'Goong API Key (dùng chung Place AutoComplete / Place Detail / Geocode)',
                                        'goong_autocomplete_enabled' => ['label' => 'Bật gợi ý địa chỉ tự động', 'type' => 'toggle'],
                                        'goong_location_lat' => 'Tọa độ ưu tiên gợi ý - latitude (VD: 21.028511), để trống nếu không dùng',
                                        'goong_location_lng' => 'Tọa độ ưu tiên gợi ý - longitude (VD: 105.804817), để trống nếu không dùng',
                                ],
                                'status' => $statusOf('maps'),
                                'test' => true,
            ],
            ],
            'active' => 'settings',
            'activeSub' => 'settings',
        ]);
    }

    // ==================== NHÂN SỰ & PHÂN QUYỀN ====================

    /** Quản lý tài khoản Nhân viên (chỉ Chủ hệ thống / Nhân sự có quyền staffs.view). */
    private function staffs(string $mode): void
    {
        // ---- Hành động POST ----
        if ($mode === 'block' && $this->isPost() && isset($_POST['id'])) {
            Permission::require('staffs', 'edit');
            $id = (int)$this->post('id');
            if ($id === (int)($_SESSION['admin_id'] ?? 0)) {
                json_response(['ok' => false, 'message' => 'Không thể tự khóa tài khoản đang đăng nhập.'], 400);
            }
            $admin = Staff::find($id);
            if ($admin && ($admin['role'] ?? '') === 'superadmin') {
                json_response(['ok' => false, 'message' => 'Không thể khóa tài khoản Chủ hệ thống.'], 400);
            }
            Staff::blockStaff($id);
            AuditLogger::log(AuditLogger::A_LOCK, 'Staffs', 'Khóa tài khoản nhân viên ' . ($admin['name'] ?? '#' . $id), admin_id());
            write_log('staff', 'Khóa tài khoản #' . $id, admin_id());
            json_response(['ok' => true, 'message' => 'Đã khóa tài khoản. Nhân viên sẽ bị ngắt khỏi hệ thống.']);
        }
        if ($mode === 'unblock' && $this->isPost() && isset($_POST['id'])) {
            Permission::require('staffs', 'edit');
            $id = (int)$this->post('id');
            $admin = Staff::find($id);
            Staff::unblockStaff($id);
            AuditLogger::log(AuditLogger::A_UNLOCK, 'Staffs', 'Mở khóa tài khoản nhân viên ' . ($admin['name'] ?? '#' . $id), admin_id());
            json_response(['ok' => true, 'message' => 'Đã mở khóa tài khoản.']);
        }
        if ($mode === 'delete' && $this->isPost() && isset($_POST['id'])) {
            Permission::requireOwner(); // vùng cấm: chỉ Chủ hệ thống
            $id = (int)$this->post('id');
            if ($id === (int)($_SESSION['admin_id'] ?? 0)) {
                json_response(['ok' => false, 'message' => 'Không thể tự xóa tài khoản đang đăng nhập.'], 400);
            }
            $admin = Staff::find($id);
            if ($admin && ($admin['role'] ?? '') === 'superadmin') {
                json_response(['ok' => false, 'message' => 'Không thể xóa tài khoản Chủ hệ thống.'], 400);
            }
            Staff::softDeleteStaff($id); // giữ nguyên lịch sử
            AuditLogger::log(AuditLogger::A_DELETE, 'Staffs', 'Soft-delete tài khoản nhân viên ' . ($admin['name'] ?? '#' . $id), admin_id());
            json_response(['ok' => true, 'message' => 'Đã xóa tài khoản (soft delete). Lịch sử thao tác vẫn được giữ.']);
        }
        if ($mode === 'create' && $this->isPost()) {
            Permission::require('staffs', 'add');
            if (!verify_csrf($this->post('_token'))) {
                json_response(['ok' => false, 'message' => 'Phiên hết hạn.'], 400);
            }
            $name  = trim((string)$this->post('name', ''));
            $email = trim((string)$this->post('email', ''));
            $phone = trim((string)$this->post('phone', ''));
            $pass  = (string)$this->post('password', '');
            $roleId = $this->post('role_id', '');
            if ($name === '' || $email === '' || $pass === '') {
                json_response(['ok' => false, 'message' => 'Vui lòng nhập đầy đủ Họ tên, Email và Mật khẩu.'], 422);
            }
            if (strlen($pass) < 6) {
                json_response(['ok' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự.'], 422);
            }
            if (Staff::findBy('email', strtolower($email))) {
                json_response(['ok' => false, 'message' => 'Email này đã được sử dụng.'], 422);
            }
            $staff = Staff::createStaff([
                'name' => $name, 'email' => $email, 'phone' => $phone,
                'role_id' => $roleId !== '' ? (int)$roleId : null, 'status' => 1,
            ], $pass);
            if (!$staff) {
                json_response(['ok' => false, 'message' => 'Không thể tạo tài khoản.'], 500);
            }
            AuditLogger::log(AuditLogger::A_CREATE, 'Staffs', 'Tạo tài khoản nhân viên ' . $name . ' (' . $email . ')', admin_id());
            write_log('staff', 'Tạo tài khoản nhân viên ' . $email, admin_id());
            json_response(['ok' => true, 'message' => 'Đã tạo tài khoản nhân viên ' . $name . '.']);
        }

        // ---- Danh sách ----
        Permission::require('staffs', 'view');
        $f = [
            'q' => trim((string)$this->get('q', '')),
            'page' => max(1, (int)$this->get('page', 1)),
            'include_deleted' => (string)$this->get('include_deleted', '') === '1',
        ];
        $data = Staff::listFiltered($f);
        $this->adminRender('staffs', [
            'pageTitle' => 'Quản lý Nhân sự - WoodCon Admin',
            'rows'      => $data['rows'],
            'pager'     => $data['pager'],
            'summary'   => ['total' => $data['total']],
            'roles'     => Permission::rolesWithCount(),
            'allPerms'  => Permission::allGrouped(),
            'f'         => $f,
            'active'    => 'nhan-su',
            'activeSub' => 'nhan-su',
        ]);
    }

    /** Ma trận phân quyền theo Chức vụ (Check/Uncheck). */
    private function roles(string $mode): void
    {
        if ($mode === 'luu' && $this->isPost()) {
            Permission::require('roles', 'edit');
            if (!verify_csrf($this->post('_token'))) {
                json_response(['ok' => false, 'message' => 'Phiên hết hạn.'], 400);
            }
            $roleId = (int)$this->post('role_id');
            $role = Staff::first('SELECT * FROM roles WHERE id = ?', [$roleId]);
            if (!$role) {
                json_response(['ok' => false, 'message' => 'Không tìm thấy Chức vụ.'], 400);
            }
            // Roles hệ thống (superadmin) không được chỉnh quyền (kernel luôn toàn quyền)
            if ((int)$role['is_system'] === 1 && $role['code'] === 'superadmin') {
                json_response(['ok' => false, 'message' => 'Chức vụ Chủ hệ thống luôn có toàn quyền.'], 400);
            }
            $permIds = array_map('intval', (array)$this->post('perms', []));
            Staff::setRolePermissions($roleId, $permIds);
            AuditLogger::log(AuditLogger::A_UPDATE, 'Roles', 'Cập nhật phân quyền cho Chức vụ ' . $role['name'], admin_id());
            json_response(['ok' => true, 'message' => 'Đã lưu phân quyền cho Chức vụ ' . $role['name'] . '.']);
        }

        Permission::require('roles', 'view');
        $groups = Permission::allGrouped();
        $roleRows = Permission::rolesWithCount();
        $rolePerms = [];
        foreach ($roleRows as $rr) {
            $rolePerms[(int)$rr['id']] = Permission::rolePermissionIds((int)$rr['id']);
        }
        $this->adminRender('roles', [
            'pageTitle' => 'Phân quyền Chức vụ - WoodCon Admin',
            'groups'    => $groups,
            'roles'     => $roleRows,
            'rolePerms' => $rolePerms,
            'active'    => 'phan-quyen',
            'activeSub' => 'phan-quyen',
        ]);
    }

    /** Nhật ký giám sát (audit logs) — đọc-only, không có Xóa/Sửa. */
    private function auditLogs(string $mode): void
    {
        Permission::requireOwner(); // chỉ Chủ hệ thống

        // Xử lý reset riêng (giữ nguyên lọc khác)
        if ($mode === 'reset') {
            redirect(BASE_URL . '/quan-tri/nhat-ky');
        }

        $f = [
            'q'      => trim((string)$this->get('q', '')),
            'role'   => trim((string)$this->get('role', '')),
            'action' => trim((string)$this->get('action', '')),
            'module' => trim((string)$this->get('module', '')),
            'from'   => trim((string)$this->get('from', '')),
            'to'     => trim((string)$this->get('to', '')),
            'page'   => max(1, (int)$this->get('page', 1)),
        ];

        $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
        $data = AuditLogger::listFiltered($f);
        if ($isAjax) {
            $htmlRows = '';
            foreach ($data['rows'] as $log) {
                $htmlRows .= $this->auditLogRow($log);
            }
            $pagerHtml = '';
            if (!empty($data['pager']) && (int)($data['pager']['total_pages'] ?? 0) > 1) {
                $pager = $data['pager'];
                ob_start();
                include BASE_PATH . '/includes/partials/pagination.php';
                $pagerHtml = ob_get_clean();
            }
            json_response([
                'ok' => true,
                'rows' => $htmlRows,
                'total' => $data['total'],
                'pager' => $pagerHtml,
            ]);
        }

        $this->adminRender('audit_logs', [
            'pageTitle' => 'Giám sát Hệ thống - WoodCon Admin',
            'rows'      => $data['rows'],
            'pager'     => $data['pager'],
            'total'     => $data['total'],
            'f'         => $f,
            'actions'   => AuditLogger::actions(),
            'modules'   => AuditLogger::modules(),
            'active'    => 'nhat-ky',
            'activeSub' => 'nhat-ky',
        ]);
    }

    // ==================== HELPERS ====================

    /** Render 1 dòng nhật ký (dùng chung cho AJAX filter). */
    private function auditLogRow(array $log): string
    {
        $badge = match ($log['action'] ?? '') {
            'CREATE'      => 'badge bg-success',
            'UPDATE'      => 'badge bg-primary',
            'DELETE'      => 'badge bg-danger',
            'HARD_DELETE' => 'badge bg-dark',
            'EXPORT'      => 'badge bg-info',
            'LOGIN'       => 'badge bg-secondary',
            'LOCK'        => 'badge bg-warning text-dark',
            'UNLOCK'      => 'badge bg-success',
            default       => 'badge bg-secondary',
        };
        $time = format_date($log['created_at'] ?? '', 'd/m/Y H:i:s');
        return '<tr>'
            . '<td>' . (int)$log['id'] . '</td>'
            . '<td><div class="fw-semibold">' . e($log['user_name'] ?? '') . '</div><div class="small text-muted">ID ' . (int)($log['user_id'] ?? 0) . '</div></td>'
            . '<td>' . e($log['role_name'] ?? '') . '</td>'
            . '<td><span class="' . $badge . '">' . e(AuditLogger::actionLabel($log['action'] ?? '')) . '</span></td>'
            . '<td>' . e(AuditLogger::moduleLabel($log['module'] ?? '')) . '</td>'
            . '<td class="text-truncate" style="max-width:340px">' . e($log['description'] ?? '') . '</td>'
            . '<td class="text-muted small">' . e($log['ip_address'] ?? '-') . '</td>'
            . '<td class="text-nowrap">' . $time . '</td>'
            . '</tr>';
    }

    private function confirmThen(string $verb): void
    {
        write_log('admin', 'Hành động ' . $verb, admin_id());
    }

    /** Phân trang cho danh sách đã fetch toàn bộ trong bộ nhớ (phục vụ các bảng nhỏ) */
    private function paginateRows(array $rows, int $perPage = 15): array
    {
        $total = count($rows);
        $pager = paginate($total, $perPage, max(1, (int)$this->get('page', 1)));
        return ['rows' => array_values(array_slice($rows, $pager['offset'], $perPage)), 'pager' => $pager];
    }

    /** Render layout admin (sidebar + topbar), tách khỏi header/footer khách */
    protected function adminRender(string $view, array $props = []): void
    {
        extract($props, EXTR_SKIP);
        $pageTitle = $pageTitle ?? 'WoodCon Admin';
        $admin = Admin::current();
        require BASE_PATH . '/admin/includes/header.php';
        require BASE_PATH . '/admin/views/' . $view . '.php';
        require BASE_PATH . '/admin/includes/footer.php';
        exit;
    }
}
