<?php
/**
 * WoodCon - Permission (RBAC Middleware)
 * Kiểm tra quyền truy cập theo Chức vụ (role) + quyền (permission).
 *
 * - Chủ hệ thống (superadmin) luôn được cấp MỌI quyền (Owner bypass).
 * - Nhân viên thông thường chỉ được phép khi có mapping trong role_permissions.
 * - Các chức năng "vùng cấm" (nhạy cảm cấp cao) được chặn tuyệt đối — xem ownerOnly().
 */

declare(strict_types=1);

namespace WoodCon;

class Permission extends Base
{
    protected static string $table = 'permissions';

    /** Các chức năng CẤM tuyệt đối đối với nhân viên (chỉ Chủ hệ thống). */
    private const OWNER_ONLY_MODULES = [
        'settings',   // Cấu hình hệ thống
        'audit',      // Xem nhật ký giám sát
    ];
    private const OWNER_ONLY_ACTIONS = [
        'staffs.delete',       // Xóa tài khoản nhân viên
        'orders.harddelete',   // Hard Delete đơn hủy
    ];

    /** Lấy role hiện tại: ưu tiên role_id, fallback về cột role enum. */
    public static function currentRole(): ?array
    {
        $id = $_SESSION['admin_id'] ?? null;
        if (!$id) {
            return null;
        }
        $admin = static::first(
            "SELECT a.*, r.id AS rid, r.code AS role_code, r.name AS role_name
             FROM admins a
             LEFT JOIN roles r ON r.id = a.role_id
             WHERE a.id = ? LIMIT 1",
            [(int)$id]
        );
        if (!$admin) {
            return null;
        }
        // Fallback: nếu chưa gắn role_id thì dựa trên cột role enum
        if (empty($admin['rid'])) {
            $code = $admin['role'] === 'superadmin' ? 'superadmin' : ($admin['role'] === 'admin' ? 'superadmin' : 'employee');
            $row = static::first('SELECT id, code, name FROM roles WHERE code = ? LIMIT 1', [$code]);
            return $row ?: ['id' => null, 'code' => $code, 'name' => $code];
        }
        return [
            'id'        => (int)$admin['rid'],
            'code'      => $admin['role_code'],
            'name'      => $admin['role_name'],
            'is_system' => (int)($admin['role'] === 'superadmin'),
        ];
    }

    public static function isSuper(): bool
    {
        return ($_SESSION['admin_role'] ?? '') === 'superadmin';
    }

    public static function currentPermissionIds(): array
    {
        $role = static::currentRole();
        if (!$role || empty($role['id'])) {
            return [];
        }
        $rows = static::query(
            'SELECT rp.permission_id FROM role_permissions rp WHERE rp.role_id = ?',
            [(int)$role['id']]
        );
        return array_map(fn($r) => (int)$r['permission_id'], $rows);
    }

    /** Kiểm tra nhân viên có quyền (module.action) không. Chủ hệ thống luôn true. */
    public static function allows(string $module, string $action): bool
    {
        if (static::isSuper()) {
            return true;
        }
        // Quyền NHẠY CẢM chỉ thuộc Chủ hệ thống — nhân viên không bao giờ được dùng
        $stmt = static::db()->prepare(
            'SELECT is_sensitive FROM permissions WHERE module = ? AND action = ? LIMIT 1'
        );
        $stmt->execute([$module, $action]);
        $p = $stmt->fetch();
        if (!$p || (int)$p['is_sensitive'] === 1) {
            return false;
        }
        $role = static::currentRole();
        if (!$role || empty($role['id'])) {
            return false;
        }
        $stmt = static::db()->prepare(
            'SELECT 1 FROM role_permissions rp
             JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = ? AND p.module = ? AND p.action = ? LIMIT 1'
        );
        $stmt->execute([(int)$role['id'], $module, $action]);
        return (bool)$stmt->fetch();
    }

    /** Guard: chặn nếu không có quyền (đứng đầu các route). */
    public static function require(string $module, string $action): void
    {
        if (!static::allows($module, $action)) {
            if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
                json_response(['ok' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.'], 403);
            }
            set_flash('error', 'Bạn không có quyền truy cập chức năng này.');
            redirect(BASE_URL . '/quan-tri');
        }
    }

    /** Guard vùng cấm: chỉ Chủ hệ thống được thao tác (Hard Delete, Cấu hình, Xóa tài khoản chủ...). */
    public static function requireOwner(): void
    {
        if (!static::isSuper()) {
            if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
                json_response(['ok' => false, 'message' => 'Chỉ Chủ hệ thống mới được thực hiện thao tác này.'], 403);
            }
            set_flash('error', 'Chỉ Chủ hệ thống mới được thực hiện thao tác này.');
            redirect(BASE_URL . '/quan-tri');
        }
    }

    /** Liệt kê toàn bộ quyền nhóm theo module (KHÔNG gồm quyền nhạy cảm — chỉ Chủ hệ thống). */
    public static function allGrouped(bool $includeSensitive = false): array
    {
        $sql = 'SELECT * FROM permissions';
        if (!$includeSensitive) {
            $sql .= ' WHERE is_sensitive = 0';
        }
        $sql .= ' ORDER BY module, id';
        $perms = static::query($sql);
        $group = [];
        foreach ($perms as $p) {
            $group[$p['module']][] = $p;
        }
        return $group;
    }

    /** Mọi roles + số thành viên. */
    public static function rolesWithCount(): array
    {
        return static::query(
            "SELECT r.*, (SELECT COUNT(*) FROM admins a WHERE a.role_id = r.id AND a.is_deleted = 0) AS member_count
             FROM roles r ORDER BY r.is_system DESC, r.id"
        );
    }

    /** Lấy danh sách permission_id của một role. */
    public static function rolePermissionIds(int $roleId): array
    {
        $rows = static::query(
            'SELECT permission_id FROM role_permissions WHERE role_id = ?',
            [$roleId]
        );
        return array_map(fn($r) => (int)$r['permission_id'], $rows);
    }
}
