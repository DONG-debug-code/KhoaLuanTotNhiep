<?php

namespace Tests\Feature;

use App\Http\Controllers\XeKhachHangController;
use App\Models\User;
use App\Services\VehicleHistoryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VehicleHistoryTest extends TestCase
{
    private const TABLES = [
        'lich_su_xe_khach_hangs', 'hoa_don_chi_tiets', 'hoa_dons', 'chi_tiet_don_hangs',
        'phieu_dat_lichs', 'xe_khach_hangs', 'san_phams', 'phu_tungs', 'users',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('ho_ten')->nullable();
        });
        Schema::create('xe_khach_hangs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('ten_xe');
            $table->string('bien_so_xe');
            $table->string('dong_xe')->nullable();
            $table->unsignedSmallInteger('nam_san_xuat')->nullable();
            $table->string('mau_sac')->nullable();
            $table->string('so_khung')->nullable();
            $table->unsignedInteger('so_km')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
        });
        Schema::create('phieu_dat_lichs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_khach_hang')->nullable();
            $table->unsignedBigInteger('id_don_hang')->nullable();
            $table->string('bien_so_xe')->nullable();
            $table->string('thong_tin_xe')->nullable();
            $table->text('ghi_chu')->nullable();
            $table->string('tieu_de_cong_viec')->nullable();
            $table->timestamp('hoan_thanh_luc')->nullable();
        });
        Schema::create('hoa_dons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_phieu_dat_lich')->nullable();
        });
        Schema::create('hoa_don_chi_tiets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_hoa_don');
            $table->string('ten_vat_tu');
            $table->integer('so_luong');
        });
        Schema::create('san_phams', function (Blueprint $table) {
            $table->id();
            $table->string('ten')->nullable();
        });
        Schema::create('phu_tungs', function (Blueprint $table) {
            $table->id();
            $table->string('ten')->nullable();
        });
        Schema::create('chi_tiet_don_hangs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('don_hang_id');
            $table->unsignedBigInteger('san_pham_id')->nullable();
            $table->unsignedBigInteger('phu_tung_id')->nullable();
            $table->integer('so_luong');
        });
        (require database_path('migrations/2026_11_18_000003_create_lich_su_xe_khach_hangs_table.php'))->up();

        DB::table('users')->insert([['id' => 1, 'ho_ten' => 'Khach A'], ['id' => 2, 'ho_ten' => 'Khach B']]);
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function customer(int $id): User
    {
        $user = new User();
        $user->id = $id;

        return $user;
    }

    public function test_customer_vehicle_changes_are_recorded_and_visible_only_to_the_owner(): void
    {
        $this->actingAs($this->customer(1), 'sanctum');

        $vehicleId = $this->postJson('/api/khach-hang/xe', ['ten_xe' => 'Porsche 911', 'bien_so_xe' => '51a-123.45', 'so_km' => 1000, 'mau_sac' => 'Đen'])
            ->assertCreated()->json('data.id');
        $this->putJson("/api/khach-hang/xe/{$vehicleId}", ['ten_xe' => 'Porsche 911', 'bien_so_xe' => '51A-123.45', 'so_km' => 5000, 'mau_sac' => 'Đỏ'])
            ->assertOk();
        $this->putJson("/api/khach-hang/xe/{$vehicleId}", ['ten_xe' => 'Porsche 911', 'bien_so_xe' => '51A-123.45', 'so_km' => 5000, 'mau_sac' => 'Đỏ'])
            ->assertOk();

        $history = $this->getJson("/api/khach-hang/xe/{$vehicleId}/lich-su")->assertOk()->json('data.lich_su');
        $this->assertCount(2, $history);
        $this->assertSame('cap_nhat_thong_tin', $history[0]['loai']);
        $this->assertEqualsCanonicalizing(
            [['truong' => 'Màu sắc', 'truoc' => 'Đen', 'sau' => 'Đỏ'], ['truong' => 'Số km', 'truoc' => '1000', 'sau' => '5000']],
            $history[0]['thay_doi']
        );
        $this->assertSame('ghi_nhan', $history[1]['loai']);

        $this->actingAs($this->customer(2), 'sanctum');
        $this->getJson("/api/khach-hang/xe/{$vehicleId}/lich-su")->assertNotFound();
    }

    public function test_completed_appointment_is_logged_once_with_serviced_items_and_creates_missing_vehicle(): void
    {
        $appointmentId = DB::table('phieu_dat_lichs')->insertGetId([
            'id_khach_hang' => 1, 'bien_so_xe' => '30a-999.99', 'thong_tin_xe' => 'Porsche Taycan · 30A-999.99',
            'tieu_de_cong_viec' => 'Nâng cấp hệ thống xả', 'id_don_hang' => 7,
        ]);
        $invoiceId = DB::table('hoa_dons')->insertGetId(['id_phieu_dat_lich' => $appointmentId]);
        DB::table('hoa_don_chi_tiets')->insert([
            ['id_hoa_don' => $invoiceId, 'ten_vat_tu' => 'Ống xả Akrapovič', 'so_luong' => 1],
            ['id_hoa_don' => $invoiceId, 'ten_vat_tu' => 'Gioăng', 'so_luong' => 2],
        ]);

        $service = app(VehicleHistoryService::class);
        $service->recordAppointmentCompleted($appointmentId);
        $service->recordAppointmentCompleted($appointmentId);

        $vehicle = DB::table('xe_khach_hangs')->where('user_id', 1)->first();
        $this->assertSame('30A-999.99', $vehicle->bien_so_xe);
        $this->assertSame('Porsche Taycan', $vehicle->ten_xe);

        $rows = DB::table('lich_su_xe_khach_hangs')->where('xe_khach_hang_id', $vehicle->id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame('nang_cap', $rows[0]->loai);
        $this->assertSame('Nâng cấp hệ thống xả', $rows[0]->tieu_de);
        $this->assertSame('Ống xả Akrapovič; Gioăng x2', $rows[0]->mo_ta);
        $this->assertSame($appointmentId, (int) $rows[0]->id_phieu_dat_lich);
    }

    public function test_staff_can_record_a_showroom_upgrade_for_a_customer_vehicle(): void
    {
        $vehicleId = DB::table('xe_khach_hangs')->insertGetId(['user_id' => 1, 'ten_xe' => 'Porsche 718', 'bien_so_xe' => '51B-111.11']);
        $staff = new \stdClass();
        $staff->id = 9;
        $staff->ho_ten = 'Nhân viên Tư vấn';

        $request = Request::create('/api/admin/xe-khach-hang/lich-su/them', 'POST', [
            'xe_khach_hang_id' => $vehicleId,
            'loai' => 'nang_cap',
            'tieu_de' => 'Thay bộ mâm',
            'thay_doi' => [['truong' => 'Mâm', 'truoc' => '19 inch', 'sau' => '20 inch']],
        ]);
        $request->setUserResolver(fn () => $staff);

        $response = app(XeKhachHangController::class)->adminThemLichSu($request);

        $this->assertSame(201, $response->getStatusCode());
        $row = DB::table('lich_su_xe_khach_hangs')->first();
        $this->assertSame(1, (int) $row->user_id);
        $this->assertSame('nhan_vien', $row->nguon);
        $this->assertSame('Nhân viên Tư vấn', $row->nguoi_tao_ten);
        $this->assertSame([['truong' => 'Mâm', 'truoc' => '19 inch', 'sau' => '20 inch']], json_decode($row->thay_doi, true));
    }
}
