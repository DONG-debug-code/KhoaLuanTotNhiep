<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LichSuBaoDuongSeeder extends Seeder
{
    public function run(): void
    {
        $noiDungList = [
            'Bảo dưỡng định kỳ 5.000 km',
            'Bảo dưỡng định kỳ 10.000 km',
            'Thay dầu động cơ Mobil 1',
            'Kiểm tra hệ thống phanh',
            'Thay lọc gió động cơ',
            'Cân chỉnh thước lái',
            'Kiểm tra hệ thống điện',
            'Bảo dưỡng tổng thể xe',
        ];

        $data = [];

        for ($i = 1; $i <= 300; $i++) {
            $data[] = [
                'user_id' => rand(1, 100), // phải tồn tại
                'san_pham_id' => rand(1, 20), // phải tồn tại
                'noi_dung' => $noiDungList[array_rand($noiDungList)],
                'ngay_bao_duong' => Carbon::now()->subDays(rand(1, 365)),
                'chi_phi' => rand(1500000, 8000000), // 1.5tr - 8tr
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('lich_su_bao_duongs')->insert($data);
    }
}
