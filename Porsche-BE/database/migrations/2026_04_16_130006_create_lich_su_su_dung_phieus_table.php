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
        Schema::create('lich_su_su_dung_phieus', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->foreignId('phieu_id')
                ->constrained('phieu_giam_gias')
                ->cascadeOnDelete();

            $table->foreignId('don_hang_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->decimal('so_tien_giam', 15, 2)->default(0);

            $table->string('trang_thai')->default('da_su_dung'); // da_su_dung, huy
            $table->timestamp('thoi_diem_su_dung')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lich_su_su_dung_phieus');
    }
};
