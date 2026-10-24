<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('phieu_id')->constrained('phieu_giam_gias')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'phieu_id']);
        });

        $now = now();
        $vouchers = DB::table('phieu_giam_gias')
            ->where('trang_thai', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('ngay_bat_dau')->orWhere('ngay_bat_dau', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('ngay_ket_thuc')->orWhere('ngay_ket_thuc', '>=', $now);
            })
            ->whereRaw(
                '(SELECT COUNT(*) FROM lich_su_su_dung_phieus WHERE phieu_id = phieu_giam_gias.id AND trang_thai = ?) < phieu_giam_gias.so_luong',
                ['da_su_dung']
            )
            ->pluck('id');

        if ($vouchers->isNotEmpty()) {
            DB::table('users')
                ->join('vai_tros', 'users.vai_tro_id', '=', 'vai_tros.id')
                ->where('vai_tros.ma_vai_tro', 'customer')
                ->where('users.trang_thai', true)
                ->select('users.id')
                ->orderBy('users.id')
                ->chunkById(500, function ($users) use ($vouchers, $now) {
                    $assignments = [];
                    foreach ($users as $user) {
                        foreach ($vouchers as $voucherId) {
                            $assignments[] = [
                                'user_id' => $user->id,
                                'phieu_id' => $voucherId,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }

                    DB::table('user_vouchers')->insertOrIgnore($assignments);
                }, 'users.id', 'id');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_vouchers');
    }
};
