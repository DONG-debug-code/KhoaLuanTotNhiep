<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('phieu_dat_lichs')) {
            return;
        }

        Schema::table('phieu_dat_lichs', function (Blueprint $table) {
            if (!Schema::hasColumn('phieu_dat_lichs', 'id_khu_vuc_workshop')) {
                $table->unsignedBigInteger('id_khu_vuc_workshop')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('phieu_dat_lichs')) {
            return;
        }

        if (Schema::hasColumn('phieu_dat_lichs', 'id_khu_vuc_workshop')) {
            Schema::table('phieu_dat_lichs', function (Blueprint $table) {
                $table->dropColumn('id_khu_vuc_workshop');
            });
        }
    }
};
