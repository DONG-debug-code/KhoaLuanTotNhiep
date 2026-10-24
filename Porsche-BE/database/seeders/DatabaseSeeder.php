<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // =========================================================================
            // Giai đoạn 1: Khởi tạo Phân quyền & Người dùng (Bảng gốc cốt lõi)
            // =========================================================================
            PermissionSeeder::class,
            VaiTroSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,

            // 💡 VỊ TRÍ 1: Thêm Seeder cấu hình AI mặc định (System Prompt & Temperature) tại đây
            PorscheAiConfigSeeder::class,

            // =========================================================================
            // Giai đoạn 2: Cấu trúc Sản phẩm & Thuộc tính xe (Phải chạy trước khi tạo đơn hàng)
            // =========================================================================
            HangXeSeeder::class,
            DanhMucSeeder::class,
            MauSacSeeder::class,
            MauXeSeeder::class,
            SanPhamSeeder::class,

            // 💡 VỊ TRÍ 2: Thêm Seeder các sản phẩm công nghệ, đồ độ Porsche cao cấp tại đây
            // (Nó đứng sau SanPhamSeeder để đảm bảo các bảng danh mục cha đã có sẵn dữ liệu)
            PorscheWorkshopProductSeeder::class,

            SanPhamMauSacSeeder::class,
            AnhSanPhamSeeder::class,
            PhuTungSeeder::class,
            WorkshopBookingSeeder::class, // gồm cả ChuyenGiaSeeder

            // =========================================================================
            // Giai đoạn 3: Bản độ xe & Khuyến mãi (Voucher)
            // =========================================================================
            BanDoSeeder::class,
            ChiTietBanDoSeeder::class,
            PhieuGiamGiaSeeder::class,

            // =========================================================================
            // Giai đoạn 4: Đơn hàng & Chi tiết hóa đơn (Bảng cha chạy trước bảng con)
            // =========================================================================
            DonHangSeeder::class,
            ChiTietDonHangSeeder::class,

            // =========================================================================
            // Giai đoạn 5: Các bảng lưu lịch sử, tương tác & Nhật ký (Bắt buộc chạy cuối cùng)
            // =========================================================================
            LichSuSuDungPhieuSeeder::class,
            DanhGiaSanPhamSeeder::class,
            BaiVietSeeder::class,
            BinhLuanSeeder::class,
            AiKetQuaSeeder::class,
            TuongThichSeeder::class,
            NhatKyKhoSeeder::class,
            NhatKyAdminSeeder::class,
        ]);
    }
}
