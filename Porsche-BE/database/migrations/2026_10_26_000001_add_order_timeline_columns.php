<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('don_hangs', function (Blueprint $table) {
            $table->timestamp('xac_nhan_luc')->nullable();
            $table->unsignedBigInteger('xac_nhan_boi')->nullable();
            $table->timestamp('thanh_toan_luc')->nullable();
            $table->timestamp('giao_hang_luc')->nullable();
            $table->timestamp('hoan_thanh_luc')->nullable();
        });

        DB::statement("UPDATE don_hangs o JOIN vnpay_payments p ON p.don_hang_id = o.id AND p.status = 'paid' SET o.thanh_toan_luc = p.paid_at WHERE o.thanh_toan_luc IS NULL");
        DB::statement("UPDATE don_hangs SET xac_nhan_luc = updated_at WHERE trang_thai IN ('da_xac_nhan','dang_giao','hoan_thanh') AND xac_nhan_luc IS NULL");
        DB::statement("UPDATE don_hangs SET giao_hang_luc = updated_at WHERE trang_thai IN ('dang_giao','hoan_thanh') AND giao_hang_luc IS NULL");
        DB::statement("UPDATE don_hangs SET hoan_thanh_luc = updated_at WHERE trang_thai = 'hoan_thanh' AND hoan_thanh_luc IS NULL");
        DB::statement("UPDATE don_hangs SET thanh_toan_luc = hoan_thanh_luc WHERE trang_thai = 'hoan_thanh' AND trang_thai_thanh_toan = 'da_thanh_toan' AND thanh_toan_luc IS NULL");
    }

    public function down(): void
    {
        Schema::table('don_hangs', function (Blueprint $table) {
            $table->dropColumn(['xac_nhan_luc', 'xac_nhan_boi', 'thanh_toan_luc', 'giao_hang_luc', 'hoan_thanh_luc']);
        });
    }
};
