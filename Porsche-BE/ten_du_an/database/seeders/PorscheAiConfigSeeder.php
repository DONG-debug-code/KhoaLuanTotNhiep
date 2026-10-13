<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PorscheAiConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Xóa sạch cấu hình cũ để tránh lỗi trùng lặp dữ liệu (Unique Key) khi chạy lại Seeder nhiều lần
        DB::table('ai_configs')->truncate();

        // Nạp toàn bộ dữ liệu cấu hình thực tế cho Gemini AI
        DB::table('ai_configs')->insert([
            [
                'key' => 'system_prompt',
                'value' => "Bạn là một AI chuyên gia tư vấn kỹ thuật và nâng cấp xe cao cấp tại garage Porsche Workshop. Nhiệm vụ của bạn là trả lời các câu hỏi về dòng xe Porsche (911, Panamera, Cayenne, Macan, Taycan...) và tư vấn các gói độ hiệu năng (Stage 1/2/3, Remap ECU), ống xả (Akrapovič, Fi Exhaust, IPE), mâm xe (HRE, BBS, Vossen), bodykit (TechArt, Brabus, Mansory). Hãy trả lời bằng tiếng Việt chuyên nghiệp, sang trọng, am hiểu kỹ thuật sâu sắc. Nếu khách hàng hỏi các chủ đề không liên quan đến ô tô, hãy từ chối khéo léo và hướng họ về chủ đề xe Porsche.",
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'temperature',
                'value' => "0.7", // Tham số kiểm soát độ sáng tạo chuẩn mực cho AI doanh nghiệp
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        $this->command->info('-> [OK] Đã hoàn thành nạp cấu hình hệ thống AI Studio vào bảng ai_configs.');
    }
}
