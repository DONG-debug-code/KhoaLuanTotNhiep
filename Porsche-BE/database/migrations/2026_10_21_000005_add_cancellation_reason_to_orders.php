<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('don_hangs', 'ly_do_huy')) {
            Schema::table('don_hangs', function (Blueprint $table) {
                $table->string('ly_do_huy', 500)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('don_hangs', 'ly_do_huy')) {
            Schema::table('don_hangs', function (Blueprint $table) {
                $table->dropColumn('ly_do_huy');
            });
        }
    }
};
