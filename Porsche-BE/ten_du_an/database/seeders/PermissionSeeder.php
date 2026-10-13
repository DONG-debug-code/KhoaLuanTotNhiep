<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('permissions')->insert([
            ['ten_chuc_nang' => 'Xem sản phẩm', 'ma_chuc_nang' => 'xem_san_pham', 'created_at' => now(), 'updated_at' => now()],
            ['ten_chuc_nang' => 'Tạo sản phẩm', 'ma_chuc_nang' => 'tao_san_pham', 'created_at' => now(), 'updated_at' => now()],
            ['ten_chuc_nang' => 'Sửa sản phẩm', 'ma_chuc_nang' => 'sua_san_pham', 'created_at' => now(), 'updated_at' => now()],
            ['ten_chuc_nang' => 'Xóa sản phẩm', 'ma_chuc_nang' => 'xoa_san_pham', 'created_at' => now(), 'updated_at' => now()],

            ['ten_chuc_nang' => 'Tạo đơn hàng', 'ma_chuc_nang' => 'tao_don_hang', 'created_at' => now(), 'updated_at' => now()],
            ['ten_chuc_nang' => 'Xem đơn hàng', 'ma_chuc_nang' => 'xem_don_hang', 'created_at' => now(), 'updated_at' => now()],
            ['ten_chuc_nang' => 'Xử lý đơn hàng', 'ma_chuc_nang' => 'xu_ly_don_hang', 'created_at' => now(), 'updated_at' => now()],

            ['ten_chuc_nang' => 'Quản lý người dùng', 'ma_chuc_nang' => 'quan_ly_user', 'created_at' => now(), 'updated_at' => now()],
            ['ten_chuc_nang' => 'Quản lý kho', 'ma_chuc_nang' => 'quan_ly_kho', 'created_at' => now(), 'updated_at' => now()],
            ['ten_chuc_nang' => 'Quản lý bài viết', 'ma_chuc_nang' => 'quan_ly_bai_viet', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}