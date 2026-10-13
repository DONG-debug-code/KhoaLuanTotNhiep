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
        Schema::create('permissions', function (Blueprint $table) {
    $table->id();
    $table->string('ten_chuc_nang');                  // Ví dụ: Tạo phiếu dịch vụ
    $table->string('ma_chuc_nang')->unique();         // Ví dụ: create_service_order
    $table->text('mo_ta')->nullable();                // Ví dụ: Quyền lập phiếu tiếp nhận xe cho xưởng
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
