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
        Schema::create('san_phams', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mau_xe_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('ten');
            $table->text('mo_ta')->nullable();

            $table->decimal('gia', 15, 2);
            $table->integer('so_luong')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('san_phams');
    }
};
