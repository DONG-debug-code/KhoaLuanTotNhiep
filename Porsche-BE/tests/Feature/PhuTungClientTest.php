<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhuTungClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('phu_tungs');
        Schema::create('phu_tungs', function (Blueprint $table) {
            $table->id();
            $table->string('ten');
            $table->decimal('gia', 15, 2);
            $table->integer('so_luong')->default(0);
            $table->string('loai');
            $table->text('mo_ta')->nullable();
            $table->json('bao_gom')->nullable();
            $table->json('dong_xe')->nullable();
            $table->string('anh')->nullable();
            $table->timestamps();
        });

        DB::table('phu_tungs')->insert([
            'id' => 1,
            'ten' => 'Gói nâng cấp Porsche',
            'gia' => 1200000,
            'so_luong' => 3,
            'loai' => 'hieu_suat',
            'mo_ta' => 'Mô tả ngắn',
            'bao_gom' => json_encode(['Hệ thống xả', 'Công lắp đặt']),
            'dong_xe' => json_encode(['911', '718']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_client_endpoints_return_package_details_as_json_arrays(): void
    {
        $expectedBaoGom = ['Hệ thống xả', 'Công lắp đặt'];
        $expectedDongXe = ['911', '718'];

        $this->getJson('/api/client/phu-tung/data')
            ->assertOk()
            ->assertJsonPath('data.0.bao_gom', $expectedBaoGom)
            ->assertJsonPath('data.0.dong_xe', $expectedDongXe);

        $this->getJson('/api/client/phu-tung/chi-tiet/1')
            ->assertOk()
            ->assertJsonPath('data.bao_gom', $expectedBaoGom)
            ->assertJsonPath('data.dong_xe', $expectedDongXe);
    }
}
