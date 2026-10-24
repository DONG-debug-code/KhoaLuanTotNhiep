<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phieu_dat_lichs', function (Blueprint $table) {
            if (!Schema::hasColumn('phieu_dat_lichs', 'loai_lich')) {
                $table->string('loai_lich', 30)->default('do-xe');
            }
            if (!Schema::hasColumn('phieu_dat_lichs', 'ghi_chu_chuyen_gia')) {
                $table->text('ghi_chu_chuyen_gia')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Giữ lại dữ liệu lịch hẹn khi rollback.
    }
};
