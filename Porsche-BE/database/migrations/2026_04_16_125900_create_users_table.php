<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('ho_ten');
            $table->string('email')->unique();
            $table->string('so_dien_thoai', 20)->nullable();
            $table->string('password');
            $table->text('dia_chi')->nullable(); // 👈 Bổ sung địa chỉ
            $table->string('avatar')->nullable();
            $table->tinyInteger('trang_thai')->default(1); // 👈 1: Hoạt động, 0: Khóa/Chờ kích hoạt
            $table->string('ma_kich_hoat', 10)->nullable(); // 👈 Mã OTP kích hoạt mail

            $table->foreignId('vai_tro_id')
                ->constrained('vai_tros')
                ->cascadeOnDelete();

            $table->bigInteger('diem_tich_luy')->default(0);

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};