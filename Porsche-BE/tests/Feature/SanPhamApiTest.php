<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SanPhamApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['anh_san_phams', 'san_pham_mau_sac', 'mau_sacs', 'san_phams', 'mau_xes'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('mau_xes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hang_xe_id');
            $table->string('ten');
            $table->text('mo_ta')->nullable();
            $table->timestamps();
        });

        Schema::create('san_phams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mau_xe_id');
            $table->string('ten');
            $table->text('mo_ta')->nullable();
            $table->decimal('gia', 15, 2);
            $table->integer('so_luong')->default(0);
            $table->timestamps();
        });

        Schema::create('mau_sacs', function (Blueprint $table) {
            $table->id();
            $table->string('ten');
            $table->string('ma_mau');
            $table->string('loai')->nullable();
            $table->timestamps();
        });

        Schema::create('san_pham_mau_sac', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('san_pham_id');
            $table->unsignedBigInteger('mau_sac_id');
            $table->decimal('gia_them', 12, 2)->default(0);
            $table->integer('so_luong')->default(0);
            $table->timestamps();
        });

        Schema::create('anh_san_phams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('san_pham_id');
            $table->string('duong_dan');
            $table->boolean('la_anh_chinh')->default(false);
            $table->timestamps();
        });
    }

    public function test_it_lists_products_with_vehicle_color_and_image_data(): void
    {
        DB::table('mau_xes')->insert([
            'id' => 1,
            'hang_xe_id' => 1,
            'ten' => 'Porsche 911 Carrera',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('san_phams')->insert([
            'id' => 1,
            'mau_xe_id' => 1,
            'ten' => 'Bộ nâng cấp Porsche 911',
            'gia' => 1200,
            'so_luong' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('mau_sacs')->insert([
            'id' => 1,
            'ten' => 'Đen',
            'ma_mau' => '#000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('san_pham_mau_sac')->insert([
            'san_pham_id' => 1,
            'mau_sac_id' => 1,
            'gia_them' => 50,
            'so_luong' => 2,
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

        $this->getJson('/api/san-pham?mau_xe_id=1')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.data.0.ten', 'Bộ nâng cấp Porsche 911')
            ->assertJsonPath('data.data.0.mau_xe.ten', 'Porsche 911 Carrera')
            ->assertJsonPath('data.data.0.mau_sacs.0.pivot.gia_them', 50)
            ->assertJsonPath('data.data.0.anh_san_phams.0.duong_dan', 'products/911.jpg');
    }
}
