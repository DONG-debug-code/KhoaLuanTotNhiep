<?php

namespace App\Services;

use App\Support\AccountRole;
use Illuminate\Support\Facades\DB;

/**
 * Quản trị tài khoản khách hàng, nhân viên và chuyên gia: khóa/mở khóa và đổi vai trò nhân viên.
 */
class AccountService
{
    public const TYPES = ['khach_hang', 'nhan_vien', 'chuyen_gia'];

    private const TABLES = ['khach_hang' => 'users', 'nhan_vien' => 'admins', 'chuyen_gia' => 'chuyen_gias'];
    private const TOKEN_TYPES = [
        'khach_hang' => 'App\\Models\\User',
        'nhan_vien' => 'App\\Models\\Admin',
        'chuyen_gia' => 'App\\Models\\ChuyenGia',
    ];
    private const ADMIN_ROLES = ['super_admin', 'executive'];
    private const CUSTOMER_ROLES = ['customer', 'vip_customer'];

    /** @return string|null thông báo lỗi nếu không thực hiện được */
    public function setActive(string $type, int $id, bool $active, ?object $actor, ?string $reason = null, ?string $ip = null): ?string
    {
        $account = DB::table(self::TABLES[$type])->where('id', $id)->first();
        if (!$account) {
            return 'Không tìm thấy tài khoản.';
        }
        if ((int) $account->trang_thai === ($active ? 1 : 0)) {
            return null;
        }

        if (!$active && $type === 'nhan_vien') {
            if ($actor && (int) $actor->id === $id) {
                return 'Bạn không thể tự khóa tài khoản của chính mình.';
            }
            if ($this->isSuperAdmin($account) && $this->activeSuperAdmins() <= 1) {
                return 'Không thể khóa quản trị viên cao cấp cuối cùng của hệ thống.';
            }
        }

        DB::transaction(function () use ($type, $id, $active, $actor, $reason, $ip) {
            DB::table(self::TABLES[$type])->where('id', $id)->update(['trang_thai' => $active ? 1 : 0, 'updated_at' => now()]);
            if (!$active) {
                $this->revokeTokens($type, $id);
            }
            $this->log($actor, $type, $id, $active ? 'mo_khoa' : 'khoa', $reason, null, $ip);
        });

        return null;
    }

    /** @return string|null thông báo lỗi nếu không thực hiện được */
    public function changeStaffRole(int $adminId, int $roleId, ?object $actor, ?string $ip = null): ?string
    {
        $account = DB::table('admins')->where('id', $adminId)->first();
        $role = DB::table('vai_tros')->where('id', $roleId)->first();
        if (!$account || !$role) {
            return 'Không tìm thấy nhân viên hoặc vai trò.';
        }
        if (in_array($role->ma_vai_tro, self::CUSTOMER_ROLES, true)) {
            return 'Không thể gán vai trò khách hàng cho nhân viên.';
        }
        if ($actor && (int) $actor->id === $adminId) {
            return 'Bạn không thể tự thay đổi vai trò của chính mình.';
        }

        // Tên miền email quyết định cổng đăng nhập: @porsche chỉ dành cho quản trị, @staff cho nhân viên.
        $isAdminDomain = AccountRole::fromEmail($account->email) === AccountRole::ADMIN;
        $isAdminRole = in_array($role->ma_vai_tro, self::ADMIN_ROLES, true);
        if ($isAdminDomain !== $isAdminRole) {
            return $isAdminDomain
                ? 'Tài khoản @porsche chỉ được gán vai trò quản trị (super_admin, executive).'
                : 'Tài khoản @staff không thể gán vai trò quản trị.';
        }

        $oldRole = DB::table('vai_tros')->where('id', $account->vai_tro_id)->first();
        if ($oldRole?->ma_vai_tro === 'super_admin' && $role->ma_vai_tro !== 'super_admin' && $this->activeSuperAdmins() <= 1) {
            return 'Không thể hạ quyền quản trị viên cao cấp cuối cùng của hệ thống.';
        }
        if ((int) $account->vai_tro_id === $roleId) {
            return null;
        }

        DB::transaction(function () use ($adminId, $roleId, $oldRole, $role, $actor, $ip) {
            DB::table('admins')->where('id', $adminId)->update(['vai_tro_id' => $roleId, 'updated_at' => now()]);
            $this->log($actor, 'nhan_vien', $adminId, 'doi_vai_tro', null, ['tu' => $oldRole?->ma_vai_tro, 'den' => $role->ma_vai_tro], $ip);
        });

        return null;
    }

    private function isSuperAdmin(object $admin): bool
    {
        return DB::table('vai_tros')->where('id', $admin->vai_tro_id)->value('ma_vai_tro') === 'super_admin';
    }

    private function activeSuperAdmins(): int
    {
        return DB::table('admins')
            ->join('vai_tros', 'vai_tros.id', '=', 'admins.vai_tro_id')
            ->where('vai_tros.ma_vai_tro', 'super_admin')
            ->where('admins.trang_thai', 1)
            ->count();
    }

    private function revokeTokens(string $type, int $id): void
    {
        DB::table('personal_access_tokens')
            ->where('tokenable_type', self::TOKEN_TYPES[$type])
            ->where('tokenable_id', $id)
            ->delete();
    }

    private function log(?object $actor, string $type, int $id, string $action, ?string $reason, ?array $data, ?string $ip): void
    {
        DB::table('nhat_ky_tai_khoans')->insert([
            'admin_id' => $actor?->id,
            'loai_tai_khoan' => $type,
            'id_tai_khoan' => $id,
            'hanh_dong' => $action,
            'ly_do' => $reason,
            'du_lieu' => $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : null,
            'ip' => $ip,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
