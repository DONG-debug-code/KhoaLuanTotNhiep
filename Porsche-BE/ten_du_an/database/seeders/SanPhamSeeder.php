<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SanPhamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenXe = [
            '911 Carrera',
            '911 Turbo S',
            'Taycan Turbo S',
            'Cayenne Coupe',
            'Macan GTS'
        ];

        for ($i = 1; $i <= 50; $i++) {
            DB::table('san_phams')->insert([
                'mau_xe_id' => rand(1, 4),
                'ten' => $tenXe[array_rand($tenXe)] . " #" . $i,
                'gia' => rand(3000000000, 10000000000),
                'so_luong' => rand(1, 10)
            ]);
        }
    }
}
