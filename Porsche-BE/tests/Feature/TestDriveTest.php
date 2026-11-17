<?php

namespace Tests\Feature;

use App\Mail\LaiThuMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TestDriveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('dang_ky_lai_thus');
        Schema::dropIfExists('phieu_dat_lichs');
        Schema::create('phieu_dat_lichs', function ($table) {
            $table->id();
            $table->unsignedTinyInteger('trang_thai')->default(0);
            $table->string('email')->nullable();
            $table->date('ngay_dat')->nullable();
            $table->string('gio_dat')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
        });
        (require database_path('migrations/2026_11_18_000001_create_dang_ky_lai_thus_table.php'))->up();
        Carbon::setTestNow(Carbon::parse('2026-11-18 09:00:00', 'Asia/Ho_Chi_Minh'));
        config(['shop.seller.email' => 'showroom@example.test']);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('dang_ky_lai_thus');
        Schema::dropIfExists('phieu_dat_lichs');
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'ho_ten' => 'Nguyen Van A',
            'email' => 'khach@example.test',
            'so_dien_thoai' => '0900000001',
            'dong_xe' => '911',
            'ngay_hen' => '2026-11-20',
            'gio_hen' => '10:00',
        ];
    }

    public function test_registration_emails_time_and_place_with_reply_to_the_showroom(): void
    {
        Mail::fake();

        $this->postJson('/api/lai-thu/dang-ky', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.email_da_gui', true);

        $this->assertSame(1, DB::table('dang_ky_lai_thus')->where('email', 'khach@example.test')->count());
        Mail::assertSent(LaiThuMail::class, function (LaiThuMail $mail) {
            $html = $mail->render();

            return $mail->hasTo('khach@example.test')
                && $mail->hasReplyTo('showroom@example.test')
                && str_contains($html, '20/11/2026')
                && str_contains($html, '10:00')
                && str_contains($html, config('shop.showroom_dia_chi'));
        });
    }

    public function test_past_or_out_of_hours_slots_are_rejected(): void
    {
        Mail::fake();

        $this->postJson('/api/lai-thu/dang-ky', $this->payload(['ngay_hen' => '2026-11-18', 'gio_hen' => '09:30']))
            ->assertStatus(422)->assertJsonValidationErrors('ngay_hen');
        $this->postJson('/api/lai-thu/dang-ky', $this->payload(['gio_hen' => '22:00']))
            ->assertStatus(422)->assertJsonValidationErrors('gio_hen');

        $this->assertSame(0, DB::table('dang_ky_lai_thus')->count());
        Mail::assertNothingSent();
    }

    public function test_customer_is_reminded_once_the_day_before(): void
    {
        Mail::fake();
        $this->postJson('/api/lai-thu/dang-ky', $this->payload(['ngay_hen' => '2026-11-19', 'gio_hen' => '08:30']))->assertCreated();

        Artisan::call('workshop:remind-upcoming-appointments');
        Artisan::call('workshop:remind-upcoming-appointments');

        Mail::assertQueued(LaiThuMail::class, 1);
        $this->assertNotNull(DB::table('dang_ky_lai_thus')->value('reminder_sent_at'));
    }
}
