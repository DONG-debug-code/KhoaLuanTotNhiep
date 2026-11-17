<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nhật ký thao tác quản lý tài khoản (khóa/mở khóa, đổi quyền) của quản trị viên.
        Schema::create('nhat_ky_tai_khoans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('loai_tai_khoan', 20);
            $table->unsignedBigInteger('id_tai_khoan');
            // khoa | mo_khoa | doi_vai_tro
            $table->string('hanh_dong', 20);
            $table->text('ly_do')->nullable();
            $table->json('du_lieu')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['loai_tai_khoan', 'id_tai_khoan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nhat_ky_tai_khoans');
    }
};
