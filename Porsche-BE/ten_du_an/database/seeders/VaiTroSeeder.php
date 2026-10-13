<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class VaiTroSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('vai_tros')->insert([
            // 1. Quản trị & Ban điều hành (Executive & Management)
            [
                'ten' => 'Super Admin',
                'ma_vai_tro' => 'super_admin',
                'mo_ta' => 'Quản trị viên toàn quyền hệ thống, cấu hình phân quyền'
            ],
            [
                'ten' => 'Giám đốc / Ban quản trị',
                'ma_vai_tro' => 'executive',
                'mo_ta' => 'Xem báo cáo doanh thu, tồn kho, chỉ số KPI toàn hệ thống'
            ],
            [
                'ten' => 'Quản lý Chi nhánh / Showroom Manager',
                'ma_vai_tro' => 'branch_manager',
                'mo_ta' => 'Quản lý toàn bộ hoạt động kinh doanh và dịch vụ tại 1 chi nhánh'
            ],

            // 2. Khối Kinh doanh & Ban Xe (Sales Department)
            [
                'ten' => 'Trưởng phòng Kinh doanh',
                'ma_vai_tro' => 'sales_manager',
                'mo_ta' => 'Quản lý đội ngũ Sales, duyệt hợp đồng mua bán xe và hạn mức giảm giá'
            ],
            [
                'ten' => 'Tư vấn bán hàng (Sales Executive)',
                'ma_vai_tro' => 'sales_executive',
                'mo_ta' => 'Tư vấn xe, đăng ký lái thử, lập báo giá và hợp đồng bán xe'
            ],

            // 3. Khối Dịch vụ & Workshop (Garage / Xưởng 3S)
            [
                'ten' => 'Trưởng xưởng dịch vụ',
                'ma_vai_tro' => 'service_manager',
                'mo_ta' => 'Quản lý toàn bộ tiến độ sửa chữa, bảo dưỡng và nâng cấp xe'
            ],
            [
                'ten' => 'Cố vấn dịch vụ (Service Advisor)',
                'ma_vai_tro' => 'service_advisor',
                'mo_ta' => 'Tiếp nhận xe, lập phiếu báo giá dịch vụ, tư vấn gói độ/sửa chữa'
            ],
            [
                'ten' => 'Kỹ thuật viên trưởng (Lead Technician / Quản xưởng)',
                'ma_vai_tro' => 'lead_technician',
                'mo_ta' => 'Chẩn đoán lỗi phức tạp, Remap ECU, phân công thợ và kiểm tra chất lượng (QC)'
            ],
            [
                'ten' => 'Kỹ thuật viên / Thợ máy',
                'ma_vai_tro' => 'technician',
                'mo_ta' => 'Thực hiện lắp ráp phụ tùng, bảo dưỡng, sửa chữa theo phiếu lệnh'
            ],

            // 4. Khối Kho & Phụ tùng (Parts & Inventory)
            [
                'ten' => 'Quản lý Kho & Phụ tùng',
                'ma_vai_tro' => 'inventory_manager',
                'mo_ta' => 'Quản lý nhập/xuất/kiểm kê xe nguyên chiếc và linh kiện phụ tùng'
            ],
            [
                'ten' => 'Thủ kho phụ tùng (Parts Keeper)',
                'ma_vai_tro' => 'parts_keeper',
                'mo_ta' => 'Xuất phụ tùng cho xưởng theo phiếu lệnh dịch vụ, quản lý vị trí kệ kho'
            ],

            // 5. Khối Tài chính - Kế toán (Finance & Accounting)
            [
                'ten' => 'Kế toán trưởng / Quản lý tài chính',
                'ma_vai_tro' => 'chief_accountant',
                'mo_ta' => 'Duyệt báo cáo thu chi, công nợ nhà cung cấp và đối tác'
            ],
            [
                'ten' => 'Kế toán thu ngân / Thủ quỹ',
                'ma_vai_tro' => 'cashier',
                'mo_ta' => 'Thu tiền dịch vụ/bán xe, xuất hóa đơn VAT cho khách hàng'
            ],

            // 6. Khối Chăm sóc khách hàng & Marketing (CR & Marketing)
            [
                'ten' => 'Chuyên viên Chăm sóc khách hàng (CSKH)',
                'ma_vai_tro' => 'customer_relation',
                'mo_ta' => 'Nhắc lịch bảo dưỡng, khảo sát độ hài lòng (CSI), tiếp nhận khiếu nại'
            ],
            [
                'ten' => 'Quản lý Marketing & Nội dung',
                'ma_vai_tro' => 'marketing_manager',
                'mo_ta' => 'Quản lý bài viết diễn đàn, tạo mã giảm giá, chương trình khuyến mãi'
            ],

            // 7. Khối Khách hàng (Clients)
            [
                'ten' => 'Khách hàng VIP / Doanh nghiệp',
                'ma_vai_tro' => 'vip_customer',
                'mo_ta' => 'Khách hàng sở hữu nhiều xe hoặc đối tác fleet, hưởng chính sách riêng'
            ],
            [
                'ten' => 'Khách hàng cá nhân',
                'ma_vai_tro' => 'customer',
                'mo_ta' => 'Tài khoản người dùng chuẩn trên ứng dụng/website'
            ],
        ]);
    }
}