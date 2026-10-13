<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PorscheWorkshopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Khóa ngoại tạm thời tắt để tránh lỗi xung đột dữ liệu khi chạy lại nhiều lần (Truncate)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Làm sạch dữ liệu cũ trước khi nạp dữ liệu mới để tránh trùng lặp gạch lỗi
        DB::table('thuong_hieus')->truncate();
        DB::table('danh_mucs')->truncate();
        DB::table('san_phams')->truncate();
        DB::table('ai_configs')->truncate();

        // ==========================================
        // 1. TẠO THƯƠNG HIỆU ĐỘ XE DANH TIẾNG (Brands)
        // ==========================================
        $brands = [
            ['ten' => 'Akrapovič', 'mo_ta' => 'Hãng ống xả Titanium hiệu năng cao danh tiếng từ Slovenia.'],
            ['ten' => 'HRE Performance Wheels', 'mo_ta' => 'Thương hiệu mâm nhôm đúc nguyên khối siêu nhẹ hàng đầu thế giới từ Mỹ.'],
            ['ten' => 'KW Automotive', 'mo_ta' => 'Hệ thống phuộc nhún, treo điện tử thích ứng chuyên dụng cho xe đua và siêu xe.'],
            ['ten' => 'TechArt', 'mo_ta' => 'Hãng độ xe chuyên biệt, cá nhân hóa toàn diện dành riêng cho các dòng xe Porsche.'],
            ['ten' => 'Porsche OEM Software', 'mo_ta' => 'Hệ thống phần mềm, firmware chính hãng và nâng cấp tính năng ẩn từ nhà máy Porsche.']
        ];

        $thuongHieuIds = [];
        foreach ($brands as $b) {
            $thuongHieuIds[$b['ten']] = DB::table('thuong_hieus')->insertGetId([
                'ten' => $b['ten'],
                'slug' => Str::slug($b['ten']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ==========================================
        // 2. TẠO DANH MỤC NÂNG CẤP BIẾN THIÊN (Categories)
        // ==========================================
        $categories = [
            'He thong ong xa (Exhaust)',
            'Mam xe hieu nang (Wheels)',
            'Phan mem & Toi uu ECU (Tuning)',
            'Cong nghe xe dien & Hybrid (EV Tech)'
        ];

        $danhMucIds = [];
        foreach ($categories as $c) {
            $danhMucIds[$c] = DB::table('danh_mucs')->insertGetId([
                'ten' => $c,
                'slug' => Str::slug($c),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ==========================================
        // 3. CẤU HÌNH AI STUDIO MẶC ĐỊNH (AI Settings)
        // ==========================================
        DB::table('ai_configs')->insert([
            [
                'key' => 'system_prompt',
                'value' => "Bạn là một AI chuyên gia tư vấn kỹ thuật và nâng cấp xe cao cấp tại garage Porsche Workshop. Nhiệm vụ của bạn là trả lời các câu hỏi về dòng xe Porsche (911, Panamera, Cayenne, Macan, Taycan...) và tư vấn các gói độ hiệu năng chuyên sâu. Hãy trả lời bằng tiếng Việt chuyên nghiệp, sang trọng, am hiểu kỹ thuật sâu sắc.",
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'temperature',
                'value' => "0.7",
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        // ==========================================
        // 4. TẠO DANH SÁCH SẢN PHẨM & CÔNG NGHỆ THỰC TẾ TRONG TƯƠNG LAI (Products & Tech)
        // ==========================================
        $products = [
            // --- DANH MỤC: HỆ THỐNG ỐNG XẢ (PHẦN CỨNG) ---
            [
                'ten' => 'Hệ thống ống xả Slip-On Titanium Akrapovič cho Porsche 911 Turbo S (992)',
                'loai' => 'phan_cung',
                'gia' => 245000000, // 245 Triệu
                'so_luong_kho' => 5,
                'danh_muc' => 'He thong ong xa (Exhaust)',
                'thuong_hieu' => 'Akrapovič',
                'mo_ta' => 'Hệ thống xả làm hoàn toàn bằng hợp kim Titanium siêu nhẹ, giúp giảm trọng lượng xe, tối ưu luồng khí thải và đem lại âm thanh uy lực đặc trưng của dòng xe đua.',
                'thong_so' => [
                    'vat_lieu' => 'Titanium siêu cao cấp',
                    'trong_luong_giam' => '-8.4 kg',
                    'cong_suat_tang' => '+11.2 mã lực',
                    'mo_men_xoan_tang' => '+14.5 Nm'
                ]
            ],
            [
                'ten' => 'Bộ ống xả thể thao TechArt Sport Exhaust kèm van On/Off cho Cayenne Coupe',
                'loai' => 'phan_cung',
                'gia' => 165000000,
                'so_luong_kho' => 3,
                'danh_muc' => 'He thong ong xa (Exhaust)',
                'thuong_hieu' => 'TechArt',
                'mo_ta' => 'Hệ thống pô điều khiển van thông minh tích hợp trực tiếp vào nút bấm chuyển chế độ lái nguyên bản trên vô lăng Porsche.',
                'thong_so' => [
                    'co_che_van' => 'Van dien tu thong minh (Valve-controlled)',
                    'dau_pout_tip' => 'Bọc sợi Carbon dập nổi logo TechArt',
                    'tuong_thich' => 'Porsche Cayenne V6 & V8 (Model 2024+)'
                ]
            ],

            // --- DANH MỤC: MÂM XE HIỆU NĂNG (PHẦN CỨNG) ---
            [
                'ten' => 'Bộ mâm nhôm đúc nguyên khối HRE Monoblok P101 SC cho Porsche 911 GT3',
                'loai' => 'phan_cung',
                'gia' => 380000000, // 380 Triệu
                'so_luong_kho' => 2,
                'danh_muc' => 'Mam xe hieu nang (Wheels)',
                'thuong_hieu' => 'HRE Performance Wheels',
                'mo_ta' => 'Tuyệt tác mâm đúc Aerospace-grade aluminum 6061-T6 thiết kế đa chấu tinh xảo, tối ưu độ cứng vững tối đa cho môi trường đường đua khắc nghiệt.',
                'thong_so' => [
                    'kich_thuoc' => 'Front: 20x9.5 | Rear: 21x12.5',
                    'ket_cau' => 'Monoblok (Đúc nguyên khối 1 mảnh)',
                    'loai_khoa' => 'Center Lock (Khóa tâm chuyên dụng Porsche GT3)'
                ]
            ],

            // --- DANH MỤC: PHẦN MỀM & TỐI ƯU ECU (PHẦN MỀM) ---
            [
                'ten' => 'Gói Can thiệp Phần mềm Công suất Remap ECU Stage 2 cho Porsche Macan GTS',
                'loai' => 'phan_mem',
                'gia' => 55000000,
                'so_luong_kho' => 999, // Phần mềm vô hạn kho
                'danh_muc' => 'Phan mem & Toi uu ECU (Tuning)',
                'thuong_hieu' => 'Porsche OEM Software',
                'mo_ta' => 'Gói tối ưu hóa Map xăng lửa chuyên sâu trên bàn DynoJet, tinh chỉnh áp suất Turbo giúp xe bứt tốc mượt mà, loại bỏ hoàn toàn độ trễ ga.',
                'thong_so' => [
                    'cong_suat_nguyen_ban' => '434 HP',
                    'cong_suat_sau_remap' => '510 HP (+76 HP)',
                    'mo_men_xoan_dat_duoc' => '680 Nm (Nguyên bản 550 Nm)',
                    'yeu_cau_phand_cung' => 'Cần nâng cấp Downpipe thông thoáng khí thải'
                ]
            ],
            [
                'ten' => 'Cập nhật Phần mềm Hộp số PDK Tuning - Mở rộng tốc độ sang số nhanh',
                'loai' => 'phan_mem',
                'gia' => 35000000,
                'so_luong_kho' => 999,
                'danh_muc' => 'Phan mem & Toi uu ECU (Tuning)',
                'thuong_hieu' => 'TechArt',
                'mo_ta' => 'Tối ưu hóa áp suất dầu và phần mềm điều khiển hộp số ly hợp kép PDK của Porsche, giúp giảm thời gian sang số đi 20% ở chế độ Sport+.',
                'thong_so' => [
                    'thoi_gian_sang_so' => 'Giảm 0.05 giây/mỗi lần chuyển số',
                    'tinh_nang_mo_khoa' => 'Launch Control tối ưu độ bám đường, tăng giới hạn vòng tua máy lên 8,200 rpm'
                ]
            ],

            // --- DANH MỤC: CÔNG NGHỆ XE ĐIỆN & HYBRID (ĐÓN ĐẦU CÔNG NGHỆ MỚI / TƯƠNG LAI) ---
            [
                'ten' => 'Gói kích hoạt Hệ thống âm thanh giả lập thể thao Porsche Electric Sport Sound cho Taycan EV',
                'loai' => 'dien_tu',
                'gia' => 28000000,
                'so_luong_kho' => 50,
                'danh_muc' => 'Cong nghe xe dien & Hybrid (EV Tech)',
                'thuong_hieu' => 'Porsche OEM Software',
                'mo_ta' => 'Kích hoạt gói âm thanh thể thao điện tử độc quyền thông qua cổng máy quét chuyên dụng PIWIS 4. Âm thanh thay đổi linh hoạt theo cao độ và chân ga của mô tơ điện.',
                'thong_so' => [
                    'phuong_thuc_kich_hoat' => 'Chạy mã Code thông qua thiết bị ODX/PIWIS',
                    'he_thong_phat' => 'Đồng bộ hóa hệ thống loa ngoài của xe và loa nội thất Burmester/Bose',
                    'tinh_nang_kem_theo' => 'Mở khóa tính năng hỗ trợ giữ làn đường chủ động nâng cao (Active Lane Keeping)'
                ]
            ],
            [
                'ten' => 'Hệ thống Quản lý dòng điện và Tản nhiệt Pin Hiệu năng cao cho Porsche Panamera E-Hybrid',
                'loai' => 'cong_nghe_moi',
                'gia' => 120000000,
                'so_luong_kho' => 8,
                'danh_muc' => 'Cong nghe xe dien & Hybrid (EV Tech)',
                'thuong_hieu' => 'KW Automotive',
                'mo_ta' => 'Giải pháp giải nhiệt chất lỏng cải tiến dành riêng cho các cell pin Hybrid, giúp duy trì dòng điện xả cực đại ổn định lâu hơn khi chạy track hoặc điều kiện nắng nóng.',
                'thong_so' => [
                    'cong_nghe' => 'Liquid-Cooled Smart Matrix System',
                    'hieu_qua_nhiet' => 'Giảm 15 độ C cho lõi pin khi sạc nhanh hoặc chạy gắt',
                    'thoi_gian_bao_hanh' => '5 năm toàn cầu'
                ]
            ],
            [
                'ten' => 'Gói nâng cấp Mô-tơ điện tăng tốc tức thời Boost Mode (Mở khóa 2026+ Tech)',
                'loai' => 'cong_nghe_moi',
                'gia' => 95000000,
                'so_luong_kho' => 100,
                'danh_muc' => 'Cong nghe xe dien & Hybrid (EV Tech)',
                'thuong_hieu' => 'Porsche OEM Software',
                'mo_ta' => 'Bản cập nhật Firmware can thiệp vào bộ biến tần (Inverter) của xe điện Porsche thế hệ mới, mở khóa thêm tính năng Overboost cung cấp thêm 50kW công suất trong vòng 10 giây khi nhấn kịch ga.',
                'thong_so' => [
                    'cong_suat_tang_cuong' => '+50 kW (+68 mã lực tực thì)',
                    'giao_dien_hien_thi' => 'Hiển thị thanh đếm ngược thời gian Boost ngay trên màn hình HUD kính lái',
                    'phien_ban_firmware' => 'P-OTA v4.0.12'
                ]
            ]
        ];

        // Duyệt mảng nạp toàn bộ sản phẩm siêu khủng vào cơ sở dữ liệu
        foreach ($products as $p) {
            DB::table('san_phams')->insert([
                'ten' => $p['ten'],
                'slug' => Str::slug($p['ten']),
                'loai' => $p['loai'],
                'gia' => $p['gia'],
                'so_luong_kho' => $p['so_luong_kho'],
                'danh_muc_id' => $danhMucIds[$p['danh_muc']],
                'thuong_hieu_id' => $thuongHieuIds[$p['thuong_hieu']],
                'mo_ta' => $p['mo_ta'],
                'thong_so_chi_tiet' => json_encode($p['thong_so'], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Bật lại kiểm tra khóa ngoại sau khi nạp xong dữ liệu an toàn
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('=====> [SUCCESS]: Hệ thống Seeder Đồ chơi & Công nghệ Porsche siêu cấp đã nạp thành công rực rỡ! <=====');
    }
}
