<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DanhGiaSanPhamSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 200; $i++) {
            DB::table('danh_gia_san_phams')->insert([
                'user_id' => rand(2, 100),
                'san_pham_id' => rand(1, 20),
                'so_sao' => rand(3, 5),
                'noi_dung' => 'Xe rất tốt, trải nghiệm lái tuyệt vời #' . $i,
            ]);
        }
    }
}
