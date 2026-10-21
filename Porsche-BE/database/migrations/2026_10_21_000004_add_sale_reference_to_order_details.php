<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('chi_tiet_don_hangs', 'chi_tiet_sale_id')) {
            Schema::table('chi_tiet_don_hangs', function (Blueprint $table) {
                $table->unsignedBigInteger('chi_tiet_sale_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('chi_tiet_don_hangs', 'chi_tiet_sale_id')) {
            Schema::table('chi_tiet_don_hangs', function (Blueprint $table) {
                $table->dropColumn('chi_tiet_sale_id');
            });
        }
    }
};
