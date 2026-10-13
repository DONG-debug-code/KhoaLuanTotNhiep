<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChiTietBanDoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy danh sách ID thực tế đang có trong bảng cha ban_dos và phu_tungs
        $banDoIds = DB::table('ban_dos')->pluck('id')->toArray();
        $phuTungIds = DB::table('phu_tungs')->pluck('id')->toArray();

        // Nếu một trong hai bảng trống dữ liệu thì dừng seeder để tránh lỗi vòng lặp
        if (empty($banDoIds) || empty($phuTungIds)) {
            return;
        }

        // Vòng lặp tạo 50 bản ghi mẫu ngẫu nhiên
        for ($i = 1; $i <= 50; $i++) {
            DB::table('chi_tiet_ban_dos')->insertOrIgnore([
                'ban_do_id'   => $banDoIds[array_rand($banDoIds)],     // Lấy ngẫu nhiên ID bản đồ thực tế
                'phu_tung_id' => $phuTungIds[array_rand($phuTungIds)], // Lấy ngẫu nhiên ID phụ tùng thực tế
                'so_luong'    => rand(1, 5),
                'gia'         => rand(100000, 2000000),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
