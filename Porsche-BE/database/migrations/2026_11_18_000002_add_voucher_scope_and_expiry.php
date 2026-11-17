<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phieu_giam_gias', function (Blueprint $table) {
            if (!Schema::hasColumn('phieu_giam_gias', 'gia_san_pham_toi_da')) {
                // Chỉ giảm cho sản phẩm có đơn giá nhỏ hơn mức này (null: áp dụng mọi sản phẩm).
                $table->decimal('gia_san_pham_toi_da', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('phieu_giam_gias', 'gioi_han_moi_khach')) {
                // Số lần mỗi khách được dùng mã (null: không giới hạn theo khách).
                $table->unsignedSmallInteger('gioi_han_moi_khach')->nullable();
            }
            if (!Schema::hasColumn('phieu_giam_gias', 'nhom')) {
                // chao_mung | chi_tieu: voucher do hệ thống tự cấp.
                $table->string('nhom', 30)->nullable()->index();
            }
        });

        Schema::table('user_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('user_vouchers', 'het_han_luc')) {
                $table->timestamp('het_han_luc')->nullable();
            }
            if (!Schema::hasColumn('user_vouchers', 'nguon')) {
                $table->string('nguon', 30)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_vouchers', function (Blueprint $table) {
            foreach (['het_han_luc', 'nguon'] as $column) {
                if (Schema::hasColumn('user_vouchers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('phieu_giam_gias', function (Blueprint $table) {
            foreach (['gia_san_pham_toi_da', 'gioi_han_moi_khach', 'nhom'] as $column) {
                if (Schema::hasColumn('phieu_giam_gias', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
