<?php

namespace Tests\Feature;

use App\Mail\LichHenMail;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkshopAutomationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('phieu_dat_lichs');
        Schema::create('phieu_dat_lichs', function (Blueprint $table) {
            $table->id();
            $table->string('ma_dat_lich');
            $table->string('ten_khach_hang');
            $table->string('email');
            $table->date('ngay_dat');
            $table->time('gio_dat');
            $table->string('ghi_chu')->nullable();
            $table->unsignedTinyInteger('trang_thai');
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('phieu_dat_lichs');
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_expired_holds_are_cancelled_and_confirmed_appointments_are_reminded_once(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-21 08:00:00'));
        Mail::fake();

        DB::table('phieu_dat_lichs')->insert([
            [
                'ma_dat_lich' => 'OLD-HOLD',
                'ten_khach_hang' => 'Customer A',
                'email' => 'a@example.test',
                'ngay_dat' => '2026-10-22',
                'gio_dat' => '09:00:00',
                'trang_thai' => 0,
                'created_at' => now()->subMinutes(31),
                'updated_at' => now()->subMinutes(31),
            ],
            [
                'ma_dat_lich' => 'UPCOMING',
                'ten_khach_hang' => 'Customer B',
                'email' => 'b@example.test',
                'ngay_dat' => '2026-10-22',
                'gio_dat' => '07:00:00',
                'trang_thai' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->assertSame(0, Artisan::call('workshop:cancel-expired-appointments'));
        $this->assertSame(0, Artisan::call('workshop:remind-upcoming-appointments'));
        $this->assertSame(0, Artisan::call('workshop:remind-upcoming-appointments'));

        $this->assertSame(3, (int) DB::table('phieu_dat_lichs')->where('ma_dat_lich', 'OLD-HOLD')->value('trang_thai'));
        $this->assertNotNull(DB::table('phieu_dat_lichs')->where('ma_dat_lich', 'UPCOMING')->value('reminder_sent_at'));
        Mail::assertQueued(LichHenMail::class, 2);
    }
}
