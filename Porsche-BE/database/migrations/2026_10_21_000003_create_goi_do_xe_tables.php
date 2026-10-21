<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goi_do_xes', function (Blueprint $table) {
            $table->id();
            $table->string('ten_goi');
            $table->string('slug')->unique();
            $table->text('mo_ta')->nullable();
            $table->foreignId('mau_xe_id')->nullable()->constrained('mau_xes')->nullOnDelete();
            $table->decimal('tong_gia_goc', 15, 2)->default(0);
            $table->decimal('gia_goi_khuyen_mai', 15, 2)->default(0);
            $table->dateTime('ngay_bat_dau')->nullable();
            $table->dateTime('ngay_ket_thuc')->nullable();
            $table->boolean('trang_thai')->default(true);
            $table->timestamps();
        });

        Schema::create('chi_tiet_goi_do_xes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goi_do_xe_id')->constrained('goi_do_xes')->cascadeOnDelete();
            $table->foreignId('san_pham_id')->constrained('san_phams')->cascadeOnDelete();
            $table->unsignedInteger('so_luong')->default(1);
            $table->decimal('don_gia_khuyen_nghi', 15, 2)->default(0);
            $table->boolean('la_qua_tang')->default(false);
            $table->string('ghi_chu_qua_tang')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_goi_do_xes');
        Schema::dropIfExists('goi_do_xes');
    }
};
