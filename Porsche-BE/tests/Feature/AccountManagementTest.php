<?php

namespace Tests\Feature;

use App\Http\Controllers\AccountController;
use App\Http\Controllers\NhanVienController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    private const TABLES = ['nhat_ky_tai_khoans', 'personal_access_tokens', 'chuyen_gias', 'users', 'admins', 'vai_tros'];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('vai_tros', function (Blueprint $table) {
            $table->id();
            $table->string('ten');
            $table->string('ma_vai_tro');
        });
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('ho_ten');
            $table->string('email');
            $table->string('so_dien_thoai')->nullable();
            $table->unsignedBigInteger('vai_tro_id');
            $table->tinyInteger('trang_thai')->default(1);
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('ho_ten');
            $table->string('email');
            $table->string('so_dien_thoai')->nullable();
            $table->tinyInteger('trang_thai')->default(1);
            $table->timestamps();
        });
        Schema::create('chuyen_gias', function (Blueprint $table) {
            $table->id();
            $table->string('ho_ten');
            $table->string('email')->nullable();
            $table->tinyInteger('trang_thai')->default(1);
            $table->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('tokenable_type');
            $table->unsignedBigInteger('tokenable_id');
            $table->string('name')->nullable();
            $table->string('token')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_11_18_000006_create_nhat_ky_tai_khoans_table.php'))->up();

        DB::table('vai_tros')->insert([
            ['id' => 1, 'ten' => 'Quản trị cao cấp', 'ma_vai_tro' => 'super_admin'],
            ['id' => 2, 'ten' => 'Điều hành', 'ma_vai_tro' => 'executive'],
            ['id' => 7, 'ten' => 'Cố vấn dịch vụ', 'ma_vai_tro' => 'service_advisor'],
            ['id' => 9, 'ten' => 'Kỹ thuật viên', 'ma_vai_tro' => 'technician'],
            ['id' => 17, 'ten' => 'Khách hàng', 'ma_vai_tro' => 'customer'],
        ]);
        DB::table('admins')->insert([
            ['id' => 1, 'ho_ten' => 'Root', 'email' => 'root@porsche.vn', 'vai_tro_id' => 1],
            ['id' => 2, 'ho_ten' => 'Lan', 'email' => 'lan@staff.vn', 'vai_tro_id' => 7],
            ['id' => 3, 'ho_ten' => 'Điều hành', 'email' => 'dh@porsche.vn', 'vai_tro_id' => 2],
        ]);
        DB::table('users')->insert(['id' => 10, 'ho_ten' => 'Khách A', 'email' => 'a@gmail.com']);
        DB::table('chuyen_gias')->insert(['id' => 20, 'ho_ten' => 'Tuner', 'email' => 't@tuner.vn']);
        foreach ([['App\\Models\\User', 10], ['App\\Models\\User', 10], ['App\\Models\\Admin', 2], ['App\\Models\\ChuyenGia', 20]] as [$type, $id]) {
            DB::table('personal_access_tokens')->insert(['tokenable_type' => $type, 'tokenable_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function asRoot(string $uri, array $data): Request
    {
        $request = Request::create($uri, 'POST', $data);
        $request->setUserResolver(fn () => (object) ['id' => 1, 'ho_ten' => 'Root']);

        return $request;
    }

    public function test_locking_an_account_logs_out_its_sessions_and_can_be_undone(): void
    {
        $controller = app(AccountController::class);

        $this->assertSame(200, $controller->doiTrangThai($this->asRoot('/x', ['loai' => 'khach_hang', 'id' => 10, 'khoa' => true, 'ly_do' => 'Gian lận']))->getStatusCode());
        $this->assertSame(0, (int) DB::table('users')->where('id', 10)->value('trang_thai'));
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_type', 'App\\Models\\User')->count());
        $this->assertSame(2, DB::table('personal_access_tokens')->count());

        $this->assertSame(200, $controller->doiTrangThai($this->asRoot('/x', ['loai' => 'chuyen_gia', 'id' => 20, 'khoa' => true]))->getStatusCode());
        $this->assertSame(200, $controller->doiTrangThai($this->asRoot('/x', ['loai' => 'nhan_vien', 'id' => 2, 'khoa' => true]))->getStatusCode());
        $this->assertSame(0, DB::table('personal_access_tokens')->count());

        $controller->doiTrangThai($this->asRoot('/x', ['loai' => 'khach_hang', 'id' => 10, 'khoa' => false]));
        $this->assertSame(1, (int) DB::table('users')->where('id', 10)->value('trang_thai'));

        $log = DB::table('nhat_ky_tai_khoans')->where('loai_tai_khoan', 'khach_hang')->orderBy('id')->get();
        $this->assertSame(['khoa', 'mo_khoa'], $log->pluck('hanh_dong')->all());
        $this->assertSame('Gian lận', $log[0]->ly_do);
        $this->assertSame(1, (int) $log[0]->admin_id);
    }

    public function test_admins_cannot_lock_themselves_or_the_last_super_admin(): void
    {
        $controller = app(AccountController::class);

        $this->assertSame(422, $controller->doiTrangThai($this->asRoot('/x', ['loai' => 'nhan_vien', 'id' => 1, 'khoa' => true]))->getStatusCode());

        $other = Request::create('/x', 'POST', ['loai' => 'nhan_vien', 'id' => 1, 'khoa' => true]);
        $other->setUserResolver(fn () => (object) ['id' => 3, 'ho_ten' => 'Điều hành']);
        $response = $controller->doiTrangThai($other);
        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('cuối cùng', $response->getData(true)['message']);
        $this->assertSame(1, (int) DB::table('admins')->where('id', 1)->value('trang_thai'));
    }

    public function test_staff_roles_follow_the_email_domain_and_protect_the_last_super_admin(): void
    {
        $controller = app(AccountController::class);

        $this->assertSame(200, $controller->doiVaiTro($this->asRoot('/x', ['id' => 2, 'vai_tro_id' => 9]))->getStatusCode());
        $this->assertSame(9, (int) DB::table('admins')->where('id', 2)->value('vai_tro_id'));
        $this->assertSame('doi_vai_tro', DB::table('nhat_ky_tai_khoans')->value('hanh_dong'));

        $this->assertSame(422, $controller->doiVaiTro($this->asRoot('/x', ['id' => 2, 'vai_tro_id' => 1]))->getStatusCode());
        $this->assertSame(422, $controller->doiVaiTro($this->asRoot('/x', ['id' => 2, 'vai_tro_id' => 17]))->getStatusCode());
        $this->assertSame(422, $controller->doiVaiTro($this->asRoot('/x', ['id' => 3, 'vai_tro_id' => 7]))->getStatusCode());
        $this->assertSame(422, $controller->doiVaiTro($this->asRoot('/x', ['id' => 1, 'vai_tro_id' => 2]))->getStatusCode());

        $update = Request::create('/x', 'POST', ['id' => 2, 'ho_ten' => 'Lan', 'email' => 'lan@staff.vn', 'id_chuc_vu' => 1]);
        $update->setUserResolver(fn () => (object) ['id' => 1]);
        $this->assertSame(422, app(NhanVienController::class)->updateNhanVien($update)->getStatusCode());
    }

    public function test_account_list_combines_all_account_types_with_session_counts(): void
    {
        $data = app(AccountController::class)->danhSach()->getData(true)['data'];

        $byKey = collect($data['tai_khoan'])->keyBy(fn ($row) => $row['loai'] . '#' . $row['id']);
        $this->assertCount(5, $byKey);
        $this->assertSame(2, $byKey['khach_hang#10']['so_phien']);
        $this->assertSame('Cố vấn dịch vụ', $byKey['nhan_vien#2']['vai_tro']);
        $this->assertSame('Chuyên gia', $byKey['chuyen_gia#20']['vai_tro']);
        $this->assertNotContains('customer', array_column($data['vai_tro_nhan_vien'], 'ma_vai_tro'));
    }
}
