<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('phieu_dat_lichs') || Schema::hasColumn('phieu_dat_lichs', 'reminder_sent_at')) {
            return;
        }

        Schema::table('phieu_dat_lichs', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('phieu_dat_lichs') || ! Schema::hasColumn('phieu_dat_lichs', 'reminder_sent_at')) {
            return;
        }

        Schema::table('phieu_dat_lichs', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }
};
