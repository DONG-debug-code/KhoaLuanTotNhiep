<?php

namespace Tests\Feature;

use App\Http\Controllers\PhieuDatLichController;
use App\Http\Controllers\ThongKeController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkshopReportingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['hoa_dons', 'phieu_dat_lichs', 'khu_vuc_workshops', 'hang_phu_tungs', 'chuyen_gias'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('chuyen_gias', function (Blueprint $table) {
            $table->id();
            $table->string('ho_ten');
            $table->unsignedBigInteger('id_hang_phu_tung');
            $table->timestamps();
        });
        Schema::create('hang_phu_tungs', function (Blueprint $table) {
            $table->id();
            $table->string('ten_hang');
            $table->timestamps();
        });
        Schema::create('khu_vuc_workshops', function (Blueprint $table) {
            $table->id();
            $table->string('ten_khu_vuc');
            $table->timestamps();
        });
        Schema::create('phieu_dat_lichs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_khach_hang')->nullable();
            $table->unsignedBigInteger('id_chuyen_gia')->nullable();
            $table->unsignedBigInteger('id_khu_vuc_workshop')->nullable();
            $table->date('ngay_dat');
            $table->unsignedTinyInteger('trang_thai');
            $table->timestamps();
        });
        Schema::create('hoa_dons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_phieu_dat_lich');
            $table->decimal('tong_tien', 15, 2);
            $table->timestamps();
        });
    }

    public function test_appointment_assignment_drives_workshop_reports(): void
    {
        DB::table('hang_phu_tungs')->insert(['id' => 1, 'ten_hang' => 'Akrapovic']);
        DB::table('chuyen_gias')->insert(['id' => 1, 'ho_ten' => 'Tuner A', 'id_hang_phu_tung' => 1]);
        DB::table('khu_vuc_workshops')->insert(['id' => 1, 'ten_khu_vuc' => 'Bay 1']);
        DB::table('phieu_dat_lichs')->insert([
            [
                'id' => 1,
                'id_khach_hang' => 101,
                'ngay_dat' => '2025-01-01',
                'trang_thai' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'id_khach_hang' => 102,
                'ngay_dat' => '2025-01-02',
                'trang_thai' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('hoa_dons')->insert([
            'id_phieu_dat_lich' => 2,
            'tong_tien' => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignmentRequest = Request::create('/api/admin/lich-hen/phan-cong', 'POST', [
            'id' => 1,
            'id_chuyen_gia' => 1,
            'id_khu_vuc_workshop' => 1,
        ]);
        $this->assertSame(
            200,
            app(PhieuDatLichController::class)->phanCongLichHen($assignmentRequest)->getStatusCode()
        );
        DB::table('phieu_dat_lichs')->where('id', 2)->update([
            'id_chuyen_gia' => 1,
            'id_khu_vuc_workshop' => 1,
        ]);

        $controller = app(ThongKeController::class);
        $request = Request::create('/api/admin/thong-ke', 'POST');

        $byBrand = $controller->getLichHenTheoHangPhuTung($request)->getData(true);
        $byExpert = $controller->getLichDoXeTheoChuyenGia($request)->getData(true);
        $byArea = $controller->getXeTheoKhuVuc($request)->getData(true);
        $customersByExpert = $controller->getKhachHangTheoChuyenGia($request)->getData(true);
        $scheduleByArea = $controller->getLichLamViecTheoKhuVuc($request)->getData(true);

        $this->assertSame(2, (int) $byBrand['data'][0]['tong_lich_hen']);
        $this->assertSame(2, (int) $byExpert['data'][0]['tong_lich_do']);
        $this->assertSame(1, (int) $byExpert['data'][0]['lich_da_hoan_thanh']);
        $this->assertSame(500.0, (float) $byExpert['data'][0]['tong_doanh_thu']);
        $this->assertSame(2, (int) $byArea['data'][0]['tong_xe']);
        $this->assertSame(2, (int) $customersByExpert['data'][0]['tong_khach_hang']);
        $this->assertCount(2, $scheduleByArea['data']);
    }
}
