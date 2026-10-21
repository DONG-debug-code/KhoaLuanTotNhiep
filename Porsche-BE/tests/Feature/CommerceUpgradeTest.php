<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\ChuongTrinhSaleController;
use App\Http\Controllers\DonHangController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommerceUpgradeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'lich_su_su_dung_phieus',
            'phieu_giam_gias',
            'nhat_ky_khos',
            'chi_tiet_don_hangs',
            'don_hangs',
            'chi_tiet_san_pham_sales',
            'chuong_trinh_sales',
            'anh_san_phams',
            'san_phams',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('san_phams', function (Blueprint $table) {
            $table->id();
            $table->string('ten');
            $table->string('slug')->nullable();
            $table->decimal('gia', 15, 2);
            $table->integer('so_luong')->default(0);
            $table->integer('so_luong_kho')->default(0);
            $table->text('mo_ta')->nullable();
            $table->text('thong_so_chi_tiet')->nullable();
            $table->timestamps();
        });

        Schema::create('anh_san_phams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('san_pham_id');
            $table->string('duong_dan');
            $table->boolean('la_anh_chinh')->default(false);
            $table->timestamps();
        });

        Schema::create('chuong_trinh_sales', function (Blueprint $table) {
            $table->id();
            $table->string('ten_su_kien');
            $table->string('slug');
            $table->string('banner_url')->nullable();
            $table->text('mo_ta')->nullable();
            $table->dateTime('ngay_bat_dau');
            $table->dateTime('ngay_ket_thuc');
            $table->boolean('trang_thai')->default(true);
            $table->timestamps();
        });

        Schema::create('chi_tiet_san_pham_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chuong_trinh_sale_id');
            $table->unsignedBigInteger('san_pham_id');
            $table->decimal('gia_goc', 15, 2);
            $table->decimal('gia_khuyen_mai', 15, 2);
            $table->string('loai_giam_gia');
            $table->decimal('gia_tri_giam', 15, 2)->default(0);
            $table->unsignedInteger('so_luong_sale')->default(1);
            $table->unsignedInteger('da_ban')->default(0);
            $table->unsignedInteger('gioi_han_moi_khach')->default(1);
            $table->timestamps();
        });

        Schema::create('don_hangs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('users_id');
            $table->decimal('tong_tien', 15, 2);
            $table->decimal('giam_gia', 15, 2);
            $table->decimal('thanh_tien', 15, 2);
            $table->string('trang_thai');
            $table->string('phuong_thuc_tt');
            $table->unsignedBigInteger('dia_chi_giao_hang_id')->nullable();
            $table->string('ly_do_huy', 500)->nullable();
            $table->timestamp('ngay_tao')->nullable();
            $table->timestamps();
        });

        Schema::create('chi_tiet_don_hangs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('don_hang_id');
            $table->unsignedBigInteger('san_pham_id');
            $table->unsignedBigInteger('chi_tiet_sale_id')->nullable();
            $table->integer('so_luong');
            $table->decimal('don_gia', 15, 2);
            $table->decimal('thanh_tien', 15, 2);
            $table->timestamps();
        });

        Schema::create('phieu_giam_gias', function (Blueprint $table) {
            $table->id();
            $table->string('code');
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
            $table->unsignedBigInteger('don_hang_id')->nullable();
            $table->decimal('so_tien_giam', 15, 2)->default(0);
            $table->string('trang_thai')->default('da_su_dung');
            $table->timestamp('thoi_diem_su_dung')->nullable();
            $table->timestamps();
        });

        Schema::create('nhat_ky_khos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('san_pham_id');
            $table->string('loai');
            $table->integer('so_luong');
            $table->string('ly_do')->nullable();
            $table->timestamps();
        });
    }

    public function test_active_flash_sale_returns_product_image(): void
    {
        DB::table('san_phams')->insert([
            'id' => 1,
            'ten' => 'Porsche 911',
            'gia' => 1000,
            'so_luong' => 2,
            'so_luong_kho' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('anh_san_phams')->insert([
            'san_pham_id' => 1,
            'duong_dan' => 'products/911.jpg',
            'la_anh_chinh' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $campaignId = DB::table('chuong_trinh_sales')->insertGetId([
            'ten_su_kien' => 'Test sale',
            'slug' => 'test-sale',
            'ngay_bat_dau' => now()->subHour(),
            'ngay_ket_thuc' => now()->addHour(),
            'trang_thai' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('chi_tiet_san_pham_sales')->insert([
            'chuong_trinh_sale_id' => $campaignId,
            'san_pham_id' => 1,
            'gia_goc' => 1000,
            'gia_khuyen_mai' => 800,
            'loai_giam_gia' => 'phan_tram',
            'gia_tri_giam' => 20,
            'so_luong_sale' => 3,
            'da_ban' => 0,
            'gioi_han_moi_khach' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/flash-sale/active')
            ->assertOk()
            ->assertJsonPath('data.0.san_pham_id', 1)
            ->assertJsonPath('data.0.hinh_anh', 'products/911.jpg');
    }

    public function test_purchase_applies_flash_sale_and_schema_compatible_voucher(): void
    {
        DB::table('san_phams')->insert([
            'id' => 1,
            'ten' => 'Porsche 911',
            'gia' => 1000,
            'so_luong' => 3,
            'so_luong_kho' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $campaignId = DB::table('chuong_trinh_sales')->insertGetId([
            'ten_su_kien' => 'Test sale',
            'slug' => 'test-sale',
            'ngay_bat_dau' => now()->subHour(),
            'ngay_ket_thuc' => now()->addHour(),
            'trang_thai' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $saleDetailId = DB::table('chi_tiet_san_pham_sales')->insertGetId([
            'chuong_trinh_sale_id' => $campaignId,
            'san_pham_id' => 1,
            'gia_goc' => 1000,
            'gia_khuyen_mai' => 800,
            'loai_giam_gia' => 'phan_tram',
            'gia_tri_giam' => 20,
            'so_luong_sale' => 3,
            'da_ban' => 0,
            'gioi_han_moi_khach' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('phieu_giam_gias')->insert([
            'id' => 1,
            'code' => 'SAVE10',
            'loai' => 'phan_tram',
            'gia_tri' => 10,
            'gia_tri_don_hang_toi_thieu' => 0,
            'giam_toi_da' => 500,
            'so_luong' => 10,
            'ngay_bat_dau' => now()->subHour(),
            'ngay_ket_thuc' => now()->addHour(),
            'trang_thai' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = new User();
        $user->id = 42;
        $request = Request::create('/api/khach-hang/dat-mua-xe', 'POST', [
            'san_pham_id' => 1,
            'phuong_thuc_tt' => 'cod',
            'so_luong' => 1,
            'ma_voucher' => 'SAVE10',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = app(DonHangController::class)->datMuaXe($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(720.0, (float) $response->getData(true)['data']['thanh_tien']);
        $this->assertSame(2, (int) DB::table('san_phams')->where('id', 1)->value('so_luong'));
        $this->assertSame(2, (int) DB::table('san_phams')->where('id', 1)->value('so_luong_kho'));
        $this->assertSame(1, (int) DB::table('chi_tiet_san_pham_sales')->where('id', $saleDetailId)->value('da_ban'));
        $this->assertSame('da_su_dung', DB::table('lich_su_su_dung_phieus')->value('trang_thai'));
    }

    public function test_restock_updates_both_inventory_columns_and_writes_audit_log(): void
    {
        DB::table('san_phams')->insert([
            'id' => 1,
            'ten' => 'Porsche 911',
            'gia' => 1000,
            'so_luong' => 3,
            'so_luong_kho' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/admin/products/1/restock', 'POST', [
            'so_luong' => 2,
            'ly_do' => 'Nhập phụ tùng',
        ]);
        $response = app(ProductController::class)->restock($request, 1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(5, (int) DB::table('san_phams')->where('id', 1)->value('so_luong'));
        $this->assertSame(5, (int) DB::table('san_phams')->where('id', 1)->value('so_luong_kho'));
        $this->assertSame('nhap', DB::table('nhat_ky_khos')->value('loai'));
    }

    public function test_product_update_records_inventory_adjustment(): void
    {
        DB::table('san_phams')->insert([
            'id' => 1,
            'ten' => 'Porsche 911',
            'slug' => 'porsche-911',
            'gia' => 1000,
            'so_luong' => 3,
            'so_luong_kho' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $request = Request::create('/api/admin/products/1', 'PUT', [
            'ten_san_pham' => 'Porsche 911',
            'gia' => 1000,
            'so_luong_kho' => 1,
        ]);

        $response = app(ProductController::class)->update($request, 1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, (int) DB::table('san_phams')->where('id', 1)->value('so_luong'));
        $this->assertSame(1, (int) DB::table('san_phams')->where('id', 1)->value('so_luong_kho'));
        $this->assertSame('xuat', DB::table('nhat_ky_khos')->value('loai'));
        $this->assertSame(2, (int) DB::table('nhat_ky_khos')->value('so_luong'));
    }
}
