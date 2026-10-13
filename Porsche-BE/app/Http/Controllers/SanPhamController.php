<?php

namespace App\Http\Controllers;

use App\Models\SanPham;
use App\Models\NhatKyKho;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class SanPhamController extends Controller
{
    /**
     * 1. [CƠ BẢN + NÂNG CAO] Lấy danh sách xe / sản phẩm có Bộ lọc & Tìm kiếm & Phân trang
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'tu_khoa' => 'sometimes|string|max:255',
            'danh_muc_id' => 'sometimes|integer',
            'mau_xe_id' => 'sometimes|integer',
            'gia_tu' => 'sometimes|numeric|min:0',
            'gia_den' => 'sometimes|numeric|min:0',
            'con_hang' => 'sometimes|boolean',
            'sap_xep' => 'sometimes|in:mac_dinh,gia_tang,gia_giam,moi_nhat',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = SanPham::with(['mauXe', 'mauSacs', 'anhSanPhams']);
        if (Schema::hasColumn('san_phams', 'trang_thai')) {
            $query->where('trang_thai', 1);
        }

        if (!empty($filters['tu_khoa'])) {
            $tuKhoa = $filters['tu_khoa'];
            $query->where(function ($q) use ($tuKhoa) {
                $q->where('ten', 'LIKE', "%{$tuKhoa}%");
                foreach (['ten_san_pham', 'ma_san_pham'] as $column) {
                    if (Schema::hasColumn('san_phams', $column)) {
                        $q->orWhere($column, 'LIKE', "%{$tuKhoa}%");
                    }
                }
            });
        }

        if (isset($filters['danh_muc_id']) && Schema::hasColumn('san_phams', 'danh_muc_id')) {
            $query->where('danh_muc_id', $filters['danh_muc_id']);
        }

        if (isset($filters['mau_xe_id'])) {
            $query->where('mau_xe_id', $filters['mau_xe_id']);
        }

        if (isset($filters['gia_tu'])) {
            $query->where('gia', '>=', $filters['gia_tu']);
        }
        if (isset($filters['gia_den'])) {
            $query->where('gia', '<=', $filters['gia_den']);
        }

        if (!empty($filters['con_hang'])) {
            $query->where('so_luong', '>', 0);
        }

        $sapXep = $filters['sap_xep'] ?? 'moi_nhat';
        switch ($sapXep) {
            case 'gia_tang':
                $query->orderBy('gia', 'asc');
                break;
            case 'gia_giam':
                $query->orderBy('gia', 'desc');
                break;
            default:
                $query->orderBy('id', 'desc');
                break;
        }

        $danhSachXe = $query->paginate($filters['per_page'] ?? 12);

        return response()->json([
            'status'  => true,
            'message' => 'Lấy danh sách xe/sản phẩm thành công',
            'data'    => $danhSachXe
        ], 200);
    }

    /**
     * 2. [CƠ BẢN] Xem chi tiết 1 chiếc xe & gợi ý Xe liên quan
     */
    public function show($id)
    {
        $xe = SanPham::with(['mauXe', 'mauSacs', 'anhSanPhams'])->find($id);

        if (!$xe) {
            return response()->json([
                'status'  => false,
                'message' => 'Không tìm thấy thông tin sản phẩm/xe'
            ], 404);
        }

        // 💡 Gợi ý 4 xe liên quan cùng Dòng xe hoặc cùng Phân khúc giá
        $xeLienQuanQuery = SanPham::with(['mauXe', 'anhSanPhams'])
            ->where('mau_xe_id', $xe->mau_xe_id)
            ->where('id', '!=', $xe->id);
        if (Schema::hasColumn('san_phams', 'trang_thai')) {
            $xeLienQuanQuery->where('trang_thai', 1);
        }
        $xeLienQuan = $xeLienQuanQuery
            ->limit(4)
            ->get();

        return response()->json([
            'status'       => true,
            'message'      => 'Lấy chi tiết xe thành công',
            'data'         => $xe,
            'xe_lien_quan' => $xeLienQuan
        ], 200);
    }

    /**
     * 3. [NGHIỆP VỤ ADMIN] Thêm xe/sản phẩm mới + Ghi Nhật ký Kho
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ten_san_pham' => 'required|string|max:255',
            'danh_muc_id'  => 'required|integer',
            'mau_xe_id'    => 'required|integer',
            'gia_ban'      => 'required|numeric|min:0',
            'so_luong_ton' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Tạo sản phẩm
            $sanPham = SanPham::create([
                'ten_san_pham' => $request->ten_san_pham,
                'ma_san_pham'  => $request->ma_san_pham ?? 'POR-' . strtoupper(uniqid()),
                'danh_muc_id'  => $request->danh_muc_id,
                'hang_xe_id'   => $request->hang_xe_id ?? 1, // Porsche
                'mau_xe_id'    => $request->mau_xe_id,
                'gia_ban'      => $request->gia_ban,
                'gia_khuyen_mai' => $request->gia_khuyen_mai ?? 0,
                'so_luong_ton' => $request->so_luong_ton,
                'mo_ta_ngan'   => $request->mo_ta_ngan,
                'mo_ta_chi_tiet' => $request->mo_ta_chi_tiet,
                'trang_thai'   => 1,
            ]);

            // 📜 GHI NHẬT KÝ KHO (Audit Log)
            NhatKyKho::create([
                'san_pham_id'   => $sanPham->id,
                'loai_thay_doi' => 'NHAP_KHO_MOI',
                'so_luong_cu'   => 0,
                'so_luong_moi'  => $sanPham->so_luong_ton,
                'ghi_chu'       => 'Nhập mới sản phẩm/xe vào hệ thống',
                'user_id'       => Auth::id() ?? 1,
            ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Thêm mới xe/sản phẩm thành công',
'data'    => $sanPham
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi khi thêm sản phẩm: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 4. [NGHIỆP VỤ ADMIN] Cập nhật số lượng kho (Cập nhật Kho Realtime)
     */
    public function updateStock(Request $request, $id)
    {
        $request->validate([
            'so_luong_moi' => 'required|integer|min:0',
            'ly_do'        => 'nullable|string'
        ]);

        $sanPham = SanPham::find($id);
        if (!$sanPham) {
            return response()->json(['status' => false, 'message' => 'Sản phẩm không tồn tại'], 404);
        }

        $soLuongCu = $sanPham->so_luong_ton;
        $soLuongMoi = $request->so_luong_moi;

        DB::beginTransaction();
        try {
            $sanPham->so_luong_ton = $soLuongMoi;
            $sanPham->save();

            // 📜 Ghi nhật ký biến động kho
            NhatKyKho::create([
                'san_pham_id'   => $sanPham->id,
                'loai_thay_doi' => $soLuongMoi > $soLuongCu ? 'NHAP_KHO' : 'XUAT_KHO',
                'so_luong_cu'   => $soLuongCu,
                'so_luong_moi'  => $soLuongMoi,
                'ghi_chu'       => $request->ly_do ?? 'Điều chỉnh số lượng kho thủ công',
                'user_id'       => Auth::id() ?? 1,
            ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Cập nhật kho thành công',
                'so_luong_cu' => $soLuongCu,
                'so_luong_moi' => $soLuongMoi
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}