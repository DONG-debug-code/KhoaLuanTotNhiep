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
        Schema::create('nhat_ky_khos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Ai thực hiện (Admin/Staff)
            $table->foreignId('san_pham_id')->constrained('san_phams')->cascadeOnDelete(); // Biến động sản phẩm nào
            $table->string('loai'); // nhap hoặc xuat
            $table->integer('so_luong'); // Số lượng tăng/giảm
            $table->string('ly_do')->nullable(); // Ví dụ: "Duyệt đơn hàng #12", "Hoàn kho đơn #15"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhat_ky_khos');
    }
};
