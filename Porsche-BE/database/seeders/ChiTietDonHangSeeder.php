<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChiTietDonHangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Lấy danh sách ID thực tế từ bảng don_hangs và san_phams
        $donHangIds = DB::table('don_hangs')->pluck('id')->toArray();
        $sanPhamIds = DB::table('san_phams')->pluck('id')->toArray();

        // Phòng trường hợp một trong hai bảng bị trống dữ liệu
        if (empty($donHangIds) || empty($sanPhamIds)) {
            return;
        }

        // 2. Chạy vòng lặp sinh ngẫu nhiên 50 bản ghi chi tiết đơn hàng
        for ($i = 1; $i <= 50; $i++) {
            $soLuong = rand(1, 3);                 // Số lượng ngẫu nhiên từ 1 đến 3
            $donGia = rand(1000000, 15000000);     // Đơn giá ngẫu nhiên từ 1M đến 15M
            $thanhTien = $donGia * $soLuong;       // Tự động tính thành tiền chuẩn logic toán học

            DB::table('chi_tiet_don_hangs')->insertOrIgnore([
                'don_hang_id' => $donHangIds[array_rand($donHangIds)],
                'san_pham_id' => $sanPhamIds[array_rand($sanPhamIds)],
                'so_luong'    => $soLuong,
                'don_gia'     => $donGia,
                'thanh_tien'  => $thanhTien,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
