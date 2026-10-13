<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MauSacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('mau_sacs')->insert([
            ['ten' => 'Guards Red', 'ma_mau' => '#C80815', 'loai' => 'Standard'],
            ['ten' => 'Jet Black Metallic', 'ma_mau' => '#0A0A0A', 'loai' => 'Metallic'],
            ['ten' => 'Carrara White Metallic', 'ma_mau' => '#F5F5F5', 'loai' => 'Metallic'],
            ['ten' => 'Crayon', 'ma_mau' => '#BEBEBE', 'loai' => 'Special'],
            ['ten' => 'Miami Blue', 'ma_mau' => '#00AEEF', 'loai' => 'Special'],
            ['ten' => 'GT Silver Metallic', 'ma_mau' => '#C0C0C0', 'loai' => 'Metallic'],
        ]);
    }
}
