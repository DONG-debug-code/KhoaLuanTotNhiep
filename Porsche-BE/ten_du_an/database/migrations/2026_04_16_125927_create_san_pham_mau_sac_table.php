<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('san_pham_mau_sac', function (Blueprint $table) {
            $table->id();

            $table->foreignId('san_pham_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('mau_sac_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('gia_them', 12, 2)->default(0);
            $table->integer('so_luong')->default(0);

            $table->timestamps();

            $table->unique(['san_pham_id', 'mau_sac_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('san_pham_mau_sac');
    }
};
