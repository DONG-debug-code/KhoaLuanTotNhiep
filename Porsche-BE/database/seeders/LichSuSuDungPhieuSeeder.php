<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LichSuSuDungPhieuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 🌟 LẤY DỰNG ID ĐỘNG TỪ CÁC BẢNG THỰC TẾ TRONG DB
        $phieuIds = DB::table('phieu_giam_gias')->pluck('id')->toArray();
        $donHangIds = DB::table('don_hangs')->pluck('id')->toArray();
        $userIds = DB::table('users')->pluck('id')->toArray();

        // Nếu một trong các bảng bị trống, thoát ra để tránh lỗi
        if (empty($phieuIds) || empty($donHangIds) || empty($userIds)) {
            return;
        }

        // Tạo vòng lặp sinh dữ liệu mẫu một cách an toàn
        for ($i = 1; $i <= 50; $i++) {
            DB::table('lich_su_su_dung_phieus')->insert([
                'don_hang_id'       => $donHangIds[array_rand($donHangIds)],
                'phieu_id'          => $phieuIds[array_rand($phieuIds)], // 🌟 Đảm bảo luôn lấy ID chuẩn đang tồn tại
                'user_id'           => $userIds[array_rand($userIds)],
                'so_tien_giam'      => rand(20000, 500000),
                'thoi_diem_su_dung' => now()->subDays(rand(1, 30)),
                'trang_thai'        => array_rand(['da_su_dung' => 'da_su_dung', 'huy' => 'huy']),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }
}
