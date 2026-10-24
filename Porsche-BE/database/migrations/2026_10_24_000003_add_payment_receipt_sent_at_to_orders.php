<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('don_hangs', 'thanh_toan_email_sent_at')) {
            Schema::table('don_hangs', function (Blueprint $table) {
                $table->timestamp('thanh_toan_email_sent_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('don_hangs', 'thanh_toan_email_sent_at')) {
            Schema::table('don_hangs', function (Blueprint $table) {
                $table->dropColumn('thanh_toan_email_sent_at');
            });
        }
    }
};
