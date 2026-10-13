<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PhuTungSeeder extends Seeder
{
    public function run(): void
    {
        $data = [

            // 🔥 NGOẠI THẤT
            [
                'ten' => 'Sơn đỏ Guards Red',
                'gia' => 50000000,
                'so_luong' => 10,
                'loai' => 'ngoai_that',
                'mo_ta' => 'Màu đỏ thể thao đặc trưng Porsche',
                'anh' => 'colors/guards_red.jpg',
            ],
            [
                'ten' => 'Sơn đen Jet Black Metallic',
                'gia' => 60000000,
                'so_luong' => 8,
                'loai' => 'ngoai_that',
                'mo_ta' => 'Màu đen ánh kim cao cấp',
                'anh' => 'colors/jet_black.jpg',
            ],
            [
                'ten' => 'Mâm 20 inch Sport',
                'gia' => 120000000,
                'so_luong' => 3,
                'loai' => 'ngoai_that',
                'mo_ta' => 'Mâm thể thao hiệu suất cao',
                'anh' => 'wheels/sport_20.jpg',
            ],

            // 🔥 NỘI THẤT
            [
                'ten' => 'Ghế da cao cấp',
                'gia' => 80000000,
                'so_luong' => 5,
                'loai' => 'noi_that',
                'mo_ta' => 'Ghế da chỉnh điện đa hướng',
                'anh' => 'interior/leather_seat.jpg',
            ],
            [
                'ten' => 'Vô lăng thể thao GT',
                'gia' => 30000000,
                'so_luong' => 4,
                'loai' => 'noi_that',
                'mo_ta' => 'Thiết kế thể thao, cầm chắc tay',
                'anh' => 'interior/gt_wheel.jpg',
            ],
            [
                'ten' => 'Nội thất da full cao cấp',
                'gia' => 150000000,
                'so_luong' => 0, // Đặt bằng 0 để test tính năng AI báo hết hàng
                'loai' => 'noi_that',
                'mo_ta' => 'Bọc da toàn bộ nội thất',
                'anh' => 'interior/full_leather.jpg',
            ],

            // 🔥 HIỆU SUẤT
            [
                'ten' => 'Gói Sport Chrono',
                'gia' => 180000000,
                'so_luong' => 6,
                'loai' => 'hieu_suat',
                'mo_ta' => 'Tăng khả năng tăng tốc và điều khiển',
                'anh' => 'performance/sport_chrono.jpg',
            ],
            [
                'ten' => 'Hệ thống ống xả thể thao',
                'gia' => 90000000,
                'so_luong' => 2,
                'loai' => 'hieu_suat',
                'mo_ta' => 'Âm thanh động cơ mạnh mẽ',
                'anh' => 'performance/exhaust.jpg',
            ],
            [
                'ten' => 'Phanh gốm PCCB',
                'gia' => 250000000,
                'so_luong' => 0, // Đặt bằng 0 để test tính năng AI báo hết hàng
                'loai' => 'hieu_suat',
                'mo_ta' => 'Phanh hiệu suất cao',
                'anh' => 'performance/pccb.jpg',
            ],

            // 🔥 CÔNG NGHỆ
            [
                'ten' => 'Màn hình giải trí nâng cao',
                'gia' => 40000000,
                'so_luong' => 7,
                'loai' => 'cong_nghe',
                'mo_ta' => 'Màn hình cảm ứng hiện đại',
                'anh' => 'tech/screen.jpg',
            ],
            [
                'ten' => 'Âm thanh Bose',
                'gia' => 70000000,
                'so_luong' => 4,
                'loai' => 'cong_nghe',
                'mo_ta' => 'Hệ thống âm thanh cao cấp',
                'anh' => 'tech/bose.jpg',
            ],
            [
                'ten' => 'Camera 360',
                'gia' => 35000000,
                'so_luong' => 5,
                'loai' => 'cong_nghe',
                'mo_ta' => 'Hỗ trợ quan sát toàn cảnh',
                'anh' => 'tech/camera360.jpg',
            ],
        ];

        DB::table('phu_tungs')->insert($data);
    }
}
