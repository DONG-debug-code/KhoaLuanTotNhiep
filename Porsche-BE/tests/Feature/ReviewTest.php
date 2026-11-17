<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    private const TABLES = [
        'danh_gia_phu_tungs', 'danh_gia_san_phams', 'danh_gia_chuyen_gias', 'chi_tiet_don_hangs',
        'don_hangs', 'phieu_dat_lichs', 'chuyen_gias', 'phu_tungs', 'san_phams', 'users',
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
        Schema::create('san_phams', function (Blueprint $table) {
            $table->id();
            $table->string('ten')->nullable();
        });
        Schema::create('phu_tungs', function (Blueprint $table) {
            $table->id();
            $table->string('ten')->nullable();
            $table->string('danh_gia')->nullable();
        });
        Schema::create('chuyen_gias', function (Blueprint $table) {
            $table->id();
            $table->string('ho_ten')->nullable();
        });
        Schema::create('don_hangs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('users_id');
            $table->string('trang_thai');
        });
        Schema::create('chi_tiet_don_hangs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('don_hang_id');
            $table->unsignedBigInteger('san_pham_id')->nullable();
            $table->unsignedBigInteger('phu_tung_id')->nullable();
        });
        Schema::create('phieu_dat_lichs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_khach_hang')->nullable();
            $table->unsignedBigInteger('id_chuyen_gia')->nullable();
            $table->integer('trang_thai')->default(0);
        });
        Schema::create('danh_gia_san_phams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('san_pham_id');
            $table->integer('so_sao');
            $table->text('noi_dung')->nullable();
            $table->timestamps();
        });
        Schema::create('danh_gia_chuyen_gias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_chuyen_gia');
            $table->unsignedBigInteger('id_khach_hang')->nullable();
            $table->string('ten_khach_hang')->nullable();
            $table->unsignedTinyInteger('so_sao');
            $table->text('noi_dung')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_11_18_000005_create_product_reviews_and_link_expert_reviews.php'))->up();

        DB::table('users')->insert([['id' => 1, 'ho_ten' => 'Nguyễn Văn An'], ['id' => 2, 'ho_ten' => 'Trần Thị Bình']]);
        DB::table('san_phams')->insert(['id' => 1, 'ten' => 'Gói độ A']);
        DB::table('phu_tungs')->insert(['id' => 1, 'ten' => 'Ống xả', 'danh_gia' => '4.9']);
        DB::table('chuyen_gias')->insert(['id' => 1, 'ho_ten' => 'Tuner An']);
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
        $user->ho_ten = DB::table('users')->where('id', $id)->value('ho_ten');

        return $user;
    }

    private function order(int $userId, string $status, array $line): void
    {
        $orderId = DB::table('don_hangs')->insertGetId(['users_id' => $userId, 'trang_thai' => $status]);
        DB::table('chi_tiet_don_hangs')->insert($line + ['don_hang_id' => $orderId]);
    }

    public function test_public_list_shows_average_distribution_and_masked_names(): void
    {
        DB::table('danh_gia_san_phams')->insert([
            ['user_id' => 1, 'san_pham_id' => 1, 'so_sao' => 5, 'noi_dung' => 'Rất tốt'],
            ['user_id' => 2, 'san_pham_id' => 1, 'so_sao' => 4, 'noi_dung' => null],
        ]);

        $response = $this->getJson('/api/danh-gia/san_pham/1')->assertOk();

        $response->assertJsonPath('data.trung_binh', 4.5)->assertJsonPath('data.so_luong', 2)
            ->assertJsonPath('data.phan_bo.5', 1)->assertJsonPath('data.phan_bo.4', 1)->assertJsonPath('data.phan_bo.1', 0);
        $names = collect($response->json('data.danh_gia'))->pluck('ten_khach_hang')->all();
        $this->assertEqualsCanonicalizing(['Nguyễn V** A*', 'Trần T** B***'], $names);
        $this->getJson('/api/danh-gia/khac/1')->assertNotFound();
    }

    public function test_only_customers_who_received_the_product_can_review_and_edit_their_review(): void
    {
        $this->actingAs($this->customer(1), 'sanctum');
        $payload = ['loai' => 'phu_tung', 'id' => 1, 'so_sao' => 5, 'noi_dung' => '<b>Chạy êm</b>'];

        $this->getJson('/api/khach-hang/danh-gia/dieu-kien?loai=phu_tung&id=1')->assertJsonPath('data.co_the_danh_gia', false);
        $this->postJson('/api/khach-hang/danh-gia/san-pham', $payload)->assertForbidden();

        $this->order(1, 'dang_giao', ['phu_tung_id' => 1]);
        $this->postJson('/api/khach-hang/danh-gia/san-pham', $payload)->assertForbidden();

        $this->order(1, 'hoan_thanh', ['phu_tung_id' => 1]);
        $this->postJson('/api/khach-hang/danh-gia/san-pham', $payload)->assertCreated();
        $this->postJson('/api/khach-hang/danh-gia/san-pham', ['so_sao' => 3] + $payload)->assertOk();

        $this->assertSame(1, DB::table('danh_gia_phu_tungs')->count());
        $this->assertSame('Chạy êm', DB::table('danh_gia_phu_tungs')->value('noi_dung'));
        $this->assertSame('3.0', DB::table('phu_tungs')->where('id', 1)->value('danh_gia'));
        $this->getJson('/api/khach-hang/danh-gia/dieu-kien?loai=phu_tung&id=1')
            ->assertJsonPath('data.co_the_danh_gia', true)->assertJsonPath('data.danh_gia_cua_toi.so_sao', 3);

        $this->postJson('/api/khach-hang/danh-gia/san-pham', ['so_sao' => 6] + $payload)->assertStatus(422);
        $this->actingAs($this->customer(2), 'sanctum');
        $this->postJson('/api/khach-hang/danh-gia/san-pham', $payload)->assertForbidden();
    }

    public function test_expert_reviews_require_a_completed_unreviewed_appointment(): void
    {
        $this->actingAs($this->customer(1), 'sanctum');
        $payload = ['id_chuyen_gia' => 1, 'so_sao' => 4, 'noi_dung' => 'Tư vấn nhiệt tình'];

        $pending = DB::table('phieu_dat_lichs')->insertGetId(['id_khach_hang' => 1, 'id_chuyen_gia' => 1, 'trang_thai' => 1]);
        $this->getJson('/api/khach-hang/danh-gia/dieu-kien?loai=chuyen_gia&id=1')->assertJsonPath('data.co_the_danh_gia', false);
        $this->postJson('/api/khach-hang/danh-gia/chuyen-gia', $payload)->assertForbidden();

        DB::table('phieu_dat_lichs')->where('id', $pending)->update(['trang_thai' => 2]);
        $this->getJson('/api/khach-hang/danh-gia/dieu-kien?loai=chuyen_gia&id=1')->assertJsonPath('data.co_the_danh_gia', true);
        $this->postJson('/api/khach-hang/danh-gia/chuyen-gia', $payload)->assertCreated();
        $this->assertSame($pending, (int) DB::table('danh_gia_chuyen_gias')->value('id_phieu_dat_lich'));
        $this->postJson('/api/khach-hang/danh-gia/chuyen-gia', $payload)->assertForbidden();

        $this->actingAs($this->customer(2), 'sanctum');
        $this->postJson('/api/khach-hang/danh-gia/chuyen-gia', $payload)->assertForbidden();
        $this->getJson('/api/danh-gia/chuyen_gia/1')->assertJsonPath('data.trung_binh', 4)->assertJsonPath('data.danh_gia.0.ten_khach_hang', 'Nguyễn V** A*');
    }
}
