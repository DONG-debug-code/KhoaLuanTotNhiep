<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hoa_dons')) {
            Schema::create('hoa_dons', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_phieu_dat_lich')->nullable();
                $table->unsignedBigInteger('id_khach_hang')->nullable();
                $table->foreignId('users_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('don_hang_id')->nullable()->constrained('don_hangs')->nullOnDelete();
                $table->string('ma_hoa_don')->unique();
                $table->decimal('tong_tien', 15, 2)->default(0);
                $table->unsignedTinyInteger('trang_thai')->default(0);
                $table->string('trang_thai_tt')->nullable();
                $table->timestamp('ngay_xuat')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hoa_don_chi_tiets')) {
            Schema::create('hoa_don_chi_tiets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('id_hoa_don')->constrained('hoa_dons')->cascadeOnDelete();
                $table->string('ten_vat_tu');
                $table->unsignedInteger('so_luong')->default(1);
                $table->decimal('don_gia', 15, 2)->default(0);
                $table->decimal('thanh_tien', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hoa_don_chi_tiets');
        Schema::dropIfExists('hoa_dons');
    }
};
