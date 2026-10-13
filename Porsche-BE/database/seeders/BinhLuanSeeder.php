<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BinhLuanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        for ($i = 1; $i <= 200; $i++) {
            DB::table('binh_luan_dien_dans')->insert([
                'bai_viet_id' => rand(1, 50),
                'user_id' => rand(1, 100),
                'noi_dung' => 'Quá đẹp!'
            ]);
        }
    }
}
