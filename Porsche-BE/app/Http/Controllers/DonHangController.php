<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\donHangModel;
use Exception;

class DonHangController extends Controller
{
    /**
     * 1. KHÁCH HÀNG: Đặt mua xe (Trừ kho ngay + Khóa hàng đợi Lock For Update)
     */
    public function datMuaXe(Request $request)
    {
        $request->validate([
            'san_pham_id'          => 'required|exists:san_phams,id',
            'phuong_thuc_tt'        => 'required|string',
            'dia_chi_giao_hang_id' => 'nullable|integer',
            'so_luong'             => 'nullable|integer|min:1',
        ]);

        $soLuongMua = $request->input('so_luong', 1);

        DB::beginTransaction();
        try {
            $userId = auth()->id();

            // Dùng lockForUpdate() để tránh 2 người cùng mua 1 chiếc xe cuối cùng đồng thời
            $sanPham = DB::table('san_phams')
                ->where('id', $request->san_pham_id)
                ->lockForUpdate()
                ->first();

            if (!$sanPham || $sanPham->so_luong < $soLuongMua) {
                DB::rollBack();
                return response()->json([
                    'status'  => false,
                    'message' => 'Xe hoặc sản phẩm này hiện tại không đủ số lượng trong kho!'
                ], 400);
            }

            $tongTien = $sanPham->gia * $soLuongMua;
            $giamGia = 0; 
            $thanhTien = $tongTien - $giamGia;

            $donHangId = DB::table('don_hangs')->insertGetId([
                'users_id'             => $userId,
                'tong_tien'            => $tongTien,
                'giam_gia'             => $giamGia,
                'thanh_tien'           => $thanhTien,
                'trang_thai'           => donHangModel::TRANG_THAI_CHO_XAC_NHAN,
                'phuong_thuc_tt'       => $request->phuong_thuc_tt,
                'dia_chi_giao_hang_id' => $request->dia_chi_giao_hang_id,
                'ngay_tao'             => now(),
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            DB::table('chi_tiet_don_hangs')->insert([
                'don_hang_id' => $donHangId,
                'san_pham_id' => $sanPham->id,
                'so_luong'    => $soLuongMua,
                'don_gia'     => $sanPham->gia,
                'thanh_tien'  => $thanhTien,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // Trừ kho xe ngay lập tức
            DB::table('san_phams')->where('id', $sanPham->id)->decrement('so_luong', $soLuongMua);

            DB::commit();

            return response()->json([
                'status'      => true,
                'message'     => 'Đặt mua xe thành công. Đơn hàng đang ở trạng thái: Chờ xác nhận.',
                'don_hang_id' => $donHangId
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Không thể đặt xe: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 2. KHÁCH HÀNG: Yêu cầu hủy đơn hàng (Ghi nhận lý do hủy)
     */
    public function khachYeuCauHuy(Request $request)
    {
        $request->validate([
            'don_hang_id' => 'required|exists:don_hangs,id',
            'ly_do_huy'   => 'required|string|max:500'
        ]);

        try {
            $userId = auth()->id();
            $donHang = DB::table('don_hangs')
                ->where('id', $request->don_hang_id)
                ->where('users_id', $userId)
                ->first();

            if (!$donHang) {
                return response()->json(['status' => false, 'message' => 'Không tìm thấy đơn hàng!'], 404);
            }

            // Đơn đã hoàn thành hoặc đã hủy trước đó thì không được gửi yêu cầu nữa
            if (in_array($donHang->trang_thai, [donHangModel::TRANG_THAI_HOAN_THANH, donHangModel::TRANG_THAI_DA_HUY_HOAN_KHO])) {
                return response()->json(['status' => false, 'message' => 'Đơn hàng đã hoàn tất hoặc đã hủy, không thể thay đổi!'], 400);
            }

            // Nếu đơn mới ở bước 'cho_xac_nhan' -> Hủy trực tiếp & hoàn tồn kho luôn
            if ($donHang->trang_thai === donHangModel::TRANG_THAI_CHO_XAC_NHAN) {
                DB::beginTransaction();
                
                DB::table('don_hangs')->where('id', $donHang->id)->update([
                    'trang_thai' => donHangModel::TRANG_THAI_DA_HUY_HOAN_KHO,
                    'updated_at' => now(),
                ]);

                // Cộng lại số lượng xe vào kho
                $chiTiets = DB::table('chi_tiet_don_hangs')->where('don_hang_id', $donHang->id)->get();
                foreach ($chiTiets as $ct) {
                    DB::table('san_phams')->where('id', $ct->san_pham_id)->increment('so_luong', $ct->so_luong);
                }

                DB::commit();
                return response()->json(['status' => true, 'message' => 'Đã hủy đơn hàng thành công và cập nhật lại kho!'], 200);
            }

            // Nếu đơn đang giao -> Chuyển sang trạng thái chờ Trưởng phòng Kinh doanh duyệt
            DB::table('don_hangs')->where('id', $donHang->id)->update([
                'trang_thai' => donHangModel::TRANG_THAI_YEU_CAU_HUY,
                'updated_at' => now(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Yêu cầu hủy đơn của bạn đã được gửi. Đang chờ Trưởng phòng Kinh doanh xác nhận hoàn xe về xưởng!'
            ], 200);

        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi xử lý: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 3. QUẢN TRỊ / SALES: Cập nhật tiến trình & Ghi nhận Doanh thu (Trường hợp 1)
     */
    public function capNhatTrangThai(Request $request)
    {
        $request->validate([
            'don_hang_id' => 'required|exists:don_hangs,id',
            'trang_thai'  => 'required|in:cho_xac_nhan,da_xac_nhan,dang_giao,hoan_thanh',
        ]);

        DB::beginTransaction();
        try {
            $donHang = DB::table('don_hangs')->where('id', $request->don_hang_id)->first();

            // Kiểm tra không cho phép sửa đơn hàng đã kết thúc
            if ($donHang->trang_thai === donHangModel::TRANG_THAI_HOAN_THANH) {
                return response()->json(['status' => false, 'message' => 'Đơn hàng này đã hoàn thành trước đó!'], 400);
            }

            if ($donHang->trang_thai === donHangModel::TRANG_THAI_DA_HUY_HOAN_KHO) {
                return response()->json(['status' => false, 'message' => 'Đơn hàng này đã bị hủy, không thể cập nhật trạng thái giao hàng!'], 400);
            }

            // TRƯỜNG HỢP 1: Giao hàng thành công -> Chuyển 'hoan_thanh' & Tạo Hóa đơn Doanh thu
            if ($request->trang_thai === donHangModel::TRANG_THAI_HOAN_THANH) {
                
                $existsHoaDon = DB::table('hoa_dons')->where('don_hang_id', $donHang->id)->exists();
                
                if (!$existsHoaDon) {
                    DB::table('hoa_dons')->insert([
                        'ma_hoa_don'     => 'HD-' . time(),
                        'don_hang_id'    => $donHang->id,
                        'users_id'       => $donHang->users_id,
                        'tong_tien'      => $donHang->thanh_tien,
                        'trang_thai_tt'  => 'da_thanh_toan',
                        'ngay_xuat'      => now(),
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }
            }

            DB::table('don_hangs')->where('id', $request->don_hang_id)->update([
                'trang_thai' => $request->trang_thai,
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => ($request->trang_thai === 'hoan_thanh') 
                    ? 'Đơn hàng đã HOÀN THÀNH. Hóa đơn và doanh thu đã được cộng vào hệ thống!' 
                    : 'Cập nhật trạng thái đơn hàng thành công.'
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Lỗi cập nhật: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 4. TRƯỞNG PHÒNG KINH DOANH: Duyệt hủy đơn & Hoàn xe lại vào kho (Trường hợp 2)
     */
    public function duyetHuyVaHoanKho(Request $request)
    {
        $request->validate([
            'don_hang_id' => 'required|exists:don_hangs,id',
            'dong_y_huy'  => 'required|boolean',
        ]);

        DB::beginTransaction();
        try {
            $donHang = DB::table('don_hangs')->where('id', $request->don_hang_id)->first();

            if ($donHang->trang_thai !== donHangModel::TRANG_THAI_YEU_CAU_HUY) {
                return response()->json(['status' => false, 'message' => 'Đơn hàng này không ở trạng thái chờ duyệt hủy!'], 400);
            }

            if ($request->dong_y_huy) {
                // TRƯỜNG HỢP 2: Chấp nhận hủy -> Hoàn xe (+1) vào kho & Không ghi nhận doanh thu
                DB::table('don_hangs')->where('id', $donHang->id)->update([
                    'trang_thai' => donHangModel::TRANG_THAI_DA_HUY_HOAN_KHO,
                    'updated_at' => now(),
                ]);

                $chiTiets = DB::table('chi_tiet_don_hangs')->where('don_hang_id', $donHang->id)->get();
                foreach ($chiTiets as $ct) {
                    DB::table('san_phams')
                        ->where('id', $ct->san_pham_id)
                        ->increment('so_luong', $ct->so_luong);
                }

                DB::commit();
                return response()->json([
                    'status'  => true,
                    'message' => 'Trưởng phòng Kinh doanh đã DUYỆT HỦY ĐƠN. Số lượng xe đã được hoàn trả lại về kho.'
                ], 200);

            } else {
                // Nếu bác bỏ yêu cầu hủy -> Cho đơn hàng tiếp tục giao
                DB::table('don_hangs')->where('id', $donHang->id)->update([
                    'trang_thai' => donHangModel::TRANG_THAI_DANG_GIAO,
                    'updated_at' => now(),
                ]);

                DB::commit();
                return response()->json([
                    'status'  => true,
                    'message' => 'Đã bác bỏ yêu cầu hủy. Đơn hàng tiếp tục trạng thái đang giao.'
                ], 200);
            }

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Lỗi xử lý duyệt hủy: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 5. KHÁCH HÀNG: Danh sách đơn hàng của tôi
     */
    public function donHangCuaToi()
    {
        try {
            $userId = auth()->id();
            $donHangs = DB::table('don_hangs')
                ->where('users_id', $userId)
                ->orderBy('id', 'desc')
                ->get();

            foreach ($donHangs as $donHang) {
                $donHang->chi_tiet = DB::table('chi_tiet_don_hangs')
                    ->join('san_phams', 'chi_tiet_don_hangs.san_pham_id', '=', 'san_phams.id')
                    ->where('chi_tiet_don_hangs.don_hang_id', $donHang->id)
                    ->select('chi_tiet_don_hangs.*', 'san_phams.ten as ten_san_pham')
                    ->get();
            }

            return response()->json(['status' => true, 'data' => $donHangs], 200);

        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
        }
    }
}