<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BanDoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        for ($i = 1; $i <= 50; $i++) {
            DB::table('ban_dos')->insert([
                'user_id' => rand(1, 100),
                'mau_xe_id' => rand(1, 4),
                'ten' => "Cấu hình xe $i",
                'tong_gia' => rand(5000000000, 12000000000)
            ]);
        }
    }
}
