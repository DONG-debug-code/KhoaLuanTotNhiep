<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PhieuGiamGiaSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // 🔹 Voucher % phổ thông
            [
                'code' => 'PORSCHE10',
                'loai' => 'phan_tram',
                'gia_tri' => 10,
                'gia_tri_don_hang_toi_thieu' => 1000000000,
                'giam_toi_da' => 50000000,
                'so_luong' => 100,
                'ngay_bat_dau' => Carbon::now()->subDays(5),
                'ngay_ket_thuc' => Carbon::now()->addMonths(2),
                'trang_thai' => 1,
            ],

            // 🔹 Voucher VIP tiền mặt
            [
                'code' => 'VIP5TR',
                'loai' => 'tien_mat',
                'gia_tri' => 5000000,
                'gia_tri_don_hang_toi_thieu' => 2000000000,
                'giam_toi_da' => null,
                'so_luong' => 50,
                'ngay_bat_dau' => Carbon::now(),
                'ngay_ket_thuc' => Carbon::now()->addMonths(1),
                'trang_thai' => 1,
            ],

            // 🔹 Flash sale
            [
                'code' => 'FLASH20',
                'loai' => 'phan_tram',
                'gia_tri' => 20,
                'gia_tri_don_hang_toi_thieu' => 1500000000,
                'giam_toi_da' => 80000000,
                'so_luong' => 20,
                'ngay_bat_dau' => Carbon::now(),
                'ngay_ket_thuc' => Carbon::now()->addDays(7),
                'trang_thai' => 1,
            ],

            // 🔹 Voucher hết hạn
            [
                'code' => 'OLD10',
                'loai' => 'phan_tram',
                'gia_tri' => 10,
                'gia_tri_don_hang_toi_thieu' => 1000000000,
                'giam_toi_da' => 30000000,
                'so_luong' => 10,
                'ngay_bat_dau' => Carbon::now()->subMonths(2),
                'ngay_ket_thuc' => Carbon::now()->subDays(1),
                'trang_thai' => 0,
            ],
        ];

        DB::table('phieu_giam_gias')->insert($data);
    }
}
