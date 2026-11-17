<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dang_ky_lai_thus', function (Blueprint $table) {
            $table->id();
            $table->string('ma_lai_thu', 30)->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('san_pham_id')->nullable();
            $table->string('dong_xe', 100)->nullable();
            $table->string('ho_ten');
            $table->string('email')->index();
            $table->string('so_dien_thoai', 20);
            $table->date('ngay_hen');
            $table->string('gio_hen', 5);
            $table->string('dia_diem');
            $table->text('ghi_chu')->nullable();
            $table->text('ghi_chu_nhan_vien')->nullable();
            // 0: mới đăng ký, 1: đã xác nhận lịch, 2: đã lái thử, 3: đã hủy
            $table->tinyInteger('trang_thai')->default(0);
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dang_ky_lai_thus');
    }
};
