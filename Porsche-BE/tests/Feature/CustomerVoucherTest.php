<?php

namespace Tests\Feature;

use App\Http\Controllers\KhachHangController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class CustomerVoucherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['user_vouchers', 'lich_su_su_dung_phieus', 'phieu_giam_gias', 'users', 'vai_tros'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('vai_tros', function (Blueprint $table) {
            $table->id();
            $table->string('ma_vai_tro');
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('ho_ten');
            $table->string('email')->unique();
            $table->string('so_dien_thoai');
            $table->string('password');
            $table->unsignedBigInteger('vai_tro_id');
            $table->boolean('trang_thai');
            $table->string('ma_kich_hoat')->nullable();
            $table->string('avatar')->nullable();
            $table->text('dia_chi')->nullable();
            $table->timestamps();
        });

        Schema::create('phieu_giam_gias', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('loai');
            $table->integer('gia_tri');
            $table->decimal('gia_tri_don_hang_toi_thieu', 15, 2)->default(0);
            $table->decimal('giam_toi_da', 15, 2)->nullable();
            $table->integer('so_luong')->default(0);
            $table->timestamp('ngay_bat_dau')->nullable();
            $table->timestamp('ngay_ket_thuc')->nullable();
            $table->boolean('trang_thai')->default(true);
            $table->timestamps();
        });

        Schema::create('lich_su_su_dung_phieus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('phieu_id');
            $table->string('trang_thai');
        });

        $migration = require database_path('migrations/2026_10_24_000001_create_user_vouchers_table.php');
        $migration->up();

        DB::table('vai_tros')->insert(['ma_vai_tro' => 'customer']);
    }

    protected function tearDown(): void
    {
        foreach (['user_vouchers', 'lich_su_su_dung_phieus', 'phieu_giam_gias', 'users', 'vai_tros'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_registration_emails_and_assigns_only_active_available_vouchers(): void
    {
        DB::table('phieu_giam_gias')->insert([
            [
                'code' => 'WELCOME10',
                'loai' => 'phan_tram',
                'gia_tri' => 10,
                'gia_tri_don_hang_toi_thieu' => 0,
                'so_luong' => 5,
                'ngay_bat_dau' => now()->subDay(),
                'ngay_ket_thuc' => now()->addDay(),
                'trang_thai' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'EXPIRED10',
                'loai' => 'phan_tram',
                'gia_tri' => 10,
                'gia_tri_don_hang_toi_thieu' => 0,
                'so_luong' => 5,
                'ngay_bat_dau' => now()->subDays(2),
                'ngay_ket_thuc' => now()->subDay(),
                'trang_thai' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Mail::shouldReceive('send')
            ->once()
            ->with('emails.kich_hoat', Mockery::on(function (array $data): bool {
                $this->assertSame(['WELCOME10'], $data['vouchers']->pluck('code')->all());
                $this->assertStringContainsString('WELCOME10', view('emails.kich_hoat', $data)->render());

                return true;
            }), Mockery::type('Closure'));

        $response = $this->postJson('/api/khach-hang/register', [
            'ho_ten' => 'Khách thử nghiệm',
            'email' => 'voucher@example.com',
            'so_dien_thoai' => '0900000000',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertCreated();
        $this->assertSame(1, DB::table('user_vouchers')->count());

        $userId = DB::table('users')->where('email', 'voucher@example.com')->value('id');
        $request = Request::create('/api/khach-hang/profile/data', 'GET');
        $request->attributes->set('id_khach_hang', $userId);
        $profile = app(KhachHangController::class)->getProfile($request)->getData(true);

        $this->assertSame('WELCOME10', $profile['data']['vouchers'][0]['code']);
        $this->assertNotEmpty($profile['data']['profile']['created_at']);
    }
}
