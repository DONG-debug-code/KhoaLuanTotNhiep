<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SanPhamMauSacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 100; $i++) {
            // Sử dụng insertOrIgnore để nếu ngẫu nhiên bị trùng lặp cặp (san_pham_id, mau_sac_id)
            // thì MySQL sẽ bỏ qua dòng đó, không làm crash toàn bộ hệ thống seeder.
            DB::table('san_pham_mau_sac')->insertOrIgnore([
                'san_pham_id' => rand(1, 50),
                'mau_sac_id'  => rand(1, 6),
                'gia_them'    => rand(0, 5000000),
                'so_luong'    => rand(1, 5),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
