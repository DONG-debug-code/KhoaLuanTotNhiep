<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\ChuongTrinhSaleController;
use App\Http\Controllers\DonHangController;
use App\Mail\PaymentReceiptMail;
use App\Models\User;
use App\Services\VnpayService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommerceUpgradeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'user_vouchers',
            'vnpay_payments',
            'hoa_dons',
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
            $table->string('trang_thai_thanh_toan')->default('chua_thanh_toan');
            $table->timestamp('thanh_toan_email_sent_at')->nullable();
            $table->string('phuong_thuc_nhan')->nullable();
            $table->decimal('phi_van_chuyen', 15, 2)->nullable();
            $table->string('ten_nguoi_nhan')->nullable();
            $table->string('email_nguoi_nhan')->nullable();
            $table->string('so_dien_thoai_nguoi_nhan')->nullable();
            $table->text('dia_chi_nguoi_nhan')->nullable();
            $table->text('ghi_chu_don_hang')->nullable();
            $table->unsignedBigInteger('dia_chi_giao_hang_id')->nullable();
            $table->string('ly_do_huy', 500)->nullable();
            $table->timestamp('ngay_tao')->nullable();
            $table->timestamps();
        });

        Schema::create('chi_tiet_don_hangs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('don_hang_id');
            $table->unsignedBigInteger('san_pham_id');
            $table->unsignedBigInteger('phu_tung_id')->nullable();
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

        Schema::create('user_vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('phieu_id');
            $table->timestamps();
        });

        Schema::create('nhat_ky_khos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('san_pham_id');
            $table->unsignedBigInteger('phu_tung_id')->nullable();
            $table->string('loai');
            $table->integer('so_luong');
            $table->string('ly_do')->nullable();
            $table->timestamps();
        });

        Schema::create('vnpay_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('don_hang_id')->unique();
            $table->string('txn_ref')->unique();
            $table->unsignedBigInteger('amount');
            $table->string('status')->default('pending');
            $table->string('transaction_no')->nullable();
            $table->string('bank_code')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('receipt_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('hoa_dons', function (Blueprint $table) {
            $table->id();
            $table->string('ma_hoa_don');
            $table->unsignedBigInteger('don_hang_id');
            $table->unsignedBigInteger('users_id');
            $table->decimal('tong_tien', 15, 2);
            $table->string('trang_thai_tt');
            $table->timestamp('ngay_xuat')->nullable();
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
        DB::table('user_vouchers')->insert([
            'user_id' => 42,
            'phieu_id' => 1,
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

    public function test_voucher_cannot_be_applied_by_a_customer_it_was_not_assigned_to(): void
    {
        DB::table('phieu_giam_gias')->insert([
            'id' => 1,
            'code' => 'PRIVATE10',
            'loai' => 'phan_tram',
            'gia_tri' => 10,
            'gia_tri_don_hang_toi_thieu' => 0,
            'so_luong' => 10,
            'ngay_bat_dau' => now()->subHour(),
            'ngay_ket_thuc' => now()->addHour(),
            'trang_thai' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = new User();
        $user->id = 42;
        $request = Request::create('/api/khach-hang/ap-ma-giam-gia', 'POST', [
            'ma_voucher' => 'PRIVATE10',
            'tong_tien' => 1000,
        ]);
        $request->setUserResolver(fn () => $user);

        $response = app(DonHangController::class)->apMaGiamGia($request);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(
            'Mã giảm giá này chưa được cấp cho tài khoản của bạn.',
            $response->getData(true)['message']
        );
    }

    public function test_card_order_creates_signed_vnpay_payment_url(): void
    {
        $this->configureVnpay();
        DB::table('san_phams')->insert([
            'id' => 1,
            'ten' => 'Gói chăm sóc xe',
            'gia' => 2500000,
            'so_luong' => 3,
            'so_luong_kho' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = new User();
        $user->id = 42;
        $request = Request::create('/api/khach-hang/dat-don-hang', 'POST', [
            'items' => [['type' => 'goi_do', 'id' => 1, 'quantity' => 1]],
            'ten_nguoi_nhan' => 'Khách đặt hàng',
            'email_nguoi_nhan' => 'buyer@example.com',
            'so_dien_thoai_nguoi_nhan' => '0900000000',
            'phuong_thuc_nhan' => 'nhan_tai_showroom',
            'phuong_thuc_tt' => 'the',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = app(DonHangController::class)->datDonHang($request);
        $data = $response->getData(true)['data'];
        $payment = DB::table('vnpay_payments')->first();
        parse_str((string) parse_url($data['checkout_url'], PHP_URL_QUERY), $query);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertStringStartsWith('https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?', $data['checkout_url']);
        $this->assertSame('cho_thanh_toan', DB::table('don_hangs')->where('id', $data['don_hang_id'])->value('trang_thai_thanh_toan'));
        $this->assertSame($payment->txn_ref, $query['vnp_TxnRef']);
        $this->assertSame('250000000', $query['vnp_Amount']);
        $this->assertTrue(app(VnpayService::class)->hasValidSignature($query));
    }

    public function test_card_checkout_is_unavailable_without_vnpay_configuration(): void
    {
        config(['services.vnpay.tmn_code' => null, 'services.vnpay.hash_secret' => null]);
        $user = new User();
        $user->id = 42;
        $request = Request::create('/api/khach-hang/dat-don-hang', 'POST', [
            'items' => [['type' => 'goi_do', 'id' => 1, 'quantity' => 1]],
            'ten_nguoi_nhan' => 'Khách đặt hàng',
            'email_nguoi_nhan' => 'buyer@example.com',
            'so_dien_thoai_nguoi_nhan' => '0900000000',
            'phuong_thuc_nhan' => 'nhan_tai_showroom',
            'phuong_thuc_tt' => 'the',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = app(DonHangController::class)->datDonHang($request);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame(0, DB::table('don_hangs')->count());
    }

    public function test_successful_vnpay_ipn_confirms_order_and_sends_receipt_once(): void
    {
        Mail::fake();
        $this->configureVnpay();
        $this->createPendingCardOrder();
        $params = $this->signedVnpayParams(['vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00']);

        $this->getJson('/api/webhooks/vnpay?' . http_build_query($params))
            ->assertOk()
            ->assertJson(['RspCode' => '00']);
        $this->getJson('/api/webhooks/vnpay?' . http_build_query($params))
            ->assertJson(['RspCode' => '02']);

        $this->assertSame('da_thanh_toan', DB::table('don_hangs')->where('id', 1)->value('trang_thai_thanh_toan'));
        $this->assertSame('paid', DB::table('vnpay_payments')->value('status'));
        Mail::assertSent(PaymentReceiptMail::class, 1);
    }

    public function test_vnpay_ipn_rejects_bad_signature_and_wrong_amount(): void
    {
        Mail::fake();
        $this->configureVnpay();
        $this->createPendingCardOrder();
        $params = $this->signedVnpayParams(['vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00']);
        $params['vnp_ResponseCode'] = '24';

        $this->getJson('/api/webhooks/vnpay?' . http_build_query($params))->assertJson(['RspCode' => '97']);
        $wrongAmount = $this->signedVnpayParams([
            'vnp_Amount' => '100',
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
        ]);
        $this->getJson('/api/webhooks/vnpay?' . http_build_query($wrongAmount))->assertJson(['RspCode' => '04']);

        $this->assertSame('cho_thanh_toan', DB::table('don_hangs')->where('id', 1)->value('trang_thai_thanh_toan'));
        Mail::assertNothingSent();
    }

    public function test_cancelled_vnpay_payment_cancels_order_and_restores_stock(): void
    {
        $this->configureVnpay();
        $this->createPendingCardOrder();
        $params = $this->signedVnpayParams(['vnp_ResponseCode' => '24', 'vnp_TransactionStatus' => '02']);

        $this->getJson('/api/webhooks/vnpay?' . http_build_query($params))->assertJson(['RspCode' => '00']);

        $this->assertSame('da_huy_hoan_kho', DB::table('don_hangs')->where('id', 1)->value('trang_thai'));
        $this->assertSame('thanh_toan_that_bai', DB::table('don_hangs')->where('id', 1)->value('trang_thai_thanh_toan'));
        $this->assertSame('cancelled', DB::table('vnpay_payments')->value('status'));
        $this->assertSame(3, (int) DB::table('san_phams')->where('id', 1)->value('so_luong'));
        $this->assertSame(3, (int) DB::table('san_phams')->where('id', 1)->value('so_luong_kho'));
    }

    public function test_expired_card_orders_command_restores_stock(): void
    {
        $this->configureVnpay();
        $this->createPendingCardOrder();
        DB::table('vnpay_payments')->update(['created_at' => now()->subHour()]);

        $this->artisan('payments:expire-unpaid-card-orders')->assertSuccessful();

        $this->assertSame('expired', DB::table('vnpay_payments')->value('status'));
        $this->assertSame(3, (int) DB::table('san_phams')->where('id', 1)->value('so_luong'));
    }

    private function configureVnpay(): void
    {
        config([
            'services.vnpay.tmn_code' => 'TESTCODE',
            'services.vnpay.hash_secret' => 'test_hash_secret',
            'services.vnpay.url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
        ]);
    }

    private function createPendingCardOrder(): void
    {
        DB::table('san_phams')->insert([
            'id' => 1,
            'ten' => 'Gói chăm sóc xe',
            'gia' => 2500000,
            'so_luong' => 2,
            'so_luong_kho' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('don_hangs')->insert([
            'id' => 1,
            'users_id' => 42,
            'tong_tien' => 2500000,
            'giam_gia' => 0,
            'thanh_tien' => 2500000,
            'trang_thai' => 'cho_xac_nhan',
            'phuong_thuc_tt' => 'the',
            'trang_thai_thanh_toan' => 'cho_thanh_toan',
            'ten_nguoi_nhan' => 'Khách thẻ',
            'email_nguoi_nhan' => 'card@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('chi_tiet_don_hangs')->insert([
            'don_hang_id' => 1,
            'san_pham_id' => 1,
            'so_luong' => 1,
            'don_gia' => 2500000,
            'thanh_tien' => 2500000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vnpay_payments')->insert([
            'don_hang_id' => 1,
            'txn_ref' => 'DH1T100',
            'amount' => 2500000,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function signedVnpayParams(array $overrides): array
    {
        $params = array_merge([
            'vnp_TmnCode' => 'TESTCODE',
            'vnp_TxnRef' => 'DH1T100',
            'vnp_Amount' => '250000000',
            'vnp_TransactionNo' => '14000001',
            'vnp_BankCode' => 'NCB',
        ], $overrides);
        ksort($params);
        $query = collect($params)->map(fn ($v, $k) => urlencode($k) . '=' . urlencode($v))->implode('&');
        $params['vnp_SecureHash'] = hash_hmac('sha512', $query, 'test_hash_secret');

        return $params;
    }

    public function test_completed_cash_on_delivery_order_sends_payment_receipt(): void
    {
        Mail::fake();
        DB::table('don_hangs')->insert([
            'id' => 1,
            'users_id' => 42,
            'tong_tien' => 2500,
            'giam_gia' => 0,
            'thanh_tien' => 2500,
            'trang_thai' => 'dang_giao',
            'phuong_thuc_tt' => 'tien_mat_khi_nhan_hang',
            'trang_thai_thanh_toan' => 'thanh_toan_khi_nhan_hang',
            'ten_nguoi_nhan' => 'Khách COD',
            'email_nguoi_nhan' => 'cod@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $request = Request::create('/api/don-hang/cap-nhat-trang-thai', 'POST', [
            'don_hang_id' => 1,
            'trang_thai' => 'hoan_thanh',
        ]);

        $response = app(DonHangController::class)->capNhatTrangThai($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('da_thanh_toan', DB::table('don_hangs')->where('id', 1)->value('trang_thai_thanh_toan'));
        $this->assertSame('da_thanh_toan', DB::table('hoa_dons')->where('don_hang_id', 1)->value('trang_thai_tt'));
        Mail::assertSent(PaymentReceiptMail::class, fn (PaymentReceiptMail $mail) => $mail->orderId === 1);
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
