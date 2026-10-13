<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ChucVuController extends Controller
{
    public function getData()
    {
        try {
            $data = DB::table('chuc_vus')->orderBy('id', 'desc')->get();
            return response()->json([
                'status'  => true,
                'message' => 'Lấy danh sách chức vụ thành công.',
                'data'    => $data
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'ten_chuc_vu' => 'required|string|max:255',
            'ma_chuc_vu'  => 'required|string|max:100|unique:chuc_vus,ma_chuc_vu',
            'trang_thai'  => 'nullable|in:0,1',
        ]);

        try {
            DB::table('chuc_vus')->insert([
                'ten_chuc_vu' => $request->ten_chuc_vu,
                'ma_chuc_vu'  => $request->ma_chuc_vu,
                'trang_thai'  => $request->input('trang_thai', 1),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Thêm mới chức vụ thành công.'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Không thể thêm chức vụ: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateChucVu(Request $request)
    {
        $request->validate([
            'id'          => 'required|integer|exists:chuc_vus,id',
            'ten_chuc_vu' => 'required|string|max:255',
            'ma_chuc_vu'  => 'required|string|max:100|unique:chuc_vus,ma_chuc_vu,' . $request->id,
            'trang_thai'  => 'required|in:0,1',
        ]);

        try {
            DB::table('chuc_vus')->where('id', $request->id)->update([
                'ten_chuc_vu' => $request->ten_chuc_vu,
                'ma_chuc_vu'  => $request->ma_chuc_vu,
                'trang_thai'  => $request->trang_thai,
                'updated_at'  => now(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Cập nhật thông tin chức vụ thành công.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Không thể cập nhật chức vụ: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyChucVu(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:chuc_vus,id'
        ]);

        try {
            DB::table('chuc_vus')->where('id', $request->id)->delete();
            return response()->json([
                'status'  => true,
                'message' => 'Xóa chức vụ thành công.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Không thể xóa chức vụ do ràng buộc dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }

    public function changeStatusChucVu(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:chuc_vus,id'
        ]);

        try {
            $chucVu = DB::table('chuc_vus')->where('id', $request->id)->first();
            $newStatus = ($chucVu->trang_thai == 1) ? 0 : 1;

            DB::table('chuc_vus')->where('id', $request->id)->update([
                'trang_thai' => $newStatus,
                'updated_at' => now(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Thay đổi trạng thái hoạt động chức vụ thành công.',
                'trang_thai_moi' => $newStatus
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Lỗi thay đổi trạng thái: ' . $e->getMessage()
            ], 500);
        }
    }
}
