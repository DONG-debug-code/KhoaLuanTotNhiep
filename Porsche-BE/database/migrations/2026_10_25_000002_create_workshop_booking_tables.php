<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chuyen_gias')) {
            Schema::create('chuyen_gias', function (Blueprint $table) {
                $table->id();
                $table->string('ho_ten');
                $table->string('email')->nullable()->unique();
                $table->string('password')->nullable();
                $table->string('ma_so_tuner')->nullable();
                $table->string('ma_kich_hoat')->nullable();
                $table->tinyInteger('trang_thai')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('khu_vuc_workshops')) {
            Schema::create('khu_vuc_workshops', function (Blueprint $table) {
                $table->id();
                $table->string('ten_khu_vuc');
                $table->tinyInteger('trang_thai')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('phieu_dat_lichs')) {
            Schema::create('phieu_dat_lichs', function (Blueprint $table) {
                $table->id();
                $table->string('ma_dat_lich')->unique();
                $table->unsignedBigInteger('id_khach_hang')->nullable()->index();
                $table->unsignedBigInteger('id_chuyen_gia')->nullable()->index();
                $table->unsignedBigInteger('id_khu_vuc_workshop')->nullable()->index();
                $table->unsignedBigInteger('id_don_hang')->nullable()->index();
                $table->string('ten_khach_hang')->nullable();
                $table->string('email')->nullable();
                $table->string('dien_thoai')->nullable();
                $table->string('bien_so_xe')->nullable();
                $table->string('thong_tin_xe')->nullable();
                $table->date('ngay_dat');
                $table->string('gio_dat', 10);
                $table->text('ghi_chu')->nullable();
                $table->tinyInteger('trang_thai')->default(0);
                $table->timestamp('reminder_sent_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Các bảng này có thể do hệ thống khác tạo, không xóa khi rollback.
    }
};
