<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiConfigsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Lưu khóa cấu hình (Ví dụ: system_prompt, temperature)
            $table->text('value')->nullable(); // Lưu giá trị cấu hình tương ứng
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_configs');
    }
}
