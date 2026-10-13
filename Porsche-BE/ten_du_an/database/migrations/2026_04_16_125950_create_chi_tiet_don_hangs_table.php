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
        // Đã sửa thành 'chi_tiet_don_hangs' có chữ 's' để đồng bộ với DB thực tế của bạn
        Schema::create('chi_tiet_don_hangs', function (Blueprint $table) {
            $table->id();

            // Lưu ý: Đảm bảo tên bảng đơn hàng ở constrained() khớp với bảng của bạn (ví dụ: 'don_hangs' hoặc 'don_hang')
            $table->foreignId('don_hang_id')->constrained('don_hangs')->cascadeOnDelete();

            $table->foreignId('san_pham_id')->constrained('san_phams')->cascadeOnDelete();
            $table->integer('so_luong')->default(1);
            $table->decimal('don_gia', 15, 2);
            $table->decimal('thanh_tien', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_don_hangs');
    }
};
