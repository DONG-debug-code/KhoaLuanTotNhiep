<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'so_dien_thoai' => fn (Blueprint $t) => $t->string('so_dien_thoai', 30)->nullable(),
            'ngay_sinh' => fn (Blueprint $t) => $t->date('ngay_sinh')->nullable(),
            'gioi_tinh' => fn (Blueprint $t) => $t->string('gioi_tinh', 20)->nullable(),
            'dia_chi' => fn (Blueprint $t) => $t->string('dia_chi')->nullable(),
            'chuc_danh' => fn (Blueprint $t) => $t->string('chuc_danh')->nullable(),
            'mo_ta' => fn (Blueprint $t) => $t->text('mo_ta')->nullable(),
            'anh' => fn (Blueprint $t) => $t->string('anh')->nullable(),
            'so_nam_kinh_nghiem' => fn (Blueprint $t) => $t->unsignedSmallInteger('so_nam_kinh_nghiem')->default(0),
            'so_du_an_hoan_thanh' => fn (Blueprint $t) => $t->unsignedInteger('so_du_an_hoan_thanh')->default(0),
            'chuyen_mon' => fn (Blueprint $t) => $t->json('chuyen_mon')->nullable(),
            'dong_xe' => fn (Blueprint $t) => $t->json('dong_xe')->nullable(),
            'tinh_trang' => fn (Blueprint $t) => $t->string('tinh_trang', 30)->default('san-sang'),
        ];

        foreach ($columns as $name => $definition) {
            if (!Schema::hasColumn('chuyen_gias', $name)) {
                Schema::table('chuyen_gias', fn (Blueprint $table) => $definition($table));
            }
        }

        if (!Schema::hasTable('lich_lam_viecs')) {
            Schema::create('lich_lam_viecs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_chuyen_gia')->index();
                $table->date('ngay_lam_viec');
                $table->string('ca_lam_viec', 50);
                $table->timestamps();
                $table->unique(['id_chuyen_gia', 'ngay_lam_viec', 'ca_lam_viec']);
            });
        }

        if (!Schema::hasTable('danh_gia_chuyen_gias')) {
            Schema::create('danh_gia_chuyen_gias', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_chuyen_gia')->index();
                $table->unsignedBigInteger('id_khach_hang')->nullable();
                $table->string('ten_khach_hang')->nullable();
                $table->unsignedTinyInteger('so_sao');
                $table->text('noi_dung')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lich_su_cong_viec_chuyen_gias')) {
            Schema::create('lich_su_cong_viec_chuyen_gias', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_chuyen_gia')->index();
                $table->string('tieu_de');
                $table->string('ten_xe')->nullable();
                $table->text('mo_ta')->nullable();
                $table->date('ngay_hoan_thanh');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Giữ lại dữ liệu chuyên gia khi rollback.
    }
};
