<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\NhanVienController;
use App\Http\Controllers\ChuyenGiaController;
use App\Http\Controllers\KhachHangController;
use App\Http\Controllers\ChucVuController;
use App\Http\Controllers\ChucNangController;
use App\Http\Controllers\PhanQuyenController;
use App\Http\Controllers\GoiDoXeController;
use App\Http\Controllers\PhuTungController;
use App\Http\Controllers\PhieuDatLichController;
use App\Http\Controllers\LichLamViecController;
use App\Http\Controllers\HoaDonController;
use App\Http\Controllers\HoSoXeController;
use App\Http\Controllers\SlideController;
use App\Http\Controllers\ThongKeController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\HomePageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ===================== 👑 I. API HỆ THỐNG QUẢN TRỊ (ADMIN) =====================
|--------------------------------------------------------------------------
*/

Route::post('/admin/login', [AdminController::class, 'loginAdmin']);

Route::group(['prefix' => 'admin', 'middleware' => 'AdminMiddleware'], function () {

    // Hệ thống & Xuất dữ liệu
    Route::post('khach-hang/xuat-excel', [AdminController::class, 'xuatExcelKhachHang']);
    Route::post('logout', [AdminController::class, 'logoutAdmin']);
    Route::get('/check-token', [AdminController::class, 'checkTokenAdmin']);

    // Profile Admin
    Route::get('profile/data', [AdminController::class, 'getProfile']);
    Route::post('profile/update', [AdminController::class, 'updateProfile']);
    Route::post('profile/update-avatar', [AdminController::class, 'updateProfileAvatar']);
    Route::post('profile/change-password', [AdminController::class, 'changePassword']);
    Route::post('hoa-don/chi-tiet', [PhieuDatLichController::class, 'hoaDonChiTiet']);

    // 🔑 CORE 1: QUẢN LÝ CHỨC VỤ (ROLES)
    Route::get('/chuc-vu/data', [ChucVuController::class, 'getData']);
    Route::post('/chuc-vu/create', [ChucVuController::class, 'store']);
    Route::post('/chuc-vu/delete', [ChucVuController::class, 'destroyChucVu']);
    Route::post('/chuc-vu/update', [ChucVuController::class, 'updateChucVu']);
    Route::post('/chuc-vu/change-status', [ChucVuController::class, 'changeStatusChucVu']);
    Route::post('/chuc-vu/search', [ChucVuController::class, 'searchChucVu']);

    // 🔑 CORE 2: QUẢN LÝ CHỨC NĂNG (PERMISSIONS SYSTEM)
    Route::get('/chuc-nang/data', [ChucNangController::class, 'getDataChucNang']);

    // 🔑 CORE 3: CẤU HÌNH PHÂN QUYỀN
    Route::get('/phan-quyen/chi-tiet-data', [PhanQuyenController::class, 'getData']);
    Route::post('/phan-quyen/create', [PhanQuyenController::class, 'createPhanQuyen']);
    Route::post('/phan-quyen/delete', [PhanQuyenController::class, 'deletePhanQuyen']);
    Route::post('/phan-quyen/update', [PhanQuyenController::class, 'updatePhanQuyen']);

    // 👥 QUẢN LÝ NHÂN VIÊN WORKSHOP (Kỹ thuật viên, Remap-er, Cố vấn dịch vụ)
    Route::get('/nhan-vien/data', [NhanVienController::class, 'getDataNhanVien']);
    Route::post('/nhan-vien/search', [NhanVienController::class, 'searchNhanVien']);
    Route::post('/nhan-vien/create', [NhanVienController::class, 'storeNhanVien']);
    Route::post('/nhan-vien/delete', [NhanVienController::class, 'destroyNhanVien']);
    Route::post('/nhan-vien/update', [NhanVienController::class, 'updateNhanVien']);
    Route::post('/nhan-vien/change-status', [NhanVienController::class, 'changeStatusNhanVien']);

    // 🏎️ QUẢN LÝ KHÁCH HÀNG & CHỦ XE PORSCHE
    Route::get('/khach-hang/data', [KhachHangController::class, 'getDataKhachHang']);
    Route::post('/khach-hang/create', [KhachHangController::class, 'storeKhachHang']);
    Route::post('/khach-hang/delete', [KhachHangController::class, 'destroyKhachHang']);
    Route::post('/khach-hang/update', [KhachHangController::class, 'updateKhachHang']);
    Route::post('/khach-hang/change-status', [KhachHangController::class, 'changeStatusKhachHang']);
    Route::post('/khach-hang/search', [KhachHangController::class, 'searchKhachHang']);
    Route::post('/khach-hang/chi-tiet-lich-su-do/{id}', [KhachHangController::class, 'lichSuDoXeById']);

    // 🖼️ QUẢN LÝ SLIDE & BANNER MARKETING
    Route::get('/slide/data', [SlideController::class, 'getDataSlide']);
    Route::post('/slide/create', [SlideController::class, 'createSlide']);
    Route::post('/slide/update', [SlideController::class, 'updateSlide']);
    Route::post('/slide/delete', [SlideController::class, 'deleteSlide']);
    Route::post('/slide/change-status', [SlideController::class, 'changeStatusSlide']);

    // 📑 QUẢN LÝ HỒ SƠ BỆNH ÁN XE (HISTORY LOG / TUNING SPECIFICATION)
    Route::get('/ho-so-xe/data', [HoSoXeController::class, 'getDataHoSoXe']);
    Route::post('/ho-so-xe/tim-kiem', [HoSoXeController::class, 'timKiemHoSoXe']);
    Route::post('/ho-so-xe/create', [HoSoXeController::class, 'createHoSoXe']);
    Route::post('/ho-so-xe/update', [HoSoXeController::class, 'updateHoSoXe']);
    Route::post('/ho-so-xe/delete', [HoSoXeController::class, 'deleteHoSoXe']);
    Route::post('/ho-so-xe/change-status', [HoSoXeController::class, 'changeStatusHoSoXe']);

    // 📅 QUẢN LÝ LỊCH HẸN ĐỘ XE / BẢO DƯỠNG DETAIING
    Route::get('/lich-hen/data', [PhieuDatLichController::class, 'getLichHenData']);
    Route::post('/lich-hen/tim-kiem', [PhieuDatLichController::class, 'timKiemLichHen']);
    Route::post('/lich-hen/hoan-thanh', [PhieuDatLichController::class, 'hoanThanhLichHen']);
    Route::post('/lich-hen/create', [PhieuDatLichController::class, 'createLichHen']);
    Route::post('/lich-hen/xac-nhan', [PhieuDatLichController::class, 'xacNhanLichHen']);
    Route::post('/lich-hen/huy', [PhieuDatLichController::class, 'huyLichHen']);
    Route::post('/lich-hen/change-status', [PhieuDatLichController::class, 'changeStatusLichHen']);

    // ⏱️ QUẢN LÝ LỊCH CỦA CHUYÊN GIA / THỢ MÁY (ROSTER)
    Route::post('/lich-lam-viec/data/{id_chuyen_gia}', [LichLamViecController::class, 'getNgayLamViecChuyenGia']);
    Route::get('/lich-lam-viec-chuyen-gia/data', [LichLamViecController::class, 'getDataLichLamViecChuyenGia']);
    Route::post('/lich-lam-viec-chuyen-gia/create', [LichLamViecController::class, 'createLichLamViecChuyenGia']);

    // 👨‍🔧 QUẢN LÝ CHUYÊN GIA / TUNER ĐỘ XE CHÍNH
    Route::get('/chuyen-gia/data', [ChuyenGiaController::class, 'getDataChuyenGia']);
    Route::post('/chuyen-gia/create', [ChuyenGiaController::class, 'storeChuyenGia']);
    Route::post('/chuyen-gia/update', [ChuyenGiaController::class, 'updateChuyenGia']);
    Route::post('/chuyen-gia/delete', [ChuyenGiaController::class, 'destroyChuyenGia']);
    Route::post('/chuyen-gia/search', [ChuyenGiaController::class, 'searchChuyenGia']);
    Route::post('/chuyen-gia/change-status', [ChuyenGiaController::class, 'changeStatusChuyenGia']);

    // 📦 QUẢN LÝ PHÒNG TRƯNG BÀY / KHU VỰC WORKSHOP (BAY ĐỘ XE)
    Route::get('/khu-vuc-workshop/data', [GoiDoXeController::class, 'getDataKhuVuc']);
    Route::post('/khu-vuc-workshop/search', [GoiDoXeController::class, 'searchKhuVuc']);
    Route::post('/khu-vuc-workshop/create', [GoiDoXeController::class, 'storeKhuVuc']);
    Route::post('/khu-vuc-workshop/delete', [GoiDoXeController::class, 'destroyKhuVuc']);
    Route::post('/khu-vuc-workshop/update', [GoiDoXeController::class, 'updateKhuVuc']);
    Route::post('/khu-vuc-workshop/change-status', [GoiDoXeController::class, 'changeStatusKhuVuc']);

    // 🛠️ QUẢN LÝ PHÂN LOẠI GÓI ĐỘ / HÃNG PHỤ TÙNG (Akrapovič, TechArt, Fi Exhaust...)
    Route::get('/hang-phu-tung/data', [PhuTungController::class, 'getDataPhuTung']);
    Route::post('/hang-phu-tung/search', [PhuTungController::class, 'searchPhuTung']);
    Route::post('/hang-phu-tung/create', [PhuTungController::class, 'storePhuTung']);
    Route::post('/hang-phu-tung/delete', [PhuTungController::class, 'destroyPhuTung']);
    Route::post('/hang-phu-tung/update', [PhuTungController::class, 'updatePhuTung']);
    Route::post('/hang-phu-tung/change-status', [PhuTungController::class, 'changeStatusPhuTung']);

    // 📈 THỐNG KÊ DOANH THU & ĐƠN HÀNG WORKSHOP
    Route::post('/thong-ke/lich-hen-theo-hang', [ThongKeController::class, 'getLichHenTheoHangPhuTung']);
    Route::post('/thong-ke/do-xe-theo-chuyen-gia', [ThongKeController::class, 'getLichDoXeTheoChuyenGia']);
    Route::post('/thong-ke/xe-theo-khu-vuc-workshop', [ThongKeController::class, 'getXeTheoKhuVuc']);
    Route::post('/thong-ke/khach-hang-theo-chuyen-gia', [ThongKeController::class, 'getKhachHangTheoChuyenGia']);
    Route::post('/thong-ke/lich-do-xe-theo-khu-vuc', [ThongKeController::class, 'getLichLamViecTheoKhuVuc']);

    // Dashboard Executive
    Route::get('/dashboard/data', [ThongKeController::class, 'getDashboardData']);
    Route::get('/hoa-don/data', [HoaDonController::class, 'getDataHoaDon']);
    Route::post('/kiem-tra-phieu-dat-lich', [PhieuDatLichController::class, 'kiemTraMaDatLich']);
});

/*
|--------------------------------------------------------------------------
| ===================== 👨‍🔧 II. API CHO CHUYÊN GIA / TUNER =====================
|--------------------------------------------------------------------------
*/
Route::post('chuyen-gia/login', [ChuyenGiaController::class, 'loginChuyenGia']);
Route::post('chuyen-gia/register', [ChuyenGiaController::class, 'registerChuyenGia']);
Route::post('chuyen-gia/kich-hoat', [ChuyenGiaController::class, 'kichHoatChuyenGia']);
Route::post('chuyen-gia/quen-mat-khau', [ChuyenGiaController::class, 'quenMatKhauChuyenGia']);
Route::post('chuyen-gia/doi-mat-khau', [ChuyenGiaController::class, 'doiMatKhauChuyenGia']);

Route::group(['prefix' => '/chuyen-gia', 'middleware' => 'ChuyenGiaMiddleware'], function () {
    Route::post('logout', [ChuyenGiaController::class, 'logoutChuyenGia']);
    Route::get('/check-token', [ChuyenGiaController::class, 'checkTokenChuyenGia']);

    // Quản Lý Khách Hàng Thuộc Cố Vấn Của Mình
    Route::get('/khach-hang/data', [ChuyenGiaController::class, 'getDataKhachHang']);
    Route::post('/khach-hang/lich-su-do', [ChuyenGiaController::class, 'getLichSuDoXe']);
    Route::post('/khach-hang/tim-kiem', [ChuyenGiaController::class, 'timKiemKhachHang']);

    // Cá nhân Profile
    Route::get('/profile/data', [ChuyenGiaController::class, 'getProfile']);
    Route::post('/profile/update', [ChuyenGiaController::class, 'updateProfile']);
    Route::post('/profile/update-avatar', [ChuyenGiaController::class, 'updateProfileAvatar']);
    Route::post('/profile/change-password', [ChuyenGiaController::class, 'changePassword']);

    // Quản lý Workflow & Tiến độ nâng cấp xe
    Route::get('/lich-dat-do/data', [ChuyenGiaController::class, 'getDataLichHenDoXe']);
    Route::get('/khu-vuc-workshop/data', [ChuyenGiaController::class, 'getDataKhuVucWorkshop']);
    Route::get('/hang-phu-tung/data', [ChuyenGiaController::class, 'getDataHangPhuTung']);
    Route::get('/lich-lam-viec/data', [ChuyenGiaController::class, 'getDataLichLamViecCaNhan']);
});

/*
|--------------------------------------------------------------------------
| ===================== 🏎️ III. API KHÁCH HÀNG (CHỦ XE PORSCHE) =====================
|--------------------------------------------------------------------------
*/
Route::post('khach-hang/login', [KhachHangController::class, 'loginKhachHang']);
Route::post('khach-hang/register', [KhachHangController::class, 'registerKhachHang']);
Route::post('khach-hang/kich-hoat', [KhachHangController::class, 'kichHoatKhachHang']);
Route::post('khach-hang/quen-mat-khau', [KhachHangController::class, 'quenMatKhauKhachHang']);
Route::post('khach-hang/doi-mat-khau', [KhachHangController::class, 'doiMatKhauKhachHang']);

Route::group(['prefix' => '/khach-hang', 'middleware' => 'KhachHangMiddleware'], function () {
    Route::post('logout', [KhachHangController::class, 'logoutKhachHang']);
    Route::post('logout-all', [KhachHangController::class, 'logoutAllKhachHang']);
    Route::get('/check-token', [KhachHangController::class, 'checkTokenKhachHang']);

    // Khách hàng quản lý Profile & Garage cá nhân
    Route::get('profile/data', [KhachHangController::class, 'getProfile']);
    Route::post('profile/update', [KhachHangController::class, 'updateProfile']);
    Route::post('profile/update-avatar', [KhachHangController::class, 'updateProfileAvatar']);
    Route::post('profile/change-password', [KhachHangController::class, 'changePassword']);

    // Theo dõi tiến độ đơn hàng
    Route::get('/lich-hen/data', [KhachHangController::class, 'getDataLichHenCuaToi']);

    // Đặt lịch Workshop & Nâng cấp
    Route::post('/dat-lich-kham', [PhieuDatLichController::class, 'datLichKhamBenhNhan']);
    Route::post('/huy-lich-kham', [PhieuDatLichController::class, 'huyLichKhamBenhNhan']);
    Route::post('/chuyen-gia/by-hang-phu-tung', [PhieuDatLichController::class, 'getBacSiTheoChuyenKhoa']);

    // 🤖 LỊCH SỬ CHAT VÀ XÓA LỊCH SỬ (Bắt buộc phải đăng nhập)
    Route::get('/ai/history', [AiController::class, 'getHistory']);
    Route::delete('/ai/clear-history', [AiController::class, 'clearHistory']);
});

/*
|--------------------------------------------------------------------------
| ===================== 🌐 IV. API CLIENT (PORTAL WEB CÔNG KHAI) =====================
|--------------------------------------------------------------------------
*/
// 🔥 🤖 ROUTE CHATBOT TRỢ LÝ AI (Công khai - Cho phép cả khách vãng lai trải nghiệm)
Route::post('/ai/ask', [AiController::class, 'askAI']);

Route::get('/home-page/data', [HomePageController::class, 'homePageData']);
Route::get('/client/hang-phu-tung/data', [HomePageController::class, 'getChuyenKhoaClient']);
Route::get('/client/hang-phu-tung/chi-tiet/{id}', [HomePageController::class, 'getChuyenKhoaClientById']);
Route::get('/client/chuyen-gia-tuning/data', [HomePageController::class, 'getBacSiClient']);
Route::get('/client/chuyen-gia-tuning/chi-tiet/{id}', [HomePageController::class, 'getBacSiClientByIds']);

/*
|--------------------------------------------------------------------------
| ===================== ⚙️ V. CRONJOB / WEBHOOK AUTOMATION =====================
|--------------------------------------------------------------------------
*/
Route::get('/auto-thanh-toan-lich-hen', [PhieuDatLichController::class, 'autoThanhToanLichHen']);
Route::get('/gui-mail-thong-bao-lich-hen', [PhieuDatLichController::class, 'GUImaillichhen']);