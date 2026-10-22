<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('don_hangs', function (Blueprint $table) {
            foreach ([
                'ten_nguoi_nhan',
                'email_nguoi_nhan',
                'so_dien_thoai_nguoi_nhan',
                'dia_chi_nguoi_nhan',
                'ghi_chu_don_hang',
                'phuong_thuc_nhan',
                'phi_van_chuyen',
                'trang_thai_thanh_toan',
            ] as $column) {
                if (Schema::hasColumn('don_hangs', $column)) {
                    continue;
                }

                match ($column) {
                    'dia_chi_nguoi_nhan', 'ghi_chu_don_hang' => $table->text($column)->nullable(),
                    'phi_van_chuyen' => $table->decimal($column, 15, 2)->nullable(),
                    'phuong_thuc_nhan' => $table->string($column, 50)->nullable(),
                    'trang_thai_thanh_toan' => $table->string($column, 50)->default('chua_thanh_toan'),
                    default => $table->string($column, 255)->nullable(),
                };
            }
        });

        if (!Schema::hasColumn('chi_tiet_don_hangs', 'phu_tung_id')) {
            Schema::table('chi_tiet_don_hangs', function (Blueprint $table) {
                $table->foreignId('phu_tung_id')->nullable()->after('san_pham_id')
                    ->constrained('phu_tungs')->nullOnDelete();
            });
        }

        $orderDetailProduct = collect(Schema::getColumns('chi_tiet_don_hangs'))
            ->firstWhere('name', 'san_pham_id');
        if ($orderDetailProduct && !$orderDetailProduct['nullable']) {
            Schema::table('chi_tiet_don_hangs', function (Blueprint $table) {
                $table->foreignId('san_pham_id')->nullable()->change();
            });
        }

        if (!Schema::hasColumn('nhat_ky_khos', 'phu_tung_id')) {
            Schema::table('nhat_ky_khos', function (Blueprint $table) {
                $table->foreignId('phu_tung_id')->nullable()->after('san_pham_id')
                    ->constrained('phu_tungs')->nullOnDelete();
            });
        }

        $inventoryColumns = Schema::getColumns('nhat_ky_khos');
        $productColumn = collect($inventoryColumns)->firstWhere('name', 'san_pham_id');
        if ($productColumn && !$productColumn['nullable']) {
            Schema::table('nhat_ky_khos', function (Blueprint $table) {
                $table->foreignId('san_pham_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('nhat_ky_khos', 'phu_tung_id')) {
            Schema::table('nhat_ky_khos', function (Blueprint $table) {
                $table->dropForeign(['phu_tung_id']);
                $table->dropColumn('phu_tung_id');
            });
        }

        if (Schema::hasColumn('chi_tiet_don_hangs', 'phu_tung_id')) {
            Schema::table('chi_tiet_don_hangs', function (Blueprint $table) {
                $table->dropForeign(['phu_tung_id']);
                $table->dropColumn('phu_tung_id');
            });
        }

        foreach ([
            'ten_nguoi_nhan',
            'email_nguoi_nhan',
            'so_dien_thoai_nguoi_nhan',
            'dia_chi_nguoi_nhan',
            'ghi_chu_don_hang',
            'phuong_thuc_nhan',
            'phi_van_chuyen',
            'trang_thai_thanh_toan',
        ] as $column) {
            if (Schema::hasColumn('don_hangs', $column)) {
                Schema::table('don_hangs', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }

        if (Schema::hasColumn('chi_tiet_don_hangs', 'san_pham_id')
            && !DB::table('chi_tiet_don_hangs')->whereNull('san_pham_id')->exists()) {
            Schema::table('chi_tiet_don_hangs', function (Blueprint $table) {
                $table->foreignId('san_pham_id')->nullable(false)->change();
            });
        }

        if (Schema::hasColumn('nhat_ky_khos', 'san_pham_id')
            && !DB::table('nhat_ky_khos')->whereNull('san_pham_id')->exists()) {
            Schema::table('nhat_ky_khos', function (Blueprint $table) {
                $table->foreignId('san_pham_id')->nullable(false)->change();
            });
        }
    }
};
