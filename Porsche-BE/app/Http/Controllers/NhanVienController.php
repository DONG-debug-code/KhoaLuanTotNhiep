<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Exception;

class NhanVienController extends Controller
{
    public function getDataNhanVien(): JsonResponse
    {
        try {
            $data = DB::table('admins')
                ->join('vai_tros', 'admins.vai_tro_id', '=', 'vai_tros.id')
                ->select('admins.id', 'admins.ho_ten', 'admins.email', 'admins.so_dien_thoai', 'admins.trang_thai', 'vai_tros.ten as ten_chuc_vu')
                ->orderBy('admins.id', 'desc')
                ->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi getDataNhanVien: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi tải thông tin nhân viên.'], 500);
        }
    }

    public function searchNhanVien(Request $request): JsonResponse
    {
        try {
            $key = $request->input('value');
            $data = DB::table('admins')
                ->where('ho_ten', 'LIKE', "%{$key}%")
                ->orWhere('email', 'LIKE', "%{$key}%")
                ->get();
            return response()->json(['status' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            Log::error('Lỗi searchNhanVien: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Tìm kiếm nhân sự thất bại.'], 500);
        }
    }

    public function storeNhanVien(Request $request): JsonResponse
    {
        $request->validate([
            'ho_ten'        => 'required|string|max:255',
            'email'         => ['required', 'email', 'unique:admins,email', fn ($a, $v, $fail) => \App\Support\AccountRole::fromEmail($v) === \App\Support\AccountRole::STAFF ?: $fail('Email nhân viên phải thuộc tên miền @staff (ví dụ ten@staff.vn).')],
            'so_dien_thoai' => 'required|string',
            'password'      => 'required|string|min:6',
            'id_chuc_vu'    => 'required|integer'
        ]);
        try {
            DB::table('admins')->insert([
                'ho_ten'        => $request->ho_ten,
                'email'         => $request->email,
                'so_dien_thoai' => $request->so_dien_thoai,
                'password'      => Hash::make($request->password),
                'vai_tro_id'    => $request->id_chuc_vu,
                'trang_thai'    => 1,
                'created_at'    => now(),
                'updated_at'    => now()
            ]);
            return response()->json(['status' => true, 'message' => 'Tạo mới nhân viên thành công.'], 201);
        } catch (Exception $e) {
            Log::error('Lỗi storeNhanVien: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi tạo mới nhân viên.'], 500);
        }
    }

    public function destroyNhanVien(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer|exists:admins,id']);
        try {
            DB::table('admins')->where('id', $request->id)->delete();
            return response()->json(['status' => true, 'message' => 'Xóa tài khoản nhân viên thành công.'], 200);
        } catch (Exception $e) {
            Log::error('Lỗi destroyNhanVien: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi ràng buộc hệ thống dịch vụ.'], 500);
        }
    }

    public function updateNhanVien(Request $request): JsonResponse
    {
        $request->validate([
            'id'         => 'required|integer|exists:admins,id',
            'ho_ten'     => 'required|string|max:255',
            'email'      => ['required', 'email', 'unique:admins,email,' . $request->id, fn ($a, $v, $fail) => \App\Support\AccountRole::fromEmail($v) === \App\Support\AccountRole::STAFF ?: $fail('Email nhân viên phải thuộc tên miền @staff (ví dụ ten@staff.vn).')],
            'id_chuc_vu' => 'required|integer'
        ]);
        try {
            $update = [
                'ho_ten'     => $request->ho_ten,
                'email'      => $request->email,
                'so_dien_thoai' => $request->so_dien_thoai,
                'vai_tro_id' => $request->id_chuc_vu,
                'updated_at' => now()
            ];
            if ($request->filled('password')) {
                $update['password'] = Hash::make($request->password);
            }
            DB::table('admins')->where('id', $request->id)->update($update);
            return response()->json(['status' => true, 'message' => 'Cập nhật thông tin nhân viên thành công.'], 200);
        } catch (Exception $e) {
            Log::error('Lỗi updateNhanVien: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Cập nhật tài khoản thất bại.'], 500);
        }
    }

    public function changeStatusNhanVien(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer|exists:admins,id']);
        try {
            $user = DB::table('admins')->where('id', $request->id)->first();
            $newStatus = $user->trang_thai == 1 ? 0 : 1;
            DB::table('admins')->where('id', $request->id)->update(['trang_thai' => $newStatus, 'updated_at' => now()]);
            return response()->json(['status' => true, 'message' => 'Đổi trạng thái nhân sự thành công.', 'trang_thai' => $newStatus], 200);
        } catch (Exception $e) {
            Log::error('Lỗi changeStatusNhanVien: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Lỗi nghiệp vụ nhân sự.'], 500);
        }
    }
}
