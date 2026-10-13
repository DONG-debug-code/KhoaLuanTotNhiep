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
        Schema::create('don_hangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('users_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('tong_tien', 15, 2)->default(0);
            $table->decimal('giam_gia', 15, 2)->default(0);
            $table->decimal('thanh_tien', 15, 2)->default(0);
            $table->string('trang_thai')->default('cho_xac_nhan'); // cho_xac_nhan, da_xac_nhan, dang_giao, hoan_thanh, huy
            $table->string('phuong_thuc_tt'); // cod, vnpay, momo
            $table->unsignedBigInteger('dia_chi_giao_hang_id')->nullable();
            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('don_hangs');
    }
};
