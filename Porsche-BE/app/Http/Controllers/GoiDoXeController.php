<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Exception;

class GoiDoXeController extends Controller
{
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
