<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lich_su_xe_khach_hangs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('xe_khach_hang_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            // nang_cap | thay_doi | bao_duong | cap_nhat_thong_tin | ghi_nhan
            $table->string('loai', 30);
            $table->string('tieu_de', 200);
            $table->text('mo_ta')->nullable();
            // Danh sách thay đổi dạng [{truong, truoc, sau}].
            $table->json('thay_doi')->nullable();
            $table->date('ngay_thuc_hien');
            $table->unsignedBigInteger('id_phieu_dat_lich')->nullable()->index();
            // he_thong | nhan_vien | khach_hang
            $table->string('nguon', 20)->default('he_thong');
            $table->unsignedBigInteger('nguoi_tao_id')->nullable();
            $table->string('nguoi_tao_ten')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lich_su_xe_khach_hangs');
    }
};
