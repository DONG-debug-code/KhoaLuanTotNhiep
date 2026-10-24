<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phieu_dat_lichs', function (Blueprint $table) {
            if (!Schema::hasColumn('phieu_dat_lichs', 'giai_doan')) {
                $table->string('giai_doan', 30)->default('cho_nhan_xe')->after('trang_thai');
            }
            if (!Schema::hasColumn('phieu_dat_lichs', 'tieu_de_cong_viec')) {
                $table->string('tieu_de_cong_viec')->nullable()->after('giai_doan');
            }
            if (!Schema::hasColumn('phieu_dat_lichs', 'gia_tri')) {
                $table->decimal('gia_tri', 15, 2)->nullable()->after('tieu_de_cong_viec');
            }
            if (!Schema::hasColumn('phieu_dat_lichs', 'thoi_luong_phut')) {
                $table->unsignedSmallInteger('thoi_luong_phut')->default(90)->after('gia_tri');
            }
            if (!Schema::hasColumn('phieu_dat_lichs', 'bat_dau_luc')) {
                $table->timestamp('bat_dau_luc')->nullable();
            }
            if (!Schema::hasColumn('phieu_dat_lichs', 'hoan_thanh_luc')) {
                $table->timestamp('hoan_thanh_luc')->nullable();
            }
            if (!Schema::hasColumn('phieu_dat_lichs', 'ban_giao_luc')) {
                $table->timestamp('ban_giao_luc')->nullable();
            }
        });

        DB::table('phieu_dat_lichs')->where('trang_thai', 2)->update(['giai_doan' => 'da_ban_giao']);

        Schema::table('khu_vuc_workshops', function (Blueprint $table) {
            if (!Schema::hasColumn('khu_vuc_workshops', 'ghi_chu')) {
                $table->string('ghi_chu')->nullable()->after('trang_thai');
            }
        });

        if (!Schema::hasTable('thong_so_tuning_xes')) {
            Schema::create('thong_so_tuning_xes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_chuyen_gia');
                $table->string('khach_key', 60);
                $table->string('bien_so_xe', 30)->default('');
                $table->string('hang_muc', 60);
                $table->string('gia_tri_truoc')->nullable();
                $table->string('gia_tri_hien_tai')->nullable();
                $table->timestamps();
                $table->unique(['id_chuyen_gia', 'khach_key', 'bien_so_xe', 'hang_muc'], 'thong_so_tuning_unique');
            });
        }

        if (!Schema::hasTable('ghi_chu_khach_chuyen_gias')) {
            Schema::create('ghi_chu_khach_chuyen_gias', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_chuyen_gia');
                $table->string('khach_key', 60);
                $table->text('noi_dung')->nullable();
                $table->timestamps();
                $table->unique(['id_chuyen_gia', 'khach_key']);
            });
        }

        // trang_thai: 1 hoạt động, 2 bảo trì (không nhận đặt lịch), 0 ẩn.
        DB::table('khu_vuc_workshops')->updateOrInsert(
            ['ten_khu_vuc' => 'Bay 4 - Dyno & Remap'],
            ['trang_thai' => 1, 'ghi_chu' => 'Dyno 4WD, sẵn sàng nạp map', 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('khu_vuc_workshops')->updateOrInsert(
            ['ten_khu_vuc' => 'Bay 5 - Hệ thống treo'],
            ['trang_thai' => 2, 'ghi_chu' => 'Bảo dưỡng cầu nâng & hệ thống khí nén', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ghi_chu_khach_chuyen_gias');
        Schema::dropIfExists('thong_so_tuning_xes');
        DB::table('khu_vuc_workshops')->whereIn('ten_khu_vuc', ['Bay 4 - Dyno & Remap', 'Bay 5 - Hệ thống treo'])->delete();
        Schema::table('khu_vuc_workshops', function (Blueprint $table) {
            if (Schema::hasColumn('khu_vuc_workshops', 'ghi_chu')) {
                $table->dropColumn('ghi_chu');
            }
        });
        Schema::table('phieu_dat_lichs', function (Blueprint $table) {
            foreach (['giai_doan', 'tieu_de_cong_viec', 'gia_tri', 'thoi_luong_phut', 'bat_dau_luc', 'hoan_thanh_luc', 'ban_giao_luc'] as $column) {
                if (Schema::hasColumn('phieu_dat_lichs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
