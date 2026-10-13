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
        Schema::create('nhat_ky_hoat_dong_admins', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->text('hanh_dong');
            $table->string('ip')->nullable();
            $table->string('trinh_duyet')->nullable();
            $table->string('doi_tuong')->nullable(); // san_pham, don_hang...
            $table->bigInteger('doi_tuong_id')->nullable();
            $table->json('du_lieu_cu')->nullable();
            $table->json('du_lieu_moi')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhat_ky_hoat_dong_admins');
    }
};
