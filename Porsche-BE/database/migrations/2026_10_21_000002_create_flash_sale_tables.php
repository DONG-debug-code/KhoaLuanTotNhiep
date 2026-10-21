<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chuong_trinh_sales', function (Blueprint $table) {
            $table->id();
            $table->string('ten_su_kien');
            $table->string('slug')->unique();
            $table->string('banner_url')->nullable();
            $table->text('mo_ta')->nullable();
            $table->dateTime('ngay_bat_dau');
            $table->dateTime('ngay_ket_thuc');
            $table->boolean('trang_thai')->default(true);
            $table->timestamps();
            $table->index(['trang_thai', 'ngay_bat_dau', 'ngay_ket_thuc'], 'idx_active_sales');
        });

        Schema::create('chi_tiet_san_pham_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chuong_trinh_sale_id')->constrained('chuong_trinh_sales')->cascadeOnDelete();
            $table->foreignId('san_pham_id')->constrained('san_phams')->cascadeOnDelete();
            $table->decimal('gia_goc', 15, 2);
            $table->decimal('gia_khuyen_mai', 15, 2);
            $table->enum('loai_giam_gia', ['phan_tram', 'so_tien'])->default('phan_tram');
            $table->decimal('gia_tri_giam', 15, 2)->default(0);
            $table->unsignedInteger('so_luong_sale')->default(1);
            $table->unsignedInteger('da_ban')->default(0);
            $table->unsignedInteger('gioi_han_moi_khach')->default(1);
            $table->timestamps();
            $table->unique(['chuong_trinh_sale_id', 'san_pham_id'], 'uk_sale_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_san_pham_sales');
        Schema::dropIfExists('chuong_trinh_sales');
    }
};
