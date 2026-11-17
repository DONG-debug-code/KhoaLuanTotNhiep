<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('danh_gia_phu_tungs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('phu_tung_id')->index();
            $table->unsignedTinyInteger('so_sao');
            $table->text('noi_dung')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'phu_tung_id']);
        });

        Schema::table('danh_gia_chuyen_gias', function (Blueprint $table) {
            if (!Schema::hasColumn('danh_gia_chuyen_gias', 'id_phieu_dat_lich')) {
                // Mỗi lịch đã hoàn thành chỉ được đánh giá một lần.
                $table->unsignedBigInteger('id_phieu_dat_lich')->nullable()->unique();
            }
        });
    }

    public function down(): void
    {
        Schema::table('danh_gia_chuyen_gias', function (Blueprint $table) {
            if (Schema::hasColumn('danh_gia_chuyen_gias', 'id_phieu_dat_lich')) {
                $table->dropUnique(['id_phieu_dat_lich']);
                $table->dropColumn('id_phieu_dat_lich');
            }
        });
        Schema::dropIfExists('danh_gia_phu_tungs');
    }
};
