<?php
/**
 * WoodCon - Staff (Quản lý Nhân sự & RBAC)
 * Quản lý tài khoản admin/staff: tạo, khóa, soft-delete, phân quyền, và
 * xử lý Hard Delete đơn hàng đã hủy (chỉ Chủ hệ thống) trong MySQL Transaction.
 */

declare(strict_types=1);

namespace WoodCon;

class Staff extends Base
{
    protected static string $table = 'admins';

    /** Phân quyền mapping theo role (seed khi chưa gắn role_id). */
    private const ROLE_ENUM_MAP = [
        'superadmin' => 'superadmin',
        'admin'      => 'superadmin', // admin enum cũ ≈ chủ hệ thống
        'employee'   => 'employee',
    ];

    /** Kiểm tra tài khoản còn dùng được (không bị khóa / soft-delete). */
    public static function isActiveAccount(array $admin): bool
    {
        return (int)($admin['status'] ?? 1) === 1 && (int)($admin['is_deleted'] ?? 0) === 0;
    }

    /** Danh sách tài khoản nhân viên (mặc định ẩn tài khoản đã soft-delete). */
    public static function listFiltered(array $f = []): array
    {
        $where  = ['1=1'];
        $params = [];
        if (empty($f['include_deleted'])) {
            $where[] = 'is_deleted = 0';
        }
        if (!empty($f['q'])) {
            $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
            $like = '%' . $f['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        $whereStr = implode(' AND ', $where);

        $stmt = static::db()->prepare("SELECT COUNT(*) AS c FROM admins WHERE {$whereStr}");
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['c'];

        $perPage = (int)($f['per_page'] ?? 15);
        $pager = paginate($total, $perPage, max(1, (int)($f['page'] ?? 1)));

        $rows = static::query(
            "SELECT a.*, r.name AS role_name, r.code AS role_code,
                    COALESCE((SELECT COUNT(*) FROM audit_logs al WHERE al.user_id = a.id),0) AS log_count
             FROM admins a
             LEFT JOIN roles r ON r.id = a.role_id
             WHERE {$whereStr}
             ORDER BY a.id DESC LIMIT {$perPage} OFFSET {$pager['offset']}",
            $params
        );
        return ['rows' => $rows, 'total' => $total, 'pager' => $pager];
    }

    /** Tạo tài khoản nhân viên (mật khẩu mã hóa Bcrypt giống cấu hình). */
    public static function createStaff(array $d, string $rawPassword): ?array
    {
        $email = strtolower(trim((string)($d['email'] ?? '')));
        if (static::findBy('email', $email)) {
            return null; // trùng email
        }
        $id = static::insert([
            'name'     => trim((string)($d['name'] ?? '')),
            'email'    => $email,
            'phone'    => trim((string)($d['phone'] ?? '')),
            'password' => password_hash($rawPassword, PASSWORD_BCRYPT),
            'role'     => 'employee',
            'role_id'  => isset($d['role_id']) && $d['role_id'] !== '' ? (int)$d['role_id'] : null,
            'status'   => (int)($d['status'] ?? 1),
            'is_deleted' => 0,
        ]);
        if (!$id) {
            return null;
        }
        return static::find($id);
    }

    /** Khóa tài khoản (status=0) — lần đăng nhập tới sẽ bị chặn. */
    public static function blockStaff(int $id): bool
    {
        return static::update($id, ['status' => 0]);
    }

    /** Mở khóa tài khoản. */
    public static function unblockStaff(int $id): bool
    {
        return static::update($id, ['status' => 1]);
    }

    /** Soft Delete tài khoản (is_deleted=1) — giữ nguyên lịch sử thao tác. */
    public static function softDeleteStaff(int $id): bool
    {
        return static::update($id, ['is_deleted' => 1, 'status' => 0]);
    }

    /** Thay thế toàn bộ quyền của một role (modal checkbox ma trận). */
    public static function setRolePermissions(int $roleId, array $permissionIds): bool
    {
        $db = static::db();
        $db->beginTransaction();
        try {
            $db->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$roleId]);
            $ins = $db->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
            foreach (array_unique(array_map('intval', $permissionIds)) as $pid) {
                $ins->execute([$roleId, $pid]);
            }
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            return false;
        }
    }

    /**
     * HARD DELETE đơn hàng đã hủy (Chỉ Chủ hệ thống).
     * An toàn: Transaction + LOCK FOR UPDATE + kiểm tra cancelled & không có invoice.
     */
    public static function hardDeleteCancelledOrder(int $orderId, int $actingAdminId): array
    {
        $db = static::db();
        $msg = '';

        $db->beginTransaction();
        try {
            // Step 1: LOCK FOR UPDATE đơn + kiểm tra điều kiện xóa
            $order = static::first(
                'SELECT * FROM orders WHERE id = ? FOR UPDATE',
                [$orderId]
            );
            if (!$order) {
                $db->rollBack();
                return ['ok' => false, 'message' => 'Không tìm thấy đơn hàng.'];
            }
            if (($order['order_status'] ?? '') !== 'cancelled') {
                $db->rollBack();
                return ['ok' => false, 'message' => 'Chỉ được xóa đơn có trạng thái HỦY.'];
            }
            $invoice = static::first(
                'SELECT 1 FROM invoices WHERE order_id = ? LIMIT 1',
                [$orderId]
            );
            if ($invoice) {
                $db->rollBack();
                return ['ok' => false, 'message' => 'Đơn này đã có hóa đơn, CẤM xóa tuyệt đối.'];
            }

            // Step 2: Lưu Snapshot JSON vào deleted_order_logs (bảng độc lập, không FK)
            $orderItems = static::query('SELECT * FROM order_items WHERE order_id = ?', [$orderId]);
            $snapshot = [
                'order'  => $order,
                'items'  => $orderItems,
                'refunds' => static::query('SELECT * FROM refunds WHERE order_id = ?', [$orderId]),
            ];
            $actor = static::first('SELECT name FROM admins WHERE id = ?', [$actingAdminId]);
            $db->prepare(
                'INSERT INTO deleted_order_logs (order_id, order_code, snapshot_json, deleted_by, deleted_by_name)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([
                $orderId,
                $order['order_code'],
                json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                $actingAdminId,
                $actor['name'] ?? null,
            ]);

            // Step 3: Xóa cascade từ con -> cha (tránh lỗi FK 1451)
            $db->prepare('DELETE wh FROM warranty_history wh
                          JOIN warranties w ON w.id = wh.warranty_id
                          WHERE w.order_id = ?')->execute([$orderId]);
            $db->prepare('DELETE FROM warranties WHERE order_id = ?')->execute([$orderId]);
            $db->prepare('DELETE FROM order_cancel_requests WHERE order_id = ?')->execute([$orderId]);
            $db->prepare('DELETE FROM refunds WHERE order_id = ?')->execute([$orderId]);
            $db->prepare('DELETE FROM order_items WHERE order_id = ?')->execute([$orderId]);
            $size = $db->prepare('DELETE FROM orders WHERE id = ?');
            $size->execute([$orderId]);

            // Step 4: Commit + ghi log
            $db->commit();
            AuditLogger::log(
                AuditLogger::A_HARDDELETE,
                'Orders',
                'Hard Delete đơn ' . $order['order_code'] . ' (ID ' . $orderId . ') đã hủy',
                $actingAdminId
            );
            return ['ok' => true, 'message' => 'Đã xóa cứng đơn ' . $order['order_code'] . ' và toàn bộ dữ liệu liên quan.'];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['ok' => false, 'message' => 'Lỗi xóa đơn: ' . $e->getMessage()];
        }
    }

    /** Hard Delete cho phép xóa đơn hủy? (kiểm tra nhanh điều kiện) */
    public static function canHardDelete(int $orderId): array
    {
        $order = static::first('SELECT * FROM orders WHERE id = ?', [$orderId]);
        if (!$order) {
            return ['ok' => false, 'message' => 'Không tìm thấy đơn.'];
        }
        if (($order['order_status'] ?? '') !== 'cancelled') {
            return ['ok' => false, 'message' => 'Chỉ đơn HỦY mới xóa được.'];
        }
        $invoice = static::first('SELECT 1 FROM invoices WHERE order_id = ? LIMIT 1', [$orderId]);
        if ($invoice) {
            return ['ok' => false, 'message' => 'Đơn đã có hóa đơn — CẤM xóa.'];
        }
        return ['ok' => true];
    }
}
