<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnhSanPhamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('anh_san_phams')->insert([
            [
                'san_pham_id' => 1,
                'duong_dan' => 'products/03.jpg'
            ],
            [
                'san_pham_id' => 2,
                'duong_dan' => 'products/06.jpg'
            ],
        ]);
    }
}
