<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'ma_san_pham' => fn (Blueprint $table) => $table->string('ma_san_pham')->nullable(),
            'danh_muc_id' => fn (Blueprint $table) => $table->unsignedBigInteger('danh_muc_id')->nullable(),
            'thuong_hieu_id' => fn (Blueprint $table) => $table->unsignedBigInteger('thuong_hieu_id')->nullable(),
            'gia_khuyen_mai' => fn (Blueprint $table) => $table->decimal('gia_khuyen_mai', 15, 2)->nullable(),
            'trang_thai' => fn (Blueprint $table) => $table->boolean('trang_thai')->default(true),
            'dong_co' => fn (Blueprint $table) => $table->string('dong_co')->nullable(),
            'dung_tich_xilanh' => fn (Blueprint $table) => $table->string('dung_tich_xilanh')->nullable(),
            'cong_suat' => fn (Blueprint $table) => $table->string('cong_suat')->nullable(),
            'momen_xoan' => fn (Blueprint $table) => $table->string('momen_xoan')->nullable(),
            'hop_so' => fn (Blueprint $table) => $table->string('hop_so')->nullable(),
            'he_dan_dong' => fn (Blueprint $table) => $table->string('he_dan_dong')->nullable(),
            'tang_toc_0_100' => fn (Blueprint $table) => $table->string('tang_toc_0_100')->nullable(),
            'tang_toc_0_200' => fn (Blueprint $table) => $table->string('tang_toc_0_200')->nullable(),
            'van_toc_toi_da' => fn (Blueprint $table) => $table->string('van_toc_toi_da')->nullable(),
            'quang_duong_phanh' => fn (Blueprint $table) => $table->string('quang_duong_phanh')->nullable(),
            'he_so_luc_can' => fn (Blueprint $table) => $table->string('he_so_luc_can')->nullable(),
            'tieu_thu_nhien_lieu' => fn (Blueprint $table) => $table->string('tieu_thu_nhien_lieu')->nullable(),
            'dung_tich_binh_xang' => fn (Blueprint $table) => $table->string('dung_tich_binh_xang')->nullable(),
            'dung_tich_pin' => fn (Blueprint $table) => $table->string('dung_tich_pin')->nullable(),
            'quang_duong_di_dien' => fn (Blueprint $table) => $table->string('quang_duong_di_dien')->nullable(),
            'thoi_gian_sac_nhanh' => fn (Blueprint $table) => $table->string('thoi_gian_sac_nhanh')->nullable(),
            'kich_thuoc_d_r_c' => fn (Blueprint $table) => $table->string('kich_thuoc_d_r_c')->nullable(),
            'chieu_dai_co_so' => fn (Blueprint $table) => $table->string('chieu_dai_co_so')->nullable(),
            'khoang_sang_gam' => fn (Blueprint $table) => $table->string('khoang_sang_gam')->nullable(),
            'trong_luong_khong_tai' => fn (Blueprint $table) => $table->string('trong_luong_khong_tai')->nullable(),
            'dung_tich_khoang_hanh_ly' => fn (Blueprint $table) => $table->string('dung_tich_khoang_hanh_ly')->nullable(),
            'he_thong_treo' => fn (Blueprint $table) => $table->string('he_thong_treo')->nullable(),
            'he_thong_phanh' => fn (Blueprint $table) => $table->string('he_thong_phanh')->nullable(),
            'mam_lop' => fn (Blueprint $table) => $table->string('mam_lop')->nullable(),
            'danh_lai_banh_sau' => fn (Blueprint $table) => $table->string('danh_lai_banh_sau')->nullable(),
            'den_pha' => fn (Blueprint $table) => $table->string('den_pha')->nullable(),
            'he_thong_am_thanh' => fn (Blueprint $table) => $table->string('he_thong_am_thanh')->nullable(),
            'man_hinh_giai_tri' => fn (Blueprint $table) => $table->string('man_hinh_giai_tri')->nullable(),
            'chat_lieu_noi_that' => fn (Blueprint $table) => $table->string('chat_lieu_noi_that')->nullable(),
        ];

        foreach ($columns as $name => $addColumn) {
            if (!Schema::hasColumn('san_phams', $name)) {
                Schema::table('san_phams', $addColumn);
            }
        }
    }

    public function down(): void
    {
        $columns = [
            'ma_san_pham', 'danh_muc_id', 'thuong_hieu_id', 'gia_khuyen_mai', 'trang_thai',
            'dong_co', 'dung_tich_xilanh', 'cong_suat', 'momen_xoan', 'hop_so', 'he_dan_dong',
            'tang_toc_0_100', 'tang_toc_0_200', 'van_toc_toi_da', 'quang_duong_phanh', 'he_so_luc_can',
            'tieu_thu_nhien_lieu', 'dung_tich_binh_xang', 'dung_tich_pin', 'quang_duong_di_dien',
            'thoi_gian_sac_nhanh', 'kich_thuoc_d_r_c', 'chieu_dai_co_so', 'khoang_sang_gam',
            'trong_luong_khong_tai', 'dung_tich_khoang_hanh_ly', 'he_thong_treo', 'he_thong_phanh',
            'mam_lop', 'danh_lai_banh_sau', 'den_pha', 'he_thong_am_thanh', 'man_hinh_giai_tri',
            'chat_lieu_noi_that',
        ];

        $existingColumns = array_values(array_filter($columns, fn ($column) => Schema::hasColumn('san_phams', $column)));

        if ($existingColumns !== []) {
            Schema::table('san_phams', fn (Blueprint $table) => $table->dropColumn($existingColumns));
        }
    }
};
