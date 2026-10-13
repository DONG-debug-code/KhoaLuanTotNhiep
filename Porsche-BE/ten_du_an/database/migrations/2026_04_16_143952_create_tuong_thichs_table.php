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
        Schema::create('tuong_thichs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('phu_tung_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('mau_xe_id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tuong_thichs');
    }
};
