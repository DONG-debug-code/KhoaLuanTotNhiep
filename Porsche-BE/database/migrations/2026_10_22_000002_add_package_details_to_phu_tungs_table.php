<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phu_tungs', function (Blueprint $table) {
            if (!Schema::hasColumn('phu_tungs', 'bao_gom')) {
                $table->json('bao_gom')->nullable();
            }

            if (!Schema::hasColumn('phu_tungs', 'dong_xe')) {
                $table->json('dong_xe')->nullable();
            }
        });
    }

    public function down(): void
    {
        $columns = array_filter(
            ['bao_gom', 'dong_xe'],
            fn (string $column): bool => Schema::hasColumn('phu_tungs', $column)
        );

        if ($columns !== []) {
            Schema::table('phu_tungs', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
