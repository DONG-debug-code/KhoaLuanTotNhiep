<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('phieu_dat_lichs') || Schema::hasColumn('phieu_dat_lichs', 'id_don_hang')) {
            return;
        }

        Schema::table('phieu_dat_lichs', function (Blueprint $table) {
            $table->unsignedBigInteger('id_don_hang')->nullable()->index();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('phieu_dat_lichs') || !Schema::hasColumn('phieu_dat_lichs', 'id_don_hang')) {
            return;
        }

        Schema::table('phieu_dat_lichs', function (Blueprint $table) {
            $table->dropColumn('id_don_hang');
        });
    }
};
