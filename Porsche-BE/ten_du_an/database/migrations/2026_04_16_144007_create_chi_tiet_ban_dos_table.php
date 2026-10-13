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
        Schema::create('chi_tiet_ban_dos', function (Blueprint $table) {
            $table->id();

            // Khóa ngoại liên kết tới bảng bản đồ (ban_dos)
            $table->foreignId('ban_do_id')
                ->constrained('ban_dos')
                ->cascadeOnDelete();

            // Khóa ngoại liên kết tới bảng phụ tùng (phu_tungs)
            $table->foreignId('phu_tung_id')
                ->constrained('phu_tungs')
                ->cascadeOnDelete();

            // Các cột lưu thông tin chi tiết
            $table->integer('so_luong')->default(1);
            $table->decimal('gia', 15, 2)->default(0);

            $table->timestamps();

            // Đảm bảo tính độc nhất: Trong 1 bản đồ, 1 phụ tùng chỉ xuất hiện 1 dòng (tránh trùng lặp)
            $table->unique(['ban_do_id', 'phu_tung_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_ban_dos');
    }
};
