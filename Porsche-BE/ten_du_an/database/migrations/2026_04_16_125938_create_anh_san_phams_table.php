<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anh_san_phams', function (Blueprint $table) {
            $table->id();

            $table->foreignId('san_pham_id')
                ->constrained('san_phams')
                ->cascadeOnDelete();

            $table->string('duong_dan');
            $table->boolean('la_anh_chinh')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anh_san_phams');
    }
};
