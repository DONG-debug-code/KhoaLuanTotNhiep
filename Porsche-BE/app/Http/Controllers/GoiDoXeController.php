<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Exception;

class GoiDoXeController extends Controller
{
    public function getGoiDoKhuyenMaiActive(): JsonResponse
    {
        $now = now();
        $packages = DB::table('goi_do_xes as packages')
            ->leftJoin('mau_xes as models', 'packages.mau_xe_id', '=', 'models.id')
            ->where('packages.trang_thai', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('packages.ngay_bat_dau')
                    ->orWhere('packages.ngay_bat_dau', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('packages.ngay_ket_thuc')
                    ->orWhere('packages.ngay_ket_thuc', '>=', $now);
            })
            ->select(
                'packages.id',
                'packages.ten_goi',
                'packages.slug',
                'packages.mo_ta',
                'models.ten as ten_mau_xe',
                'packages.tong_gia_goc',
                'packages.gia_goi_khuyen_mai',
                'packages.ngay_ket_thuc'
            )
            ->orderByDesc('packages.id')
            ->get();

        foreach ($packages as $package) {
            $package->danh_sach_phu_tung = DB::table('chi_tiet_goi_do_xes as details')
                ->join('san_phams as products', 'details.san_pham_id', '=', 'products.id')
                ->leftJoin('anh_san_phams as images', function ($join) {
                    $join->on('products.id', '=', 'images.san_pham_id')
                        ->where('images.la_anh_chinh', true);
                })
                ->where('details.goi_do_xe_id', $package->id)
                ->select(
                    'products.id as san_pham_id',
                    'products.ten as ten_san_pham',
                    'images.duong_dan as hinh_anh',
                    'details.so_luong',
                    'details.la_qua_tang',
                    'details.ghi_chu_qua_tang'
                )
                ->get();
        }

        return response()->json(['status' => true, 'data' => $packages]);
    }

    public function storeGoiDoXe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ten_goi' => 'required|string|max:255',
            'mo_ta' => 'nullable|string',
            'mau_xe_id' => 'nullable|integer|exists:mau_xes,id',
            'gia_goi_khuyen_mai' => 'required|numeric|min:0',
            'ngay_bat_dau' => 'nullable|date',
            'ngay_ket_thuc' => 'nullable|date|after_or_equal:ngay_bat_dau',
            'danh_sach_sp' => 'required|array|min:1',
            'danh_sach_sp.*.san_pham_id' => 'required|integer|distinct|exists:san_phams,id',
            'danh_sach_sp.*.so_luong' => 'required|integer|min:1',
            'danh_sach_sp.*.la_qua_tang' => 'required|boolean',
            'danh_sach_sp.*.ghi_chu_qua_tang' => 'nullable|string|max:255',
        ]);

        try {
            $packageId = DB::transaction(function () use ($data) {
                $products = DB::table('san_phams')
                    ->whereIn('id', array_column($data['danh_sach_sp'], 'san_pham_id'))
                    ->get()
                    ->keyBy('id');

                $regularPrice = 0;
                foreach ($data['danh_sach_sp'] as $item) {
                    if (!$item['la_qua_tang']) {
                        $regularPrice += (float) $products[$item['san_pham_id']]->gia * $item['so_luong'];
                    }
                }

                $id = DB::table('goi_do_xes')->insertGetId([
                    'ten_goi' => $data['ten_goi'],
                    'slug' => Str::slug($data['ten_goi']) . '-' . Str::lower(Str::random(8)),
                    'mo_ta' => $data['mo_ta'] ?? null,
                    'mau_xe_id' => $data['mau_xe_id'] ?? null,
                    'tong_gia_goc' => $regularPrice,
                    'gia_goi_khuyen_mai' => $data['gia_goi_khuyen_mai'],
                    'ngay_bat_dau' => $data['ngay_bat_dau'] ?? null,
                    'ngay_ket_thuc' => $data['ngay_ket_thuc'] ?? null,
                    'trang_thai' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($data['danh_sach_sp'] as $item) {
                    $product = $products[$item['san_pham_id']];
                    DB::table('chi_tiet_goi_do_xes')->insert([
                        'goi_do_xe_id' => $id,
                        'san_pham_id' => $product->id,
                        'so_luong' => $item['so_luong'],
                        'don_gia_khuyen_nghi' => $item['la_qua_tang'] ? 0 : $product->gia,
                        'la_qua_tang' => $item['la_qua_tang'],
                        'ghi_chu_qua_tang' => $item['ghi_chu_qua_tang'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return $id;
            });

            return response()->json([
                'status' => true,
                'message' => 'Tạo gói độ xe khuyến mãi thành công.',
                'data' => ['id' => $packageId],
            ], 201);
        } catch (Exception $e) {
            Log::error('Lỗi tạo gói độ xe.', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Không thể tạo gói độ xe.'], 500);
        }
    }

    public function getDataKhuVuc(): JsonResponse
    {
        try {
            $data = DB::table('khu_vuc_workshops')->orderBy('id', 'desc')->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getDataKhuVuc: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi tải danh sách khu vực.'], 500);
        }
    }

    public function searchKhuVuc(Request $request): JsonResponse
    {
        try {
            $key = $request->input('value');
            $data = DB::table('khu_vuc_workshops')->where('ten_khu_vuc', 'LIKE', "%{$key}%")->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi searchKhuVuc: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Tìm kiếm khu vực thất bại.'], 500);
        }
    }

    public function storeKhuVuc(Request $request): JsonResponse
    {
        $request->validate(['ten_khu_vuc' => 'required|string|unique:khu_vuc_workshops,ten_khu_vuc']);
        try {
            DB::table('khu_vuc_workshops')->insert([
                'ten_khu_vuc' => $request->ten_khu_vuc,
                'trang_thai'  => 1,
                'created_at'  => now(),
                'updated_at'  => now()
            ]);
            return response()->json(['status' => true, 'message' => 'Thêm khu vực workshop thành công.'], 201);
        } catch (Exception $e) {
            Log::error('Lỗi storeKhuVuc: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi thêm khu vực.'], 500);
        }
    }

    public function destroyKhuVuc(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer|exists:khu_vuc_workshops,id']);
        try {
            DB::table('khu_vuc_workshops')->where('id', $request->id)->delete();
            return response()->json(['status' => true, 'message' => 'Xóa khu vực thành công.'], 200);
        } catch (Exception $e) {
            Log::error('Lỗi destroyKhuVuc: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Không thể xóa, khu vực đang vận hành.'], 500);
        }
    }

    public function updateKhuVuc(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer|exists:khu_vuc_workshops,id',
            'ten_khu_vuc' => 'required|string|unique:khu_vuc_workshops,ten_khu_vuc,' . $request->id
        ]);
        try {
            DB::table('khu_vuc_workshops')->where('id', $request->id)->update([
                'ten_khu_vuc' => $request->ten_khu_vuc,
                'updated_at'  => now()
            ]);
            return response()->json(['status' => true, 'message' => 'Cập nhật khu vực thành công.'], 200);
        } catch (Exception $e) {
            Log::error('Lỗi updateKhuVuc: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Cập nhật thất bại.'], 500);
        }
    }

    public function changeStatusKhuVuc(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer|exists:khu_vuc_workshops,id']);
        try {
            $current = DB::table('khu_vuc_workshops')->where('id', $request->id)->first();
            $newStatus = $current->trang_thai == 1 ? 0 : 1;
            DB::table('khu_vuc_workshops')->where('id', $request->id)->update(['trang_thai' => $newStatus, 'updated_at' => now()]);
            return response()->json(['status' => true, 'message' => 'Cập nhật trạng thái bay độ xe thành công.', 'trang_thai' => $newStatus], 200);
        } catch (Exception $e) {
            Log::error('Lỗi changeStatusKhuVuc: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi chuyển đổi trạng thái.'], 500);
        }
    }
}
