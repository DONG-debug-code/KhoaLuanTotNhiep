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
        $anhTheoMauXe = [
            '911 Carrera' => ['911Carrera_1.jpg', '911Carrera_2.jpg', '911Carrera_3.jpg'],
            '911 Turbo S' => ['911TurboS_1.jpg', '911TurboS_2.jpg', '911TurboS_3.jpg'],
            'Taycan Turbo S' => ['TaycanTurboS_1.jpg', 'TaycanTurboS_2.jpg', 'TaycanTurboS_3.jpg'],
            'Cayenne Coupe' => ['CayenneCoupe_1.jpg', 'CayenneCoupe_2.jpg', 'CayenneCoupe_3.jpg'],
            'Macan GTS' => ['MacanGTS_1.jpg', 'MacanGTS_2.jpg', 'MacanGTS_3.jpg'],
        ];

        foreach ($anhTheoMauXe as $tenMauXe => $tepAnh) {
            $sanPham = DB::table('san_phams')
                ->where('ten', 'like', "%{$tenMauXe}%")
                ->orderBy('id')
                ->first(['id']);

            if (!$sanPham) {
                $this->command->warn("Không tìm thấy sản phẩm mẫu xe: {$tenMauXe}; bỏ qua ảnh.");
                continue;
            }

            foreach ($tepAnh as $index => $tenTep) {
                $duongDan = "products/{$tenTep}";

                DB::table('anh_san_phams')->updateOrInsert(
                    [
                        'san_pham_id' => $sanPham->id,
                        'duong_dan' => $duongDan,
                    ],
                    [
                        'la_anh_chinh' => $index === 0,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
