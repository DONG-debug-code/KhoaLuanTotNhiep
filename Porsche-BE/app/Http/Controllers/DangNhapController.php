<?php

namespace App\Http\Controllers;

use App\Support\AccountRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DangNhapController extends Controller
{
    private const ADMIN_ROLES = ['super_admin', 'executive'];

    public function login(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email', 'password' => 'required|string']);

        $role = AccountRole::fromEmail($request->email);

        if (in_array($role, [AccountRole::ADMIN, AccountRole::STAFF], true)) {
            $roleCode = DB::table('admins')
                ->leftJoin('vai_tros', 'vai_tros.id', '=', 'admins.vai_tro_id')
                ->where('admins.email', $request->email)
                ->value('vai_tros.ma_vai_tro');
            $isAdminRole = in_array($roleCode, self::ADMIN_ROLES, true);
            if (($role === AccountRole::ADMIN) !== $isAdminRole && $roleCode !== null) {
                return response()->json(['status' => false, 'message' => 'Thông tin đăng nhập không chính xác.'], 401);
            }
        }

        $response = match ($role) {
            AccountRole::ADMIN, AccountRole::STAFF => app(AdminController::class)->loginAdmin($request),
            AccountRole::EXPERT => app(ChuyenGiaController::class)->loginChuyenGia($request),
            default => app(KhachHangController::class)->loginKhachHang($request),
        };

        $payload = $response->getData(true);
        if (!empty($payload['status'])) {
            // Nhân viên dùng chung khu vực quản trị với admin, phân biệt bằng quyền theo chức vụ.
            $payload['vai_tro'] = $role === AccountRole::STAFF ? AccountRole::ADMIN : $role;
            $payload['loai_tai_khoan'] = $role;
            $response->setData($payload);
        }

        return $response;
    }
}
