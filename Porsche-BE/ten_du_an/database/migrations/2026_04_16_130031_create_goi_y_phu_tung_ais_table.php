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
        Schema::create('goi_y_phu_tung_ais', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('san_pham_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('phu_tung_id')->nullable()->constrained()->nullOnDelete();

            $table->text('input_user');     // user hỏi gì

            $table->integer('do_tin_cay')->default(0); // % AI tin tưởng

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goi_y_phu_tung_ais');
    }
};
