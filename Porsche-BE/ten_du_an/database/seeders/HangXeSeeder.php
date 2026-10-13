<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HangXeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('hang_xes')->insert([
            [
                'ten' => 'Porsche',
                'slug' => 'porsche',
                'quoc_gia' => 'Germany',
                'mo_ta' => 'Thương hiệu xe thể thao cao cấp của Đức',
                'logo' => 'porsche.png',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
