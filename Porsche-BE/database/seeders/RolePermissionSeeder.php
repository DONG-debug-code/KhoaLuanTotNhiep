<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Lấy danh sách toàn bộ quyền theo cột ma_chuc_nang
        $permissions = DB::table('permissions')->pluck('id', 'ma_chuc_nang');

        // 2. Tìm ID của các vai trò
        $adminRole = DB::table('vai_tros')->where('ten', 'like', 'admin')->first();
        $staffRole = DB::table('vai_tros')->where('ten', 'like', 'staff')->first();
        $customerRole = DB::table('vai_tros')->where('ten', 'like', 'customer')->first();

        $adminId = $adminRole ? $adminRole->id : 1;
        $staffId = $staffRole ? $staffRole->id : 2;
        $customerId = $customerRole ? $customerRole->id : 3;

        $data = [];

        // 🔹 GÁN QUYỀN CHO ADMIN (TẤT CẢ CÁC QUYỀN)
        foreach ($permissions as $permId) {
            $data[] = [
                'vai_tro_id' => $adminId,
                'permission_id' => $permId
            ];
        }

        // 🔹 GÁN QUYỀN CHO STAFF
        $staffPerms = [
            'xem_san_pham',
            'tao_don_hang',
            'xem_don_hang',
            'xu_ly_don_hang'
        ];

        foreach ($staffPerms as $perm) {
            if (isset($permissions[$perm])) {
                $data[] = [
                    'vai_tro_id' => $staffId,
                    'permission_id' => $permissions[$perm]
                ];
            }
        }

        // 🔹 GÁN QUYỀN CHO CUSTOMER
        $customerPerms = [
            'xem_san_pham',
            'tao_don_hang',
            'xem_don_hang'
        ];

        foreach ($customerPerms as $perm) {
            if (isset($permissions[$perm])) {
                $data[] = [
                    'vai_tro_id' => $customerId,
                    'permission_id' => $permissions[$perm]
                ];
            }
        }

        // 3. Đổ toàn bộ dữ liệu phân quyền vào bảng trung gian role_permission
        DB::table('role_permission')->insert($data);
    }
}