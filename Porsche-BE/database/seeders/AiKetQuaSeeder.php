<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiKetQuaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Lấy danh sách ID người dùng thực tế từ bảng users
        $userIds = DB::table('users')->pluck('id')->toArray();

        if (empty($userIds)) {
            return;
        }

        // 2. Kịch bản dữ liệu AI thực tế (Hỏi - Đáp thông minh chuyên sâu về Porsche và Độ xe Ô tô)
        $aiScenarios = [
            [
                'input' => 'Tư vấn gói độ tăng công suất Stage 2 cho Porsche 911 Carrera S (992).',
                'output' => 'Để lên Stage 2 cho Porsche 911 Carrera S (992), bạn cần kết hợp: 1. Nâng cấp hệ thống ống xả (Downpipe) loại bỏ bầu lọc chuyên dụng (Catless hoặc High-flow Cat) từ các thương hiệu như Akrapovič hoặc IPE. 2. Lọc gió hiệu năng cao BMC. 3. Remap ECU/Tuning (như phần mềm từ Cobb Tuning hoặc Mcchip-DKR). Gói này giúp công suất xe tăng từ 450 HP lên khoảng 540 - 560 HP, mô-men xoắn đạt mức cực đại mượt mà hơn ở dải tua thấp.'
            ],
            [
                'input' => 'Chi phí và lưu ý khi nâng cấp bodykit TechArt cho dòng xe Porsche Cayenne Coupé?',
                'output' => 'Bodykit TechArt cho Porsche Cayenne Coupé là bản độ khí động học đẳng cấp. Gói độ bao gồm: cản trước hầm hố, ốp sườn carbon, cánh gió đuôi và bộ khuếch tán sau. Chi phí hàng chính hãng nhập khẩu dao động từ 300 triệu đến hơn 600 triệu VNĐ tùy thuộc vào tỷ lệ chi tiết Carbon Fiber. Lưu ý: Khi lắp đặt cần chọn garage có chứng chỉ, sử dụng keo chuyên dụng của hãng để không làm ảnh hưởng đến các cảm biến va chạm và hệ thống radar zin của xe.'
            ],
            [
                'input' => 'Xe Porsche Panamera đi được 40.000 km thì cần bảo dưỡng những hạng mục lớn nào?',
                'output' => 'Ở mốc 40.000 km (Bảo dưỡng lớn - Major Service), chiếc Panamera của bạn cần thực hiện: 1. Thay nhớt máy và lọc nhớt định kỳ. 2. Thay lọc gió động cơ và lọc gió điều hòa (than hoạt tính). 3. Thay hệ thống bugi đánh lửa để đảm bảo hiệu suất buồng đốt. 4. Kiểm tra/thay dầu hộp số PDK (rất quan trọng đối với Porsche). 5. Kiểm tra hệ thống phanh (má phanh, đĩa phanh) và mức độ lão hóa của cao su chân máy.'
            ],
            [
                'input' => 'Nên chọn ống xả Akrapovič Titanium hay Fi Exhaust cho Porsche Macan GTS?',
                'output' => 'Cả hai thương hiệu đều sở hữu hệ thống van On/Off thông minh (Valvetronic). Tuy nhiên: Akrapovič Titanium thiên về chất âm trầm ấm, uy lực ở tua cao, trọng lượng siêu nhẹ nhờ chất liệu Titanium cao cấp và giữ được nét sang trọng của Porsche. Trong khi đó, Fi Exhaust (Frequency Intelligent) cho âm thanh đanh, giòn, tiếng "pop and bang" rõ và to hơn khi hạ số, phù hợp với chủ xe thích phong cách thể thao phấn khích rõ rệt.'
            ],
            [
                'input' => 'Công nghệ phanh gốm carbon (PCCB) trên các dòng xe Porsche có ưu điểm gì so với phanh đĩa thép thông thường?',
                'output' => 'Hệ thống phanh gốm carbon Porsche Ceramic Composite Brake (PCCB) nổi bật với các kẹp phanh màu vàng đặc trưng. Ưu điểm vượt trội bao gồm: 1. Trọng lượng nhẹ hơn phanh đĩa thép khoảng 50%, giúp giảm trọng lượng không được treo (unsprung mass), tăng độ nhạy tay lái. 2. Khả năng chịu nhiệt cực cao, hoàn toàn không bị hiện tượng mất phanh do quá nhiệt (brake fade) kể cả trên đường đua. 3. Tuổi thọ đĩa phanh có thể lên tới 100.000 - 150.000 km trong điều kiện vận hành thông thường.'
            ],
            [
                'input' => 'Xu hướng độ mâm xe ô tô hiện nay cho các dòng xe thể thao Đức là gì?',
                'output' => 'Xu hướng hàng đầu hiện nay là dòng mâm Thèn (Forged - Mâm rèn nguyên khối) 2 mảnh hoặc 3 mảnh (Multi-piece), cho phép tùy biến tháo rời lòng mâm và viền mâm. Các thương hiệu mâm độ xa xỉ được giới chơi Porsche ưa chuộng bao gồm HRE Performance Wheels, BBS (đặc biệt là dòng BBS FI-R siêu nhẹ) và Vossen. Thiết kế được chuộng nhất là kiểu đa chấu hoặc chấu chữ Y sơn màu Satin Black, Brushed Bronze hoặc mạ Chrome mờ.'
            ]
        ];

        // 3. Tiến hành bơm dữ liệu ngẫu nhiên vào DB
        for ($i = 1; $i <= 30; $i++) {
            // Lấy ngẫu nhiên 1 kịch bản AI hỏi đáp
            $scenario = $aiScenarios[array_rand($aiScenarios)];

            DB::table('ai_goi_y_ket_quas')->insert([
                'user_id'    => rand(1, 5) === 1 ? null : $userIds[array_rand($userIds)], // 20% cơ hội khách vãng lai hỏi (null) theo đúng migration của bạn
                'input_user' => $scenario['input'],  // 🌟 Đã sửa khớp khớp 100% với Migration
                'output_ai'  => $scenario['output'], // 🌟 Đã sửa khớp khớp 100% với Migration
                'created_at' => now()->subHours(rand(1, 72)), // Tạo thời gian rải rác nhìn cho thật
                'updated_at' => now(),
            ]);
        }
    }
}
