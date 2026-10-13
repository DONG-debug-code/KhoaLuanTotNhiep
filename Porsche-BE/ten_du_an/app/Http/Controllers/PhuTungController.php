<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Exception;

class PhuTungController extends Controller
{
    /**
     * Lấy danh sách toàn bộ phụ tùng
     */
    public function getDataPhuTung(): JsonResponse
    {
        try {
            $data = DB::table('phu_tungs')->orderBy('id', 'desc')->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getDataPhuTung: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi tải danh sách phụ tùng.'], 500);
        }
    }

    /**
     * Tìm kiếm phụ tùng theo tên
     */
    public function searchPhuTung(Request $request): JsonResponse
    {
        try {
            $key = $request->input('value');
            $data = DB::table('phu_tungs')->where('ten', 'LIKE', "%{$key}%")->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi searchPhuTung: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Tìm kiếm phụ tùng thất bại.'], 500);
        }
    }

    /**
     * Thêm mới phụ tùng vào kho
     */
    public function storePhuTung(Request $request): JsonResponse
    {
        $request->validate([
            'ten'      => 'required|string|unique:phu_tungs,ten',
            'gia'      => 'required|numeric|min:0',
            'so_luong' => 'required|integer|min:0',
            'loai'     => 'required|string',
            'mo_ta'    => 'nullable|string',
            'anh'      => 'nullable|string',
        ]);

        try {
            DB::table('phu_tungs')->insert([
                'ten'        => $request->ten,
                'gia'        => $request->gia,
                'so_luong'   => $request->so_luong,
                'loai'       => $request->loai,
                'mo_ta'      => $request->mo_ta,
                'anh'        => $request->anh ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json(['status' => true, 'message' => 'Thêm phụ tùng thành công.'], 201);
        } catch (Exception $e) {
            Log::error('Lỗi storePhuTung: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi lưu trữ dữ liệu phụ tùng.'], 500);
        }
    }

    /**
     * Cập nhật thông tin phụ tùng (bao gồm số lượng tồn kho)
     */
    public function updatePhuTung(Request $request): JsonResponse
    {
        $request->validate([
            'id'       => 'required|integer|exists:phu_tungs,id',
            'ten'      => 'required|string|unique:phu_tungs,ten,' . $request->id,
            'gia'      => 'required|numeric|min:0',
            'so_luong' => 'required|integer|min:0',
            'loai'     => 'required|string',
            'mo_ta'    => 'nullable|string',
            'anh'      => 'nullable|string',
        ]);

        try {
            DB::table('phu_tungs')->where('id', $request->id)->update([
                'ten'        => $request->ten,
                'gia'        => $request->gia,
                'so_luong'   => $request->so_luong,
                'loai'       => $request->loai,
                'mo_ta'      => $request->mo_ta,
                'anh'        => $request->anh ?? null,
                'updated_at' => now()
            ]);

            return response()->json(['status' => true, 'message' => 'Cập nhật phụ tùng thành công.'], 200);
        } catch (Exception $e) {
            Log::error('Lỗi updatePhuTung: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Cập nhật phụ tùng thất bại.'], 500);
        }
    }

    /**
     * Xóa phụ tùng khỏi CSDL
     */
    public function destroyPhuTung(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer|exists:phu_tungs,id']);

        try {
            DB::table('phu_tungs')->where('id', $request->id)->delete();
            return response()->json(['status' => true, 'message' => 'Xóa phụ tùng thành công.'], 200);
        } catch (Exception $e) {
            Log::error('Lỗi destroyPhuTung: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Không thể xóa phụ tùng do có dữ liệu liên quan.'], 500);
        }
    }
}