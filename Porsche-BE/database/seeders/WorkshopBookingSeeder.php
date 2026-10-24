<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkshopBookingSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ChuyenGiaSeeder::class);

        $now = now();
        foreach (['Bay 1 - Lắp đặt phụ tùng', 'Bay 2 - Hiệu năng & ECU', 'Bay 3 - Ngoại thất & Mâm xe'] as $area) {
            DB::table('khu_vuc_workshops')->updateOrInsert(
                ['ten_khu_vuc' => $area],
                ['trang_thai' => 1, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}
