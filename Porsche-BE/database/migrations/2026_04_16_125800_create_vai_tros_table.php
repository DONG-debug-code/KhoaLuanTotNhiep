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
        Schema::create('vai_tros', function (Blueprint $table) {
            $table->id();
            $table->string('ten');                        // Tên hiển thị (vd: Trưởng phòng Kinh doanh)
            $table->string('ma_vai_tro')->unique();       // Mã slug dùng trong code (vd: sales_manager)
            $table->text('mo_ta')->nullable();            // Mô tả nhiệm vụ/vai trò
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vai_tros');
    }
};