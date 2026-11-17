<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuoc_tro_chuyens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('nhan_vien_id')->nullable();
            $table->unsignedBigInteger('id_phieu_dat_lich')->nullable()->index();
            // cho: chờ nhân viên nhận | dang: đang trao đổi | dong: đã kết thúc
            $table->string('trang_thai', 10)->default('cho');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'trang_thai']);
            $table->index(['trang_thai', 'last_message_at']);
        });

        Schema::create('tin_nhans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cuoc_tro_chuyen_id')->index();
            // khach_hang | nhan_vien | he_thong
            $table->string('nguoi_gui_loai', 15);
            $table->unsignedBigInteger('nguoi_gui_id')->nullable();
            $table->text('noi_dung');
            $table->timestamp('da_doc_luc')->nullable();
            $table->timestamps();
        });

        Schema::create('lich_su_phieu_dat_lichs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_phieu_dat_lich')->index();
            // xac_nhan | doi_lich | doi_chuyen_gia | huy | tu_dong_huy
            $table->string('loai', 30);
            $table->text('noi_dung');
            $table->json('du_lieu')->nullable();
            $table->string('nguoi_thuc_hien')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lich_su_phieu_dat_lichs');
        Schema::dropIfExists('tin_nhans');
        Schema::dropIfExists('cuoc_tro_chuyens');
    }
};
