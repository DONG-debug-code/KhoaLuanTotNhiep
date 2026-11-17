<?php

namespace App\Http\Controllers;

use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Quản trị viên cao cấp quản lý tài khoản: kiểm tra, khóa/mở khóa và đổi vai trò nhân viên. */
class AccountController extends Controller
{
    private const TOKEN_TYPES = [
        'khach_hang' => 'App\\Models\\User',
        'nhan_vien' => 'App\\Models\\Admin',
        'chuyen_gia' => 'App\\Models\\ChuyenGia',
    ];

    public function __construct(private readonly AccountService $accounts)
    {
    }

    public function danhSach(): JsonResponse
    {
        $sessions = DB::table('personal_access_tokens')
            ->select('tokenable_type', 'tokenable_id', DB::raw('COUNT(*) as so_phien'), DB::raw('MAX(created_at) as dang_nhap_gan_nhat'))
            ->groupBy('tokenable_type', 'tokenable_id')
            ->get()
            ->keyBy(fn ($row) => $row->tokenable_type . '#' . $row->tokenable_id);

        $present = function (string $type, $rows, callable $role) use ($sessions) {
            return $rows->map(function ($row) use ($type, $sessions, $role) {
                $session = $sessions[self::TOKEN_TYPES[$type] . '#' . $row->id] ?? null;

                return [
                    'loai' => $type,
                    'id' => $row->id,
                    'ho_ten' => $row->ho_ten,
                    'email' => $row->email,
                    'so_dien_thoai' => $row->so_dien_thoai ?? null,
                    'trang_thai' => (int) $row->trang_thai,
                    'created_at' => $row->created_at,
                    'so_phien' => (int) ($session->so_phien ?? 0),
                    'dang_nhap_gan_nhat' => $session->dang_nhap_gan_nhat ?? null,
                ] + $role($row);
            });
        };

        $customers = $present('khach_hang', DB::table('users')->get(), fn () => ['vai_tro' => 'Khách hàng', 'vai_tro_id' => null]);
        $experts = $present('chuyen_gia', DB::table('chuyen_gias')->get(), fn () => ['vai_tro' => 'Chuyên gia', 'vai_tro_id' => null]);
        $staff = $present(
            'nhan_vien',
            DB::table('admins')->leftJoin('vai_tros', 'vai_tros.id', '=', 'admins.vai_tro_id')->get(['admins.*', 'vai_tros.ten as ten_vai_tro']),
            fn ($row) => ['vai_tro' => $row->ten_vai_tro, 'vai_tro_id' => $row->vai_tro_id]
        );

        return response()->json([
            'status' => true,
            'data' => [
                'tai_khoan' => $staff->concat($experts)->concat($customers)->values(),
                'vai_tro_nhan_vien' => DB::table('vai_tros')
                    ->whereNotIn('ma_vai_tro', ['customer', 'vip_customer'])
                    ->orderBy('id')
                    ->get(['id', 'ten', 'ma_vai_tro']),
            ],
        ]);
    }

    public function doiTrangThai(Request $request): JsonResponse
    {
        $data = $request->validate([
            'loai' => 'required|string|in:' . implode(',', AccountService::TYPES),
            'id' => 'required|integer|min:1',
            'khoa' => 'required|boolean',
            'ly_do' => 'nullable|string|max:500',
        ]);

        $error = $this->accounts->setActive($data['loai'], (int) $data['id'], !$data['khoa'], $request->user(), $data['ly_do'] ?? null, $request->ip());
        if ($error) {
            return response()->json(['status' => false, 'message' => $error], 422);
        }

        return response()->json(['status' => true, 'message' => $data['khoa'] ? 'Đã khóa tài khoản và đăng xuất mọi phiên đang hoạt động.' : 'Đã mở khóa tài khoản.']);
    }

    public function doiVaiTro(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required|integer|min:1',
            'vai_tro_id' => 'required|integer|min:1',
        ]);

        $error = $this->accounts->changeStaffRole((int) $data['id'], (int) $data['vai_tro_id'], $request->user(), $request->ip());
        if ($error) {
            return response()->json(['status' => false, 'message' => $error], 422);
        }

        return response()->json(['status' => true, 'message' => 'Đã cập nhật vai trò nhân viên.']);
    }
}
