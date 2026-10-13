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
    Schema::create('phu_tungs', function (Blueprint $table) {
        $table->id();
        $table->string('ten');
        $table->decimal('gia', 15, 2);
        $table->integer('so_luong')->default(0); // <--- THÊM DÒNG NÀY CỰC KỲ ĐẦY ĐỦ VÀ CHUẨN MỰC
        $table->string('loai'); // noi_that, ngoai_that, hieu_suat
        $table->text('mo_ta')->nullable();
        $table->string('anh')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phu_tungs');
    }
};
