<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class donHangModel extends Model
{
    use HasFactory;

    protected $table = 'don_hangs';

    protected $fillable = [
        'users_id',
        'tong_tien',
        'giam_gia',
        'thanh_tien',
        'trang_thai',
        'phuong_thuc_tt',
        'dia_chi_giao_hang_id',
        'ngay_tao'
    ];

    // ==========================
    //  TRẠNG THÁI ĐƠN HÀNG
    // ==========================
    const TRANG_THAI_CHO_XAC_NHAN    = 'cho_xac_nhan';        // 1. Chờ xác nhận từ hệ thống
    const TRANG_THAI_DA_XAC_NHAN     = 'da_xac_nhan';         // 2. Đang chuẩn bị đơn hàng
    const TRANG_THAI_DANG_GIAO       = 'dang_giao';           // 3. Đang vận chuyển
    const TRANG_THAI_HOAN_THANH      = 'hoan_thanh';          // 4. Đã giao thành công (Tạo hóa đơn & doanh thu)
    const TRANG_THAI_YEU_CAU_HUY     = 'yeu_cau_huy';         // Khách muốn hủy khi đang giao
    const TRANG_THAI_DA_HUY_HOAN_KHO = 'da_huy_hoan_kho';     // Trưởng phòng KD duyệt hủy -> Cộng trả xe vào kho

    // ==========================
    //  THANH TOÁN
    // ==========================
    const THANH_TOAN_CHUA = 'chua_thanh_toan';
    const THANH_TOAN_ROI  = 'da_thanh_toan';

    // ==========================
    //  QUAN HỆ
    // ==========================

    public function chiTiet()
    {
        return $this->hasMany(chiTietDonHangModel::class, 'don_hang_id');
    }

    public function nguoiDung()
    {
        return $this->belongsTo(userModel::class, 'users_id');
    }
}