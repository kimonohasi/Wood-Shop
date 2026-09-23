<?php
/**
 * WoodCon - AuditLogger
 * Nhật ký giám sát (immutable): mọi thao tác CREATE/UPDATE/DELETE/EXPORT/LOGIN/LOCK
 * của nhân viên được ghi tự động qua AuditLogger::log(). Không có cơ chế xóa/sửa.
 */

declare(strict_types=1);

namespace WoodCon;

class AuditLogger extends Base
{
    protected static string $table = 'audit_logs';

    public const A_CREATE = 'CREATE';
    public const A_UPDATE = 'UPDATE';
    public const A_DELETE = 'DELETE';
    public const A_EXPORT = 'EXPORT';
    public const A_LOGIN  = 'LOGIN';
    public const A_LOGOUT = 'LOGOUT';
    public const A_LOCK   = 'LOCK';
    public const A_UNLOCK = 'UNLOCK';
    public const A_HARDDELETE = 'HARD_DELETE';

    /**
     * Ghi nhật ký giám sát.
     *
     * @param string $action      CREATE|UPDATE|DELETE|EXPORT|LOGIN|LOCK|HARD_DELETE...
     * @param string $module      Orders, Products, Staffs, Invoices...
     * @param string $description Chi tiết hành động
     */
    public static function log(string $action, string $module, string $description, ?int $userId = null, bool $bootstrap = false): bool
    {
        // Nếu không truyền user_id thì lấy từ session (trường hợp có admin đang đăng nhập)
        if ($userId === null) {
            $userId = $_SESSION['admin_id'] ?? null;
        }
        if ($userId === null && !$bootstrap) {
            return false;
        }

        $userName  = $_SESSION['admin_name'] ?? '';
        $roleCode  = $_SESSION['admin_role'] ?? '';
        $roleName  = $roleCode;

        // Nạp vai trò hiển thị (Chủ hệ thống, Kế toán...) nếu có
        if ($userId) {
            try {
                $admin = static::first(
                    'SELECT a.name, a.role, r.name AS role_name FROM admins a
                     LEFT JOIN roles r ON r.id = a.role_id WHERE a.id = ? LIMIT 1',
                    [(int)$userId]
                );
                if ($admin) {
                    $userName = $admin['name'];
                    $roleName = $admin['role_name'] ?: $admin['role'];
                }
            } catch (\Throwable $e) {
                // không làm hỏng luồng chính nếu tra cứu thất bại
            }
        }

        $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
        return (bool)static::insert([
            'user_id'     => $userId,
            'user_name'   => $userName,
            'role_name'   => $roleName,
            'action'      => $action,
            'module'      => $module,
            'description' => $description,
            'ip_address'  => $ip,
        ]);
    }

    /**
     * Danh sách nhật ký giám sát có lọc + phân trang.
     * filter: q, action, module, from, to, page
     */
    public static function listFiltered(array $f = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($f['q'])) {
            $where[] = '(user_name LIKE ? OR user_id = ? OR description LIKE ?)';
            $like  = '%' . $f['q'] . '%';
            $uid   = ctype_digit((string)$f['q']) ? (int)$f['q'] : -1;
            array_push($params, $like, $uid, $like);
        }
        if (!empty($f['role'])) {
            $where[] = 'role_name = ?';
            $params[] = $f['role'];
        }
        if (!empty($f['action'])) {
            $where[] = 'action = ?';
            $params[] = $f['action'];
        }
        if (!empty($f['module'])) {
            $where[] = 'module = ?';
            $params[] = $f['module'];
        }
        if (!empty($f['from']) && !empty($f['to'])) {
            $where[] = 'created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY) - INTERVAL 1 SECOND';
            array_push($params, $f['from'] . ' 00:00:00', $f['to']);
        }
        $whereStr = implode(' AND ', $where);

        $stmt = static::db()->prepare("SELECT COUNT(*) AS c FROM audit_logs WHERE {$whereStr}");
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['c'];

        $perPage = (int)($f['per_page'] ?? 15);
        $pager = paginate($total, $perPage, max(1, (int)($f['page'] ?? 1)));

        $rows = static::query(
            "SELECT * FROM audit_logs WHERE {$whereStr}
             ORDER BY id DESC LIMIT {$perPage} OFFSET {$pager['offset']}",
            $params
        );
        return ['rows' => $rows, 'total' => $total, 'pager' => $pager];
    }

    /** Phân bổ module -> nhãn hiển thị (cho filter dropdown) */
    public static function modules(): array
    {
        return array_column(static::query('SELECT DISTINCT module FROM audit_logs WHERE module IS NOT NULL ORDER BY module'), 'module');
    }

    public static function actions(): array
    {
        return array_column(static::query('SELECT DISTINCT action FROM audit_logs ORDER BY action'), 'action');
    }

    /**
     * Map action -> nhãn tiếng Việt.
     * CHỈ dùng để hiển thị (dropdown, badge...); KHÔNG đổi giá trị lưu trong DB.
     * Giá trị chưa có trong map sẽ fallback hiển thị nguyên giá trị gốc.
     */
    public static function actionLabels(): array
    {
        return [
            'CREATE'      => 'Tạo mới',
            'UPDATE'      => 'Cập nhật',
            'DELETE'      => 'Xóa',
            'HARD_DELETE' => 'Xóa vĩnh viễn',
            'EXPORT'      => 'Xuất dữ liệu',
            'LOGIN'       => 'Đăng nhập',
            'LOGOUT'      => 'Đăng xuất',
            'LOCK'        => 'Khóa tài khoản',
            'UNLOCK'      => 'Mở khóa',
        ];
    }

    /**
     * Map module -> nhãn tiếng Việt.
     * CHỈ dùng để hiển thị; giá trị chưa có trong map sẽ hiển thị nguyên gốc.
     */
    public static function moduleLabels(): array
    {
        return [
            'Approvals'  => 'Yêu cầu duyệt',
            'Auth'       => 'Đăng nhập & bảo mật',
            'Invoices'   => 'Hóa đơn',
            'Orders'     => 'Đơn hàng',
            'Products'   => 'Sản phẩm',
            'Staffs'     => 'Nhân viên',
            'Taxes'      => 'Thuế',
            'Warranties' => 'Bảo hành',
        ];
    }

    /** Nhãn tiếng Việt của 1 action (fallback: giá trị gốc). */
    public static function actionLabel(string $value): string
    {
        return self::actionLabels()[$value] ?? $value;
    }

    /** Nhãn tiếng Việt của 1 module (fallback: giá trị gốc). */
    public static function moduleLabel(string $value): string
    {
        return self::moduleLabels()[$value] ?? $value;
    }
}
