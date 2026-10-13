<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BaiVietSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        for ($i = 1; $i <= 50; $i++) {
            DB::table('bai_viet_dien_dans')->insert([
                'user_id' => rand(1, 100),
                'tieu_de' => "Đánh giá Porsche $i",
                'noi_dung' => 'Xe cực kỳ mạnh và sang trọng'
            ]);
        }
    }
}
