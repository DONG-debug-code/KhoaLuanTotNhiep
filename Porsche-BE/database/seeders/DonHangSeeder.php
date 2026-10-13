<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DonHangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = DB::table('users')->pluck('id')->toArray();
        if (empty($userIds)) {
            return;
        }

        $phuongThucTT = ['cod', 'vnpay', 'momo'];
        $trangThaiList = ['cho_xac_nhan', 'da_xac_nhan', 'dang_giao', 'hoan_thanh', 'huy'];
        $donHangData = [];

        for ($i = 1; $i <= 200; $i++) {
            $donHangData[] = [
                'users_id'             => $userIds[array_rand($userIds)],
                'tong_tien'            => 0, // Sẽ được ChiTietDonHangSeeder tính toán cập nhật lại sau
                'giam_gia'             => 0,
                'thanh_tien'           => 0,
                'trang_thai'           => $trangThaiList[array_rand($trangThaiList)],
                'phuong_thuc_tt'       => $phuongThucTT[array_rand($phuongThucTT)],
                'dia_chi_giao_hang_id' => rand(1, 5),
                'ngay_tao'             => now()->subDays(rand(1, 30)),
                'created_at'           => now(),
                'updated_at'           => now(),
            ];
        }

        DB::table('don_hangs')->insert($donHangData);
    }
}
