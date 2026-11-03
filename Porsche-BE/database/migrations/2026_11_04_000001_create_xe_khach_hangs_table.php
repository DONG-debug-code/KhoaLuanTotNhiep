<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xe_khach_hangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('ten_xe', 150);
            $table->string('bien_so_xe', 20);
            $table->string('dong_xe', 60)->nullable();
            $table->unsignedSmallInteger('nam_san_xuat')->nullable();
            $table->string('mau_sac', 50)->nullable();
            $table->string('so_khung', 30)->nullable();
            $table->unsignedInteger('so_km')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'bien_so_xe']);
        });

        // Đưa các xe đã xuất hiện trong lịch hẹn cũ vào garage để khách có thể sửa/xóa
        if (Schema::hasTable('phieu_dat_lichs')) {
            DB::table('phieu_dat_lichs')
                ->whereNotNull('id_khach_hang')
                ->whereNotNull('bien_so_xe')
                ->where('bien_so_xe', '!=', '')
                ->orderBy('id')
                ->get(['id_khach_hang', 'bien_so_xe', 'thong_tin_xe'])
                ->each(function ($row) {
                    $ten = trim(preg_split('/[·|,]/u', (string) $row->thong_tin_xe)[0] ?? '');
                    DB::table('xe_khach_hangs')->insertOrIgnore([
                        'user_id' => $row->id_khach_hang,
                        'ten_xe' => $ten !== '' ? mb_substr($ten, 0, 150) : 'Xe Porsche',
                        'bien_so_xe' => mb_strtoupper(mb_substr(trim($row->bien_so_xe), 0, 20)),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('xe_khach_hangs');
    }
};
