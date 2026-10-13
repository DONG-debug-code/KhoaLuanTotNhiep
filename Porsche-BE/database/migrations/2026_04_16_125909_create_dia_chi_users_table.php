<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiaChiUsersTable extends Migration
{
    public function up(): void
    {
        Schema::create('dia_chi_users', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('ho_ten');
            $table->string('so_dien_thoai');
            $table->text('dia_chi');
            $table->string('thanh_pho');
            $table->string('quan_huyen');
            $table->string('phuong_xa');

            $table->boolean('mac_dinh')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dia_chi_users');
    }
}
