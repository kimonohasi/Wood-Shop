<?php
/**
 * WoodCon - Model Quản trị viên
 * Cung cấp xác thực đăng nhập admin + thống kê dashboard.
 */

declare(strict_types=1);

namespace WoodCon;

class Admin extends Base
{
    protected static string $table = 'admins';

    /** Truy cập PDO (public để controller admin dùng chung) */
    public static function db(): \PDO
    {
        return parent::db();
    }

    /** Xác thực đăng nhập bằng email + mật khẩu */
    public static function attempt(string $email, string $password): ?array
    {
        $admin = static::first(
            'SELECT * FROM admins WHERE email = ? AND status = 1 AND is_deleted = 0 LIMIT 1',
            [trim($email)]
        );
        if (!$admin || !password_verify($password, $admin['password'])) {
            return null;
        }
        static::update((int)$admin['id'], ['last_login' => date('Y-m-d H:i:s')]);
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_role'] = $admin['role'];
        return $admin;
    }

    public static function current(): ?array
    {
        $id = $_SESSION['admin_id'] ?? null;
        if (!$id) {
            return null;
        }
        $admin = static::find((int)$id);
        // Session hết hiệu lực ngay nếu tài khoản bị KHÓA hoặc SOFT-DELETE
        if (!$admin || (int)$admin['status'] !== 1 || (int)($admin['is_deleted'] ?? 0) === 1) {
            unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email'], $_SESSION['admin_role']);
            return null;
        }
        return $admin;
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email'], $_SESSION['admin_role']);
    }

    public static function isSuper(): bool
    {
        return ($_SESSION['admin_role'] ?? '') === 'superadmin';
    }

    /** Dữ liệu dashboard: thống kê đơn + doanh thu + sản phẩm + khách + biểu đồ */
    public static function dashboard(): array
    {
        $db = static::db();

        $today = date('Y-m-d');
        $sum = static::query("SELECT
                (SELECT COUNT(*) FROM orders WHERE order_status='pending') AS pending_orders,
                (SELECT COUNT(*) FROM orders WHERE risk_flag=1) AS risk_orders,
                (SELECT COUNT(*) FROM contacts WHERE status='new') AS new_contacts,
                (SELECT COUNT(*) FROM products WHERE quantity <= 5) AS low_stock
            ")[0];

        // ---- Doanh thu: MỌI chỉ số gọi qua RevenueService (nguồn duy nhất) ----
        // Doanh thu hôm nay  = đơn GIAO THÀNH CÔNG hôm nay, nhận diện theo delivered_at
        //                       (total_amount; kể cả COD chưa thu tiền — không lọc payment_status).
        $sum['today_revenue'] = RevenueService::total('delivered', RevenueService::COL_DELIVERED, $today, $today);
        // Tổng doanh thu (đã giao) = tích lũy mọi đơn delivered mọi thời điểm.
        $sum['revenue']        = RevenueService::total('delivered', RevenueService::COL_DELIVERED);

        // ---- 4 thẻ KPI ưu tiên (Phần A — prompt v10) ----
        // Tổng thuế (VAT hóa đơn): cộng dồn VAT (sản phẩm + vận chuyển + lắp đặt) của những đơn
        // ĐÃ GIAO THÀNH CÔNG và CÓ HÓA ĐƠN bán (SALE_INVOICE). Tích lũy — khớp cách tính "Tổng doanh thu".
        $vatRow = static::query(
            "SELECT COALESCE(SUM(i.vat + i.vat_shipping_amount + i.vat_install_amount),0) AS total_vat
             FROM invoices i
             JOIN orders o ON o.id = i.order_id
             WHERE i.type = ? AND o.order_status = 'delivered'",
            [Invoice::TYPE_SALE]
        )[0];
        $sum['total_vat'] = (float)$vatRow['total_vat'];

        // Tổng sản phẩm đang Hoạt động (status=1 = hiển thị trên cửa hàng).
        // Chọn chỉ đếm sản phẩm Hoạt động để phản ánh đúng số sản phẩm khách có thể mua.
        $sum['active_products'] = (int)static::query("SELECT COUNT(*) AS c FROM products WHERE status = 1")[0]['c'];

        // Lợi nhuận ròng = Doanh thu đã giao − (Giá vốn sản phẩm đã bán + Phí vận chuyển + VAT đã nộp).
        // products.cost = Giá vốn đã có sẵn trong DB → tính được chính xác (không bịa số liệu).
        $costRow = static::query(
            "SELECT
                COALESCE(SUM(oi.quantity * COALESCE(p.cost, 0)), 0) AS cogs,
                COALESCE(SUM(o.shipping_fee), 0)                    AS ship_cost
             FROM orders o
             JOIN order_items oi ON oi.order_id = o.id
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE o.order_status = 'delivered'"
        )[0];
        $sum['cogs']       = (float)$costRow['cogs'];
        $sum['ship_cost']  = (float)$costRow['ship_cost'];
        $sum['net_profit'] = (float)$sum['revenue'] - $sum['cogs'] - $sum['ship_cost'] - $sum['total_vat'];

        // Tổng người dùng = số tài khoản KHÁCH HÀNG đã đăng ký (bảng users chỉ chứa khách hàng;
        // admin/nhân viên nội bộ nằm riêng ở bảng admins → không bị tính nhầm vào đây).
        $sum['customer_users'] = (int)static::query("SELECT COUNT(*) AS c FROM users WHERE status = 1")[0]['c'];

        // Thống kê bổ sung cho dashboard
        $sum['total_staff']      = (int)static::query("SELECT COUNT(*) AS c FROM admins WHERE is_deleted = 0")[0]['c'];
        $sum['total_orders']     = (int)static::query("SELECT COUNT(*) AS c FROM orders")[0]['c'];
        $sum['delivered_orders'] = (int)static::query("SELECT COUNT(*) AS c FROM orders WHERE order_status = 'delivered'")[0]['c'];
        $sum['receivable_amount'] = (float)static::query("SELECT COALESCE(SUM(total_amount),0) AS s FROM orders WHERE order_status = 'delivered'")[0]['s'];

        $orders = static::query("SELECT order_status, COUNT(*) AS c FROM orders GROUP BY order_status");
        $orderDist = [];
        foreach ($orders as $row) {
            $orderDist[$row['order_status']] = (int)$row['c'];
        }

        // Doanh thu 7 ngày gần nhất (đơn đã giao thành công, nhận diện theo ngày giao)
        // — chuỗi bucket liên tục để vẽ biểu đồ NGAY (không phụ thuộc AJAX).
        $revChart = RevenueService::buckets(
            'delivered', 'day', RevenueService::COL_DELIVERED,
            RevenueService::BUCKET_COUNTS['day']
        );

        // Top sản phẩm bán chạy
        $bestSellers = static::query(
            "SELECT p.id, p.name, p.cover_image, SUM(oi.quantity) AS sold
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             JOIN orders o ON o.id = oi.order_id
             WHERE o.order_status != 'cancelled' AND o.order_status != 'returned'
             GROUP BY p.id ORDER BY sold DESC LIMIT 5"
        );

        // Đơn mới nhất
        $recentOrders = static::query(
            "SELECT id, order_code, customer_name, total_amount, order_status, payment_method, created_at
             FROM orders ORDER BY created_at DESC LIMIT 8"
        );

        return [
            'sum'        => $sum,
            'orderDist'  => $orderDist,
            'revChart'   => $revChart,
            'bestSellers'=> $bestSellers,
            'recent'     => $recentOrders,
        ];
    }

    /** Thống kê nhanh cho trang quản lý sản phẩm (các thẻ card đầu trang) */
    public static function productStats(): array
    {
        $row = static::query("SELECT
                (SELECT COUNT(*) FROM products) AS total,
                (SELECT COUNT(*) FROM products WHERE status = 1) AS active,
                (SELECT COUNT(*) FROM products WHERE status = 0) AS hidden,
                (SELECT COUNT(*) FROM products WHERE is_best_seller = 1) AS best_seller,
                (SELECT COUNT(*) FROM products WHERE is_featured = 1) AS featured,
                (SELECT COUNT(*) FROM products WHERE is_new = 1) AS is_new,
                (SELECT COUNT(*) FROM products WHERE quantity > 0) AS in_stock,
                (SELECT COUNT(*) FROM products WHERE quantity <= 5) AS low_stock
            ")[0];

        return [
            'total'       => (int)$row['total'],
            'active'      => (int)$row['active'],
            'hidden'      => (int)$row['hidden'],
            'best_seller' => (int)$row['best_seller'],
            'featured'    => (int)$row['featured'],
            'is_new'      => (int)$row['is_new'],
            'in_stock'    => (int)$row['in_stock'],
            'low_stock'   => (int)$row['low_stock'],
        ];
    }

    /** Thống kê nhanh cho trang quản lý danh mục (các thẻ card đầu trang) */
    public static function categoryStats(): array
    {
        $row = static::query("SELECT
                (SELECT COUNT(*) FROM categories) AS total,
                (SELECT COUNT(*) FROM categories WHERE parent_id IS NOT NULL) AS child,
                (SELECT COUNT(*) FROM categories WHERE status = 1) AS active,
                (SELECT COUNT(*) FROM categories WHERE status = 0) AS inactive
            ")[0];

        return [
            'total'    => (int)$row['total'],
            'child'    => (int)$row['child'],
            'active'   => (int)$row['active'],
            'inactive' => (int)$row['inactive'],
        ];
    }

    /** Thống kê nhanh cho trang quản lý thương hiệu (các thẻ card đầu trang) */
    public static function brandStats(): array
    {
        $row = static::query("SELECT
                (SELECT COUNT(*) FROM brands) AS total,
                (SELECT COUNT(*) FROM brands WHERE status = 1) AS active,
                (SELECT COUNT(*) FROM brands WHERE status = 0) AS inactive
            ")[0];

        return [
            'total'    => (int)$row['total'],
            'active'   => (int)$row['active'],
            'inactive' => (int)$row['inactive'],
        ];
    }

    /** Thống kê nhanh cho trang quản lý đơn hàng (các thẻ card đầu trang) */
    public static function orderStats(): array
    {
        $byStatus = [];
        foreach (static::query('SELECT order_status, COUNT(*) AS c FROM orders GROUP BY order_status') as $row) {
            $byStatus[(string)$row['order_status']] = (int)$row['c'];
        }
        $cod = 0;
        foreach (static::query('SELECT payment_method, COUNT(*) AS c FROM orders GROUP BY payment_method') as $row) {
            if ((string)$row['payment_method'] === 'cod') {
                $cod = (int)$row['c'];
            }
        }
        $total = array_sum($byStatus);

        return [
            'total'    => $total,
            'status'   => $byStatus,
            'cod'      => $cod,
            'transfer' => $total - $cod,
        ];
    }

    /** Thống kê nhanh cho trang quản lý hóa đơn (các thẻ card đầu trang) */
    public static function invoiceStats(): array
    {
        $byType = [];
        foreach (static::query('SELECT type, COUNT(*) AS c FROM invoices GROUP BY type') as $row) {
            $byType[(string)$row['type']] = (int)$row['c'];
        }
        $total = array_sum($byType);

        return [
            'total'     => $total,
            'sale'      => $byType['SALE_INVOICE'] ?? 0,
            'adjust'    => $byType['REFUND_INVOICE'] ?? 0,
            'cancelled' => $byType['CANCELLED_INVOICE'] ?? 0,
        ];
    }

    /** Báo cáo tài chính theo khoảng thời gian, nhóm theo ngày */
    public static function report(string $from, string $to, string $group = 'day', string $mode = '1'): array
    {
        $from = $from ?: date('Y-m-01');
        $to = $to ?: date('Y-m-d');

        // Chế độ doanh thu — MỌI trạng thái/điều kiện định nghĩa DUY NHẤT tại RevenueService:
        //  '1' = Doanh thu THỰC: đơn GIAO THÀNH CÔNG, nhận diện theo delivered_at (mặc định).
        //  '3' = Doanh thu TIỀM NĂNG: mọi đơn chưa hủy/chưa trả (active), theo created_at.
        //  '2' = Kế toán/Hóa đơn: dùng riêng bảng invoices (block legal bên dưới).
        $revScope = 'delivered';
        $dateBy = RevenueService::COL_DELIVERED;
        if ($mode === '3') {
            $revScope = 'active';
            $dateBy = RevenueService::COL_CREATED;
        }

        [$scopeWhere, $scopeParams] = RevenueService::buildWhere($revScope, $dateBy, $from, $to);
        $groupExpr = RevenueService::groupExpr($group, $dateBy);

        // Tất cả cột (doanh thu, giá trị hàng, giảm giá, ship, VAT) dùng CÙNG scope + CÙNG cột mốc
        // → báo cáo thống nhất nội bộ, không lẫn trạng thái/ngày đặt với ngày giao.
        $rows = static::query(
            "SELECT {$groupExpr} AS period,
                    COUNT(*) AS orders,
                    COALESCE(SUM(total_amount),0) AS revenue,
                    COALESCE(SUM(subtotal),0)     AS goods_value,
                    COALESCE(SUM(discount_amount),0) AS discount,
                    COALESCE(SUM(shipping_fee),0)    AS shipping,
                    COALESCE(SUM(vat_amount),0)      AS vat
             FROM orders
             WHERE {$scopeWhere}
             GROUP BY period ORDER BY period",
            $scopeParams
        );

        $summary = [
            'orders'   => array_sum(array_column($rows, 'orders')),
            'revenue'  => array_sum(array_column($rows, 'revenue')),
            'goods'    => array_sum(array_column($rows, 'goods_value')),
            'discount' => array_sum(array_column($rows, 'discount')),
            'shipping' => array_sum(array_column($rows, 'shipping')),
            'vat'      => array_sum(array_column($rows, 'vat')),
        ];

        // Dạng 2 - Kế toán: tổng hợp từ mục Hóa đơn (chứng từ hợp lệ).
        $legal = \WoodCon\Invoice::taxReportByPeriod($from, $to, $group);

        // Cảnh báo rủi ro (NĐ 254/2026): đơn ĐÃ GIAO nhưng CHƯA có hóa đơn bán hợp lệ.
        $risk = ['orders' => 0, 'amount' => 0.0];
        if ($mode === '2') {
            $rr = static::query(
                "SELECT COUNT(*) AS cnt, COALESCE(SUM(o.total_amount),0) AS amount
                 FROM orders o
                 LEFT JOIN invoices i ON i.order_id = o.id AND i.type = ?
                 WHERE o.order_status = 'delivered'
                   AND o.delivered_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY) - INTERVAL 1 SECOND
                   AND i.id IS NULL",
                [\WoodCon\Invoice::TYPE_SALE, $from . ' 00:00:00', $to]
            )[0];
            $risk['orders'] = (int)($rr['cnt'] ?? 0);
            $risk['amount'] = (float)($rr['amount'] ?? 0);
        }

        return [
            'from'    => $from,
            'to'      => $to,
            'mode'    => $mode,
            'group'   => $group,
            'rows'    => $rows,
            'summary' => $summary,
            'legal'   => $legal,
            'risk'    => [
                'orders' => (int)($risk['orders'] ?? 0),
                'amount' => (float)($risk['amount'] ?? 0),
            ],
        ];
    }

    /** Danh sách đơn hàng theo bộ lọc cho admin (có phân trang) */
    public static function ordersFiltered(array $f): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($f['q'])) {
            $where[] = '(order_code LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)';
            $like = '%' . $f['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        if (!empty($f['status'])) {
            $where[] = 'order_status = ?';
            $params[] = $f['status'];
        }
        if (!empty($f['payment'])) {
            $where[] = 'payment_method = ?';
            $params[] = $f['payment'];
        }
        if (!empty($f['payment_status'])) {
            $where[] = 'payment_status = ?';
            $params[] = $f['payment_status'];
        }
        $whereStr = implode(' AND ', $where);

        $stmt = static::db()->prepare("SELECT COUNT(*) AS c FROM orders WHERE {$whereStr}");
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['c'];

        $perPage = (int)($f['per_page'] ?? 15);
        $pager = paginate($total, $perPage, max(1, (int)($f['page'] ?? 1)));

        $stmt = static::db()->prepare(
            "SELECT * FROM orders WHERE {$whereStr} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$pager['offset']}"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        return ['total' => $total, 'rows' => $rows, 'pager' => $pager];
    }

    public static function usersFiltered(array $f): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($f['q'])) {
            $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
            $like = '%' . $f['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        if (isset($f['status']) && ($f['status'] === '1' || $f['status'] === '0')) {
            $where[] = 'u.status = ?';
            $params[] = (int)$f['status'];
        }
        $whereStr = implode(' AND ', $where);

        $stmt = static::db()->prepare("SELECT COUNT(*) AS c FROM users WHERE {$whereStr}");
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['c'];

        $perPage = (int)($f['per_page'] ?? 15);
        $pager = paginate($total, $perPage, max(1, (int)($f['page'] ?? 1)));

        $sql = "SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count
                FROM users u WHERE {$whereStr} ORDER BY u.created_at DESC LIMIT {$perPage} OFFSET {$pager['offset']}";
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        return ['total' => $total, 'rows' => $rows, 'pager' => $pager];
    }
}
