<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('phieu_giam_gias', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();

            $table->enum('loai', ['phan_tram', 'tien_mat']); // % hoặc tiền

            $table->integer('gia_tri'); // 10 (%) hoặc 500000 (VNĐ)

            $table->decimal('gia_tri_don_hang_toi_thieu', 15, 2)->default(0);

            $table->decimal('giam_toi_da', 15, 2)->nullable(); // chỉ dùng cho %

            $table->integer('so_luong')->default(0); // số lần dùng

            $table->timestamp('ngay_bat_dau')->nullable();
            $table->timestamp('ngay_ket_thuc')->nullable();

            $table->boolean('trang_thai')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phieu_giam_gias');
    }
};
