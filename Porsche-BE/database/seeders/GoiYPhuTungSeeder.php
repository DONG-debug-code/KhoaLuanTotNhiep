<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GoiYPhuTungSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 50; $i++) {
            DB::table('goi_y_phu_tung_ais')->insert([
                'user_id' => rand(2, 50),
                'san_pham_id' => rand(1, 20),
                'phu_tung_id' => rand(1, 20),
                'input_user' => 'Tôi cần phụ tùng cho xe Porsche',
                'do_tin_cay' => rand(70, 100),
            ]);
        }
    }
}
