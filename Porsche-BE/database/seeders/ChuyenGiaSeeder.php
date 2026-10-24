<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChuyenGiaSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $experts = [
            [
                'ma_so_tuner' => 'TUN-001', 'ho_ten' => 'Nguyễn Hoàng Minh', 'email' => 'minh.nguyen@tuner.vn',
                'so_dien_thoai' => '0901 234 561', 'ngay_sinh' => '1986-03-14', 'gioi_tinh' => 'Nam', 'dia_chi' => 'Quận 7, TP. Hồ Chí Minh',
                'chuc_danh' => 'Trưởng nhóm Tuner',
                'mo_ta' => 'Chuyên sâu nâng cấp hệ thống nạp – xả và tinh chỉnh ECU cho dòng 911. Từng làm việc tại trung tâm kỹ thuật Porsche châu Á – Thái Bình Dương.',
                'so_nam_kinh_nghiem' => 12, 'so_du_an_hoan_thanh' => 486, 'tinh_trang' => 'san-sang',
                'chuyen_mon' => ['Độ động cơ', 'Tuning ECU', '911 / Taycan'], 'dong_xe' => ['911', '718'],
            ],
            [
                'ma_so_tuner' => 'TUN-002', 'ho_ten' => 'Trần Quốc Bảo', 'email' => 'bao.tran@tuner.vn',
                'so_dien_thoai' => '0902 345 672', 'ngay_sinh' => '1989-07-02', 'gioi_tinh' => 'Nam', 'dia_chi' => 'Thủ Đức, TP. Hồ Chí Minh',
                'chuc_danh' => 'Chuyên gia hệ thống treo',
                'mo_ta' => 'Chuyên gia cân chỉnh hệ thống treo và góc đặt bánh cho các mẫu Cayman, Cayenne. Đam mê các cung đường đèo và track day.',
                'so_nam_kinh_nghiem' => 9, 'so_du_an_hoan_thanh' => 312, 'tinh_trang' => 'dang-kin-lich',
                'chuyen_mon' => ['Coilover', 'Treo khí nén', 'Gầm xe'], 'dong_xe' => ['718', 'Cayenne'],
            ],
            [
                'ma_so_tuner' => 'TUN-003', 'ho_ten' => 'Lê Thị Thu Hà', 'email' => 'ha.le@tuner.vn',
                'so_dien_thoai' => '0903 456 783', 'ngay_sinh' => '1992-11-23', 'gioi_tinh' => 'Nữ', 'dia_chi' => 'Quận 2, TP. Hồ Chí Minh',
                'chuc_danh' => 'Cố vấn kỹ thuật',
                'mo_ta' => 'Cố vấn kỹ thuật cho dòng xe điện Taycan và các buổi bảo dưỡng định kỳ. Chứng chỉ kỹ thuật viên cao cấp của Porsche.',
                'so_nam_kinh_nghiem' => 7, 'so_du_an_hoan_thanh' => 528, 'tinh_trang' => 'san-sang',
                'chuyen_mon' => ['Bảo dưỡng định kỳ', 'Chẩn đoán', 'Taycan EV'], 'dong_xe' => ['Taycan'],
            ],
            [
                'ma_so_tuner' => 'TUN-004', 'ho_ten' => 'Phạm Tuấn Kiệt', 'email' => 'kiet.pham@tuner.vn',
                'so_dien_thoai' => '0904 567 894', 'ngay_sinh' => '1995-05-30', 'gioi_tinh' => 'Nam', 'dia_chi' => 'Bình Thạnh, TP. Hồ Chí Minh',
                'chuc_danh' => 'Chuyên gia nội thất & độ xe',
                'mo_ta' => 'Phụ trách các gói độ nội thất da cao cấp, ốp carbon và tùy biến ngoại thất theo yêu cầu riêng của từng khách hàng.',
                'so_nam_kinh_nghiem' => 5, 'so_du_an_hoan_thanh' => 274, 'tinh_trang' => 'tam-nghi',
                'chuyen_mon' => ['Nội thất da', 'Carbon', 'Độ ngoại thất'], 'dong_xe' => ['Panamera', 'Cayenne'],
            ],
        ];

        $reviewers = ['Nguyễn Văn An', 'Trần Thị Mai', 'Lê Đức Huy', 'Phạm Gia Bảo', 'Võ Thanh Tùng', 'Đặng Ngọc Lan'];
        $comments = [
            5 => ['Làm việc cực kỳ chuyên nghiệp, xe vận hành đúng như mong đợi.', 'Tư vấn nhiệt tình, hoàn thành đúng hẹn.', 'Tay nghề rất cao, rất đáng tin cậy.'],
            4 => ['Hài lòng với kết quả, thời gian hoàn thành hơi lâu một chút.', 'Làm tốt, giải thích chi tiết từng hạng mục.'],
        ];
        $jobs = [
            'TUN-001' => [['Nâng cấp hệ thống nạp – xả', 'Porsche 911 Carrera S'], ['Tuning ECU Stage 1', 'Porsche 718 Cayman GTS'], ['Tinh chỉnh ECU & intercooler', 'Porsche 911 Turbo']],
            'TUN-002' => [['Lắp bộ coilover thể thao', 'Porsche 718 Cayman'], ['Cân chỉnh góc đặt bánh', 'Porsche Cayenne Coupe'], ['Lắp treo khí nén', 'Porsche Cayenne']],
            'TUN-003' => [['Bảo dưỡng định kỳ 20.000 km', 'Porsche Taycan 4S'], ['Chẩn đoán hệ thống pin cao áp', 'Porsche Taycan Turbo'], ['Kiểm tra tổng quát trước chuyến đi xa', 'Porsche Macan']],
            'TUN-004' => [['Bọc da nội thất tùy chỉnh', 'Porsche Panamera'], ['Ốp carbon ngoại thất', 'Porsche Cayenne'], ['Độ bodykit ngoại thất', 'Porsche Panamera GTS']],
        ];
        $shifts = ['Ca sáng (08:00 - 12:00)', 'Ca chiều (13:00 - 17:00)'];

        foreach ($experts as $index => $expert) {
            $code = $expert['ma_so_tuner'];
            DB::table('chuyen_gias')->updateOrInsert(
                ['ma_so_tuner' => $code],
                array_merge($expert, [
                    'chuyen_mon' => json_encode($expert['chuyen_mon'], JSON_UNESCAPED_UNICODE),
                    'dong_xe' => json_encode($expert['dong_xe'], JSON_UNESCAPED_UNICODE),
                    'trang_thai' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
            $id = DB::table('chuyen_gias')->where('ma_so_tuner', $code)->value('id');
            DB::table('chuyen_gias')->where('id', $id)->whereNull('password')
                ->update(['password' => \Illuminate\Support\Facades\Hash::make(env('EXPERT_DEFAULT_PASSWORD', 'Porsche@123'))]);

            DB::table('lich_lam_viecs')->where('id_chuyen_gia', $id)->delete();
            if ($expert['tinh_trang'] !== 'tam-nghi') {
                for ($day = 0; $day < 14; $day++) {
                    $date = now()->addDays($day);
                    if ($date->isSunday()) {
                        continue;
                    }
                    foreach ($shifts as $shift) {
                        DB::table('lich_lam_viecs')->insert([
                            'id_chuyen_gia' => $id, 'ngay_lam_viec' => $date->toDateString(),
                            'ca_lam_viec' => $shift, 'created_at' => $now, 'updated_at' => $now,
                        ]);
                    }
                }
            }

            DB::table('danh_gia_chuyen_gias')->where('id_chuyen_gia', $id)->delete();
            foreach ($reviewers as $i => $name) {
                $stars = ($i + $index) % 4 === 0 ? 4 : 5;
                $options = $comments[$stars];
                DB::table('danh_gia_chuyen_gias')->insert([
                    'id_chuyen_gia' => $id, 'ten_khach_hang' => $name, 'so_sao' => $stars,
                    'noi_dung' => $options[($i + $index) % count($options)],
                    'created_at' => now()->subDays(($i + 1) * 9), 'updated_at' => $now,
                ]);
            }

            DB::table('lich_su_cong_viec_chuyen_gias')->where('id_chuyen_gia', $id)->delete();
            foreach ($jobs[$code] as $i => [$title, $car]) {
                DB::table('lich_su_cong_viec_chuyen_gias')->insert([
                    'id_chuyen_gia' => $id, 'tieu_de' => $title, 'ten_xe' => $car,
                    'mo_ta' => 'Hoàn thành đúng tiến độ, đã bàn giao và kiểm tra cùng khách hàng.',
                    'ngay_hoan_thanh' => now()->subDays(12 + $i * 21)->toDateString(),
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }
}
