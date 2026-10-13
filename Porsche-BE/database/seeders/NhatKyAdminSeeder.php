<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NhatKyAdminSeeder extends Seeder
{
    public function run(): void
    {
        $hanhDongList = [
            'Thêm sản phẩm',
            'Cập nhật sản phẩm',
            'Xóa sản phẩm',
            'Xác nhận đơn hàng',
            'Hủy đơn hàng',
            'Tạo mã giảm giá',
            'Cập nhật cấu hình xe',
        ];

        $doiTuongList = ['san_pham', 'don_hang', 'phieu_giam_gia'];

        $data = [];

        for ($i = 1; $i <= 200; $i++) {

            $hanhDong = $hanhDongList[array_rand($hanhDongList)];
            $doiTuong = $doiTuongList[array_rand($doiTuongList)];

            $duLieuCu = [
                'gia' => rand(1000000000, 3000000000),
                'ten' => 'Porsche Model Old'
            ];

            $duLieuMoi = [
                'gia' => rand(1000000000, 3000000000),
                'ten' => 'Porsche Model New'
            ];

            $data[] = [
                'user_id' => rand(1, 5), // admin/staff
                'hanh_dong' => $hanhDong,
                'ip' => '192.168.1.' . rand(1, 255),
                'trinh_duyet' => 'Chrome',
                'doi_tuong' => $doiTuong,
                'doi_tuong_id' => rand(1, 50),
                'du_lieu_cu' => json_encode($duLieuCu),
                'du_lieu_moi' => json_encode($duLieuMoi),
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
                'updated_at' => now(),
            ];
        }

        DB::table('nhat_ky_hoat_dong_admins')->insert($data);
    }
}
