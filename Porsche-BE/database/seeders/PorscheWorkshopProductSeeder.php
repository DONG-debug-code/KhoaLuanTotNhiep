<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PorscheWorkshopProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Lấy ID Hãng xe: Lấy ID đầu tiên có sẵn trong bảng hang_xes
        $hangXeId = DB::table('hang_xes')->value('id') ?? 1;

        // 2. Lấy ID Danh mục: Lấy danh sách ID từ bảng danh_mucs
        $allDanhMucIds = DB::table('danh_mucs')->pluck('id')->toArray();

        $danhMucExhaust = $allDanhMucIds[0] ?? 1;
        $danhMucWheels  = $allDanhMucIds[1] ?? ($allDanhMucIds[0] ?? 1);
        $danhMucTuning  = $allDanhMucIds[2] ?? ($allDanhMucIds[0] ?? 1);
        $danhMucEV      = $allDanhMucIds[3] ?? ($allDanhMucIds[0] ?? 1);

        // 3. Mảng dữ liệu sản phẩm cao cấp Porsche và thông số JSON
        $products = [
            [
                'ten' => 'Hệ thống xả Slip-On Titanium Akrapovič cho Porsche 911 Turbo S (992)',
                'loai' => 'phan_cung',
                'gia' => 245000000,
                'so_luong' => 5,
                'danh_muc_id' => $danhMucExhaust,
                'mo_ta' => 'Hệ thống xả cao cấp làm hoàn toàn bằng hợp kim Titanium siêu nhẹ, giảm trọng lượng xe, tối ưu luồng khí thải cuồn cuộn và đem lại âm thanh phấn khích đặc trưng.',
                'thong_so' => [
                    'vat_lieu' => 'Titanium Premium',
                    'trong_luong_giam' => '-8.4 kg',
                    'cong_suat_tang' => '+11.2 mã lực'
                ]
            ],
            [
                'ten' => 'Bộ mâm nhôm đúc nguyên khối HRE Monoblok P101 SC cho Porsche 911 GT3',
                'loai' => 'phan_cung',
                'gia' => 380000000,
                'so_luong' => 2,
                'danh_muc_id' => $danhMucWheels,
                'mo_ta' => 'Tuyệt tác mâm đúc Aerospace-grade aluminum thiết kế đa chấu tinh xảo, tối ưu hóa triệt để trọng lượng không được treo (Unsprung weight) cho đường đua.',
                'thong_so' => [
                    'kich_thuoc' => 'Front: 20x9.5 | Rear: 21x12.5',
                    'ket_cau' => 'Monoblok',
                    'loai_khoa' => 'Center Lock'
                ]
            ],
            [
                'ten' => 'Gói tối ưu hóa phần mềm công suất Remap ECU Stage 2 cho Porsche Macan GTS',
                'loai' => 'phan_mem',
                'gia' => 55000000,
                'so_luong' => 999,
                'danh_muc_id' => $danhMucTuning,
                'mo_ta' => 'Gói tinh chỉnh Map xăng lửa chuyên sâu trên bàn DynoJet, tối ưu áp suất Turbo giúp xe bứt tốc mượt mà, loại bỏ hoàn toàn độ trễ ga nguyên bản.',
                'thong_so' => [
                    'cong_suat_nguyen_ban' => '434 HP',
                    'cong_suat_sau_remap' => '510 HP (+76 HP)',
                    'giao_thuc_nap' => 'Qua cổng OBD2 bảo mật'
                ]
            ],
            [
                'ten' => 'Gói kích hoạt Hệ thống âm thanh giả lập thể thao Porsche Electric Sport Sound cho Taycan EV',
                'loai' => 'cong_nghe_moi',
                'gia' => 28000000,
                'so_luong' => 100,
                'danh_muc_id' => $danhMucEV,
                'mo_ta' => 'Bản cập nhật Firmware kích hoạt gói âm thanh thể thao điện tử độc quyền thông qua thiết bị máy quét chuyên dụng PIWIS 4. Âm thanh thay đổi linh hoạt theo bước chân ga mô-tơ.',
                'thong_so' => [
                    'phuong_thuc_kich_hoat' => 'Chạy mã Code thông qua ODX/PIWIS 4',
                    'he_thong_loa_ap_dung' => 'Đồng bộ hóa loa ngoài thân xe và hệ thống loa nội thất'
                ]
            ]
        ];

        // 4. Thực hiện nạp dữ liệu bằng vòng lặp thông minh tự động thích ứng cấu trúc cột bảng
        foreach ($products as $p) {
            // Mảng insert dữ liệu động cơ bản
            $insertData = [
                'gia'               => $p['gia'],
                'mo_ta'             => $p['mo_ta'],
                'loai_san_pham'     => $p['loai'],
                'thong_so_chi_tiet' => json_encode($p['thong_so'], JSON_UNESCAPED_UNICODE),
                'created_at'        => now(),
                'updated_at'        => now(),
            ];

            // 🔍 Bơm dữ liệu vào CẢ HAI cột Tên để triệt tiêu lỗi "doesn't have a default value"
            if (DB::getSchemaBuilder()->hasColumn('san_phams', 'ten')) {
                $insertData['ten'] = $p['ten'];
            }
            if (DB::getSchemaBuilder()->hasColumn('san_phams', 'ten_san_pham')) {
                $insertData['ten_san_pham'] = $p['ten'];
            }

            // 🔍 Bơm dữ liệu vào CẢ HAI cột Số lượng kho để triệt tiêu lỗi validation của DB
            if (DB::getSchemaBuilder()->hasColumn('san_phams', 'so_luong')) {
                $insertData['so_luong'] = $p['so_luong'];
            }
            if (DB::getSchemaBuilder()->hasColumn('san_phams', 'so_luong_kho')) {
                $insertData['so_luong_kho'] = $p['so_luong'];
            }

            // 🔍 Kiểm tra tự động cột Hãng xe
            if (DB::getSchemaBuilder()->hasColumn('san_phams', 'hang_xe_id')) {
                $insertData['hang_xe_id'] = $hangXeId;
            } elseif (DB::getSchemaBuilder()->hasColumn('san_phams', 'id_hang_xe')) {
                $insertData['id_hang_xe'] = $hangXeId;
            }

            // 🔍 Kiểm tra tự động cột Mẫu xe (Dựa trên lỗi log trước thấy bạn có cột mau_xe_id)
            if (DB::getSchemaBuilder()->hasColumn('san_phams', 'mau_xe_id')) {
                $insertData['mau_xe_id'] = DB::table('mau_xes')->value('id') ?? 1;
            }

            // 🔍 Kiểm tra tự động cột Danh mục
            if (DB::getSchemaBuilder()->hasColumn('san_phams', 'danh_muc_id')) {
                $insertData['danh_muc_id'] = $p['danh_muc_id'];
            } elseif (DB::getSchemaBuilder()->hasColumn('san_phams', 'id_danh_muc')) {
                $insertData['id_danh_muc'] = $p['danh_muc_id'];
            }

            // 🔍 Kiểm tra tự động cột Slug
            if (DB::getSchemaBuilder()->hasColumn('san_phams', 'slug')) {
                $insertData['slug'] = Str::slug($p['ten']);
            }

            // Tiến hành chèn bản ghi sau khi đã đồng bộ toàn bộ các trường tên cột
            DB::table('san_phams')->insert($insertData);
        }

        $this->command->info('-> [OK] PorscheWorkshopProductSeeder đã nạp toàn bộ dữ liệu Porsche thích ứng hệ thống thành công!');
    }
}
