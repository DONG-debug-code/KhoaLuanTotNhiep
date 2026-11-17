<?php

namespace Tests\Feature;

use App\Http\Controllers\ChatController;
use App\Http\Controllers\PhieuDatLichController;
use App\Mail\LichHenMail;
use App\Models\Admin;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChatAndAppointmentChangesTest extends TestCase
{
    private const TABLES = [
        'lich_su_phieu_dat_lichs', 'tin_nhans', 'cuoc_tro_chuyens', 'lich_lam_viecs',
        'phieu_dat_lichs', 'khu_vuc_workshops', 'chuyen_gias', 'admins', 'users',
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
            $table->string('so_dien_thoai')->nullable();
        });
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('ho_ten')->nullable();
        });
        Schema::create('chuyen_gias', function (Blueprint $table) {
            $table->id();
            $table->string('ho_ten');
            $table->integer('trang_thai')->default(1);
            $table->string('tinh_trang')->nullable();
        });
        Schema::create('khu_vuc_workshops', function (Blueprint $table) {
            $table->id();
            $table->string('ten_khu_vuc');
        });
        Schema::create('lich_lam_viecs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_chuyen_gia');
            $table->date('ngay_lam_viec');
            $table->string('ca_lam_viec');
        });
        Schema::create('phieu_dat_lichs', function (Blueprint $table) {
            $table->id();
            $table->string('ma_dat_lich');
            $table->unsignedBigInteger('id_khach_hang')->nullable();
            $table->unsignedBigInteger('id_chuyen_gia')->nullable();
            $table->unsignedBigInteger('id_khu_vuc_workshop')->nullable();
            $table->string('ten_khach_hang')->nullable();
            $table->string('email')->nullable();
            $table->date('ngay_dat');
            $table->string('gio_dat');
            $table->text('ghi_chu')->nullable();
            $table->integer('trang_thai')->default(0);
            $table->string('giai_doan')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_11_18_000004_create_chat_and_appointment_history_tables.php'))->up();

        DB::table('users')->insert([['id' => 1, 'ho_ten' => 'Khach A'], ['id' => 2, 'ho_ten' => 'Khach B']]);
        DB::table('admins')->insert(['id' => 5, 'ho_ten' => 'Nhan vien Lan']);
        DB::table('chuyen_gias')->insert([['id' => 1, 'ho_ten' => 'Tuner An'], ['id' => 2, 'ho_ten' => 'Tuner Binh']]);
        DB::table('khu_vuc_workshops')->insert(['id' => 1, 'ten_khu_vuc' => 'Bay 1']);
        foreach ([1, 2] as $expertId) {
            DB::table('lich_lam_viecs')->insert(['id_chuyen_gia' => $expertId, 'ngay_lam_viec' => '2026-11-20', 'ca_lam_viec' => 'Ca sáng (08:00 - 12:00)']);
        }

        Carbon::setTestNow(Carbon::parse('2026-11-18 09:00:00', 'Asia/Ho_Chi_Minh'));
        config(['shop.seller.email' => 'showroom@example.test']);
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function customer(int $id): User
    {
        $user = new User();
        $user->id = $id;

        return $user;
    }

    private function staff(): Admin
    {
        $staff = new Admin();
        $staff->id = 5;
        $staff->ho_ten = 'Nhan vien Lan';

        return $staff;
    }

    private function staffRequest(string $uri, array $data = [], string $method = 'POST'): Request
    {
        $request = Request::create($uri, $method, $data);
        $request->setUserResolver(fn () => $this->staff());

        return $request;
    }

    private function appointment(array $overrides = []): int
    {
        return DB::table('phieu_dat_lichs')->insertGetId($overrides + [
            'ma_dat_lich' => 'PORSCHE-TEST',
            'id_khach_hang' => 1,
            'id_chuyen_gia' => 1,
            'id_khu_vuc_workshop' => 1,
            'ten_khach_hang' => 'Khach A',
            'email' => 'khach@example.test',
            'ngay_dat' => '2026-11-19',
            'gio_dat' => '09:00',
            'trang_thai' => 0,
            'reminder_sent_at' => '2026-11-18 01:00:00',
        ]);
    }

    public function test_customer_and_staff_can_exchange_messages_and_only_the_owner_can_read_them(): void
    {
        $this->actingAs($this->customer(1), 'sanctum');
        $conversationId = $this->postJson('/api/khach-hang/chat/ket-noi')->assertOk()->assertJsonPath('data.trang_thai', 'cho')->json('data.id');
        $this->postJson("/api/khach-hang/chat/{$conversationId}/gui", ['noi_dung' => 'Tôi muốn đổi giờ hẹn'])->assertCreated();

        $queue = app(ChatController::class)->nhanVienDanhSach()->getData(true)['data'];
        $this->assertSame(1, $queue[0]['so_chua_doc']);
        $this->assertSame('Tôi muốn đổi giờ hẹn', $queue[0]['tin_cuoi']);

        $staffMessages = app(ChatController::class)->nhanVienTinNhan($this->staffRequest("/x", [], 'GET'), $conversationId)->getData(true);
        $this->assertSame(['Tôi muốn đổi giờ hẹn'], array_column($staffMessages['data']['tin_nhan'], 'noi_dung'));
        $this->assertSame('Khach A', $staffMessages['data']['tin_nhan'][0]['nguoi_gui_ten']);

        $reply = $this->staffRequest('/x', ['noi_dung' => 'Dạ, bạn muốn đổi sang giờ nào ạ?']);
        $this->assertSame(201, app(ChatController::class)->nhanVienGui($reply, $conversationId)->getStatusCode());
        $this->assertSame('dang', DB::table('cuoc_tro_chuyens')->where('id', $conversationId)->value('trang_thai'));
        $this->assertSame(5, (int) DB::table('cuoc_tro_chuyens')->where('id', $conversationId)->value('nhan_vien_id'));

        $mine = $this->getJson("/api/khach-hang/chat/{$conversationId}/tin-nhan")->assertOk();
        $this->assertSame('Nhan vien Lan', $mine->json('data.tin_nhan.1.nguoi_gui_ten'));
        $this->assertSame('Nhan vien Lan', $mine->json('data.cuoc_tro_chuyen.ten_nhan_vien'));
        $firstId = $mine->json('data.tin_nhan.0.id');
        $this->getJson("/api/khach-hang/chat/{$conversationId}/tin-nhan?sau_id={$firstId}")->assertOk()->assertJsonCount(1, 'data.tin_nhan');
        $this->getJson('/api/khach-hang/chat/hien-tai')->assertJsonPath('data.so_chua_doc', 0);

        $this->actingAs($this->customer(2), 'sanctum');
        $this->getJson("/api/khach-hang/chat/{$conversationId}/tin-nhan")->assertNotFound();
        $this->postJson("/api/khach-hang/chat/{$conversationId}/gui", ['noi_dung' => 'xin chào'])->assertNotFound();
    }

    public function test_rescheduling_checks_availability_and_notifies_the_customer_everywhere(): void
    {
        Mail::fake();
        $id = $this->appointment();
        $this->appointment(['ma_dat_lich' => 'OTHER', 'id_khach_hang' => 2, 'ngay_dat' => '2026-11-20', 'gio_dat' => '10:00']);

        $controller = app(PhieuDatLichController::class);
        $busy = $controller->doiLichHen($this->staffRequest('/x', ['id' => $id, 'ngay_dat' => '2026-11-20', 'gio_dat' => '10:00']));
        $this->assertSame(422, $busy->getStatusCode());
        $past = $controller->doiLichHen($this->staffRequest('/x', ['id' => $id, 'ngay_dat' => '2026-11-18', 'gio_dat' => '08:00']));
        $this->assertSame(422, $past->getStatusCode());
        $this->assertSame('2026-11-19', DB::table('phieu_dat_lichs')->where('id', $id)->value('ngay_dat'));

        $ok = $controller->doiLichHen($this->staffRequest('/x', ['id' => $id, 'ngay_dat' => '2026-11-20', 'gio_dat' => '09:00', 'ly_do' => 'Xưởng bảo trì']));
        $this->assertSame(200, $ok->getStatusCode());

        $row = DB::table('phieu_dat_lichs')->where('id', $id)->first();
        $this->assertSame('2026-11-20', substr($row->ngay_dat, 0, 10));
        $this->assertNull($row->reminder_sent_at);

        $history = DB::table('lich_su_phieu_dat_lichs')->where('id_phieu_dat_lich', $id)->first();
        $this->assertSame('doi_lich', $history->loai);
        $this->assertStringContainsString('Xưởng bảo trì', $history->noi_dung);
        $this->assertSame('Nhan vien Lan', $history->nguoi_thuc_hien);

        $chat = DB::table('tin_nhans')->where('nguoi_gui_loai', 'he_thong')->first();
        $this->assertStringContainsString('20/11/2026 09:00', $chat->noi_dung);
        $this->assertSame(1, (int) DB::table('cuoc_tro_chuyens')->where('id', $chat->cuoc_tro_chuyen_id)->value('user_id'));

        Mail::assertSent(LichHenMail::class, function (LichHenMail $mail) {
            $html = $mail->render();

            return $mail->hasTo('khach@example.test')
                && $mail->hasReplyTo('showroom@example.test')
                && str_contains($html, 'Xưởng bảo trì');
        });
    }

    public function test_changing_the_expert_requires_a_free_expert_and_tells_the_customer(): void
    {
        Mail::fake();
        $id = $this->appointment();
        $this->appointment(['ma_dat_lich' => 'BUSY', 'id_khach_hang' => 2, 'id_chuyen_gia' => 2, 'id_khu_vuc_workshop' => null, 'ngay_dat' => '2026-11-19', 'gio_dat' => '09:00']);
        DB::table('lich_lam_viecs')->insert(['id_chuyen_gia' => 2, 'ngay_lam_viec' => '2026-11-19', 'ca_lam_viec' => 'Ca sáng (08:00 - 12:00)']);
        DB::table('lich_lam_viecs')->insert(['id_chuyen_gia' => 1, 'ngay_lam_viec' => '2026-11-19', 'ca_lam_viec' => 'Ca sáng (08:00 - 12:00)']);

        $controller = app(PhieuDatLichController::class);
        $this->assertSame(422, $controller->doiChuyenGia($this->staffRequest('/x', ['id' => $id, 'id_chuyen_gia' => 2]))->getStatusCode());

        DB::table('phieu_dat_lichs')->where('ma_dat_lich', 'BUSY')->update(['gio_dat' => '10:00']);
        $this->assertSame(200, $controller->doiChuyenGia($this->staffRequest('/x', ['id' => $id, 'id_chuyen_gia' => 2, 'ly_do' => 'Tuner An nghỉ ốm']))->getStatusCode());

        $this->assertSame(2, (int) DB::table('phieu_dat_lichs')->where('id', $id)->value('id_chuyen_gia'));
        $message = DB::table('lich_su_phieu_dat_lichs')->where('id_phieu_dat_lich', $id)->value('noi_dung');
        $this->assertStringContainsString('từ Tuner An sang Tuner Binh', $message);
        $this->assertStringContainsString('Tuner An nghỉ ốm', $message);
        $this->assertSame(1, DB::table('tin_nhans')->where('nguoi_gui_loai', 'he_thong')->count());
        Mail::assertSent(LichHenMail::class, 1);
    }
}
