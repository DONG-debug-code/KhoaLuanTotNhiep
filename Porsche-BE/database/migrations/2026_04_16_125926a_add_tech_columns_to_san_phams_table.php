<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTechColumnsToSanPhamsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('san_phams', function (Blueprint $col) {
            // 1. Thêm cột phân loại sản phẩm độ (Tự động đẩy xuống cuối bảng, không cần after)
            if (!Schema::hasColumn('san_phams', 'loai_san_pham')) {
                $col->string('loai_san_pham')->default('phan_cung');
            }

            // 2. Thêm cột JSON động để lưu thông số kỹ thuật chi tiết
            if (!Schema::hasColumn('san_phams', 'thong_so_chi_tiet')) {
                $col->json('thong_so_chi_tiet')->nullable();
            }

            // 3. Tạo cột alias tên sản phẩm nếu bảng gốc chưa có cột này
            if (!Schema::hasColumn('san_phams', 'ten_san_pham')) {
                $col->string('ten_san_pham')->nullable();
            }

            // 4. Tạo cột alias số lượng kho nếu bảng gốc chưa có
            if (!Schema::hasColumn('san_phams', 'so_luong_kho')) {
                $col->integer('so_luong_kho')->default(0);
            }

            // 5. Dự phòng tạo luôn cột slug nếu bảng gốc của bạn đang thiếu
            if (!Schema::hasColumn('san_phams', 'slug')) {
                $col->string('slug')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('san_phams', function (Blueprint $col) {
            $col->dropColumn(['loai_san_pham', 'thong_so_chi_tiet', 'ten_san_pham', 'so_luong_kho', 'slug']);
        });
    }
}
