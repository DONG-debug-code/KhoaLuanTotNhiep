<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PhuTungDetailsSeeder extends Seeder
{
    public function run(): void
    {
        $details = [
            'Akrapovič Evolution Line Titan' => [
                'mo_ta' => 'Xả titan siêu nhẹ, giảm 9 kg và tăng 12 mã lực.',
                'bao_gom' => ['Full system titan', 'Cổ xả thể thao', 'Ống nối & gioăng', 'Cân chỉnh ECU'],
                'dong_xe' => ['911 Turbo S', '911 Carrera'],
            ],
            'Fi Exhaust Valvetronic' => [
                'mo_ta' => 'Van xả điều khiển bằng app, đổi âm thể thao ↔ êm dịu chỉ một chạm.',
                'bao_gom' => ['Full system thép không gỉ', 'Van điều khiển', 'Module app', 'Remote'],
                'dong_xe' => ['Macan GTS', 'Cayenne Coupe'],
            ],
            'Manthey Performance Kit' => [
                'mo_ta' => 'Gói tối ưu vòng đua chính hãng, giữ nguyên điều kiện bảo hành của Porsche.',
                'bao_gom' => ['Bộ khí động học', 'Treo tối ưu', 'Mâm nhẹ', 'Cân chỉnh tại xưởng'],
                'dong_xe' => ['911 Turbo S'],
            ],
            'Gói Stage 2 ECU Tuning' => [
                'mo_ta' => 'Remap ECU trên dyno, tối ưu hỗn hợp & đánh lửa, tăng 60–90 mã lực.',
                'bao_gom' => ['Remap cá nhân hóa', '3 lượt chạy dyno', 'Báo cáo công suất', 'Bảo hành phần mềm'],
                'dong_xe' => ['Macan GTS', '911 Carrera', 'Cayenne Coupe'],
            ],
            'Hệ thống nạp khí Carbon' => [
                'mo_ta' => 'Hộp nạp carbon giảm nhiệt độ khí nạp, tiếng turbo rõ hơn.',
                'bao_gom' => ['Hộp nạp carbon', 'Lọc hiệu năng cao', 'Ống nạp silicon', 'Công lắp'],
                'dong_xe' => ['911 Carrera', 'Macan GTS'],
            ],
            'TechArt GTstreet R Bodykit' => [
                'mo_ta' => 'Bodykit khí động học full carbon, hạ gầm, mở rộng hốc bánh.',
                'bao_gom' => ['Cản trước carbon', 'Cánh gió sau', 'Ốp sườn', 'Bộ chia gió'],
                'dong_xe' => ['911 Turbo S'],
            ],
            'Cánh gió Carbon GT3' => [
                'mo_ta' => 'Cánh sau carbon kiểu GT3, tăng lực ép xuống ở tốc độ cao.',
                'bao_gom' => ['Cánh gió carbon', 'Chân đế nhôm', 'Bộ ốc', 'Công lắp'],
                'dong_xe' => ['911 Carrera', '911 Turbo S'],
            ],
            'KW Variant 4 Coilover' => [
                'mo_ta' => 'Coilover chỉnh nén & hồi phục độc lập, cân bằng phố ↔ track day.',
                'bao_gom' => ['Bộ 4 phuộc', 'Lò xo thể thao', 'Căn chỉnh góc đặt bánh', 'Công lắp'],
                'dong_xe' => ['911 Carrera', 'Cayenne Coupe'],
            ],
            'HRE P101 Forged Wheels' => [
                'mo_ta' => 'Mâm rèn monoblock CNC, nhẹ hơn mâm zin 22%, tùy biến màu sơn.',
                'bao_gom' => ['4 mâm 21”', 'Cân bằng động', 'Van cảm biến áp suất', 'Công lắp'],
                'dong_xe' => ['Macan GTS', 'Cayenne Coupe'],
            ],
            'Bộ phanh gốm PCCB' => [
                'mo_ta' => 'Đĩa gốm composite, giảm khối lượng, hiệu năng phanh bền bỉ.',
                'bao_gom' => ['4 đĩa gốm', 'Cùm 6 piston', 'Bố phanh', 'Dầu phanh hiệu năng cao'],
                'dong_xe' => ['Cayenne Coupe', '911 Turbo S'],
            ],
            'Nội thất Carbon & Alcantara' => [
                'mo_ta' => 'Bọc lại vô lăng, táp lô, ốp cửa bằng carbon thật + Alcantara.',
                'bao_gom' => ['Vô lăng Alcantara', 'Ốp táp lô carbon', 'Ốp cửa carbon', 'Chỉ khâu tùy chọn'],
                'dong_xe' => ['Taycan Turbo S', '911 Carrera', 'Cayenne Coupe'],
            ],
            'Phim PPF & Phủ Ceramic toàn xe' => [
                'mo_ta' => 'PPF tự phục hồi toàn xe + phủ ceramic 9H.',
                'bao_gom' => ['PPF full body', 'Ceramic 9H', 'Vệ sinh khoang máy', 'Bảo dưỡng định kỳ 3 lần'],
                'dong_xe' => ['911 Turbo S', '911 Carrera', 'Taycan Turbo S', 'Cayenne Coupe', 'Macan GTS'],
            ],
        ];

        DB::transaction(function () use ($details): void {
            $foundNames = DB::table('phu_tungs')
                ->whereIn('ten', array_keys($details))
                ->pluck('ten')
                ->all();
            $missingNames = array_diff(array_keys($details), $foundNames);

            if ($missingNames !== []) {
                throw new RuntimeException(
                    'Không tìm thấy phụ tùng cần cập nhật: ' . implode(', ', $missingNames)
                );
            }

            foreach ($details as $name => $attributes) {
                DB::table('phu_tungs')->where('ten', $name)->update([
                    'mo_ta' => $attributes['mo_ta'],
                    'bao_gom' => json_encode($attributes['bao_gom'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'dong_xe' => json_encode($attributes['dong_xe'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                ]);
            }
        });
    }
}
