<?php

namespace App\Http\Controllers;

use App\Services\VehicleHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Quản lý xe trong garage của khách hàng (thêm / sửa / xóa / danh sách).
 * Khách chỉ thao tác được trên xe của chính mình.
 */
class XeKhachHangController extends Controller
{
    public function __construct(private readonly VehicleHistoryService $history)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $rows = DB::table('xe_khach_hangs')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($xe) => self::present($xe));

        return response()->json(['status' => true, 'data' => $rows], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $data = $this->validated($request, $userId);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        try {
            $id = DB::table('xe_khach_hangs')->insertGetId($data + [
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $created = DB::table('xe_khach_hangs')->find($id);
            $this->history->recordVehicleCreated($created, $userId);

            return response()->json([
                'status' => true,
                'message' => 'Đã thêm xe vào garage.',
                'data' => self::present($created),
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Lỗi thêm xe garage: ' . $e->getMessage(), ['user_id' => $userId]);
            return response()->json(['status' => false, 'message' => 'Không thể thêm xe. Vui lòng thử lại.'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;
        $xe = DB::table('xe_khach_hangs')->where('id', $id)->where('user_id', $userId)->first();
        if (!$xe) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy xe.'], 404);
        }

        $data = $this->validated($request, $userId, $id);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        try {
            DB::table('xe_khach_hangs')->where('id', $id)->update($data + ['updated_at' => now()]);
            $updated = DB::table('xe_khach_hangs')->find($id);
            $this->history->recordVehicleUpdated($xe, $updated, $userId);

            return response()->json([
                'status' => true,
                'message' => 'Đã cập nhật thông tin xe.',
                'data' => self::present($updated),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Lỗi sửa xe garage: ' . $e->getMessage(), ['user_id' => $userId, 'xe_id' => $id]);
            return response()->json(['status' => false, 'message' => 'Không thể cập nhật xe. Vui lòng thử lại.'], 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $deleted = DB::table('xe_khach_hangs')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->delete();

        if (!$deleted) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy xe.'], 404);
        }

        DB::table('lich_su_xe_khach_hangs')->where('xe_khach_hang_id', $id)->delete();

        return response()->json(['status' => true, 'message' => 'Đã xóa xe khỏi garage.'], 200);
    }

    /** Khách xem lịch sử thay đổi/nâng cấp của xe mình. */
    public function lichSu(Request $request, int $id): JsonResponse
    {
        $xe = DB::table('xe_khach_hangs')->where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$xe) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy xe.'], 404);
        }

        return response()->json(['status' => true, 'data' => ['xe' => self::present($xe), 'lich_su' => $this->historyRows($id)]]);
    }

    /** Nhân viên: danh sách xe của mọi khách kèm số bản ghi lịch sử. */
    public function adminDanhSach(): JsonResponse
    {
        $rows = DB::table('xe_khach_hangs as x')
            ->join('users as u', 'u.id', '=', 'x.user_id')
            ->leftJoin('lich_su_xe_khach_hangs as h', 'h.xe_khach_hang_id', '=', 'x.id')
            ->groupBy('x.id', 'x.ten_xe', 'x.bien_so_xe', 'x.dong_xe', 'x.nam_san_xuat', 'x.mau_sac', 'x.so_km', 'x.user_id', 'u.ho_ten', 'u.so_dien_thoai')
            ->orderByDesc(DB::raw('MAX(h.id)'))
            ->orderByDesc('x.id')
            ->get([
                'x.id', 'x.ten_xe', 'x.bien_so_xe', 'x.dong_xe', 'x.nam_san_xuat', 'x.mau_sac', 'x.so_km', 'x.user_id',
                'u.ho_ten as chu_xe', 'u.so_dien_thoai',
                DB::raw('COUNT(h.id) as so_ban_ghi'), DB::raw('MAX(h.ngay_thuc_hien) as lan_cuoi'),
            ]);

        return response()->json(['status' => true, 'data' => $rows]);
    }

    public function adminLichSu(int $id): JsonResponse
    {
        $xe = DB::table('xe_khach_hangs')->where('id', $id)->first();
        if (!$xe) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy xe.'], 404);
        }

        return response()->json(['status' => true, 'data' => ['xe' => self::present($xe), 'lich_su' => $this->historyRows($id)]]);
    }

    /** Nhân viên ghi nhận nâng cấp/thay đổi/bảo dưỡng thực hiện tại showroom. */
    public function adminThemLichSu(Request $request): JsonResponse
    {
        $data = $request->validate([
            'xe_khach_hang_id' => 'required|integer|exists:xe_khach_hangs,id',
            'loai' => 'required|string|in:nang_cap,thay_doi,bao_duong,ghi_nhan',
            'tieu_de' => 'required|string|max:200',
            'mo_ta' => 'nullable|string|max:2000',
            'ngay_thuc_hien' => 'nullable|date|before_or_equal:today',
            'thay_doi' => 'nullable|array|max:20',
            'thay_doi.*.truong' => 'required|string|max:60',
            'thay_doi.*.truoc' => 'nullable|string|max:255',
            'thay_doi.*.sau' => 'nullable|string|max:255',
        ]);

        $xe = DB::table('xe_khach_hangs')->where('id', $data['xe_khach_hang_id'])->first();
        $staff = $request->user();
        $id = $this->history->record([
            'xe_khach_hang_id' => $xe->id,
            'user_id' => $xe->user_id,
            'loai' => $data['loai'],
            'tieu_de' => $data['tieu_de'],
            'mo_ta' => $data['mo_ta'] ?? null,
            'thay_doi' => $data['thay_doi'] ?? null,
            'ngay_thuc_hien' => $data['ngay_thuc_hien'] ?? 'now',
            'nguon' => 'nhan_vien',
            'nguoi_tao_id' => $staff?->id,
            'nguoi_tao_ten' => $staff?->ho_ten,
        ]);

        return response()->json(['status' => true, 'message' => 'Đã ghi nhận vào lịch sử xe.', 'data' => ['id' => $id]], 201);
    }

    private function historyRows(int $vehicleId)
    {
        return DB::table('lich_su_xe_khach_hangs')
            ->where('xe_khach_hang_id', $vehicleId)
            ->orderByDesc('ngay_thuc_hien')
            ->orderByDesc('id')
            ->get()
            ->map(function ($row) {
                $row->thay_doi = $row->thay_doi ? json_decode($row->thay_doi, true) : [];

                return $row;
            });
    }

    /**
     * Dạng dữ liệu trả về cho FE (giữ thêm thong_tin_xe/bien_so_xe tương thích màn hình đặt lịch).
     */
    public static function present(object $xe): array
    {
        return [
            'id' => $xe->id,
            'ten_xe' => $xe->ten_xe,
            'bien_so_xe' => $xe->bien_so_xe,
            'thong_tin_xe' => $xe->ten_xe,
            'dong_xe' => $xe->dong_xe,
            'nam_san_xuat' => $xe->nam_san_xuat,
            'mau_sac' => $xe->mau_sac,
            'so_khung' => $xe->so_khung,
            'so_km' => $xe->so_km,
            'ghi_chu' => $xe->ghi_chu,
            'created_at' => $xe->created_at,
        ];
    }

    private function validated(Request $request, int $userId, ?int $ignoreId = null): array|JsonResponse
    {
        // Chuẩn hóa biển số trước khi kiểm tra trùng
        if (is_string($request->input('bien_so_xe'))) {
            $request->merge(['bien_so_xe' => mb_strtoupper(trim($request->input('bien_so_xe')))]);
        }

        $validator = Validator::make($request->all(), [
            'ten_xe' => 'required|string|max:150',
            'bien_so_xe' => [
                'required', 'string', 'max:20',
                Rule::unique('xe_khach_hangs', 'bien_so_xe')
                    ->where('user_id', $userId)
                    ->ignore($ignoreId),
            ],
            'dong_xe' => 'nullable|string|max:60',
            'nam_san_xuat' => 'nullable|integer|between:1950,' . (now()->year + 1),
            'mau_sac' => 'nullable|string|max:50',
            'so_khung' => 'nullable|string|max:30',
            'so_km' => 'nullable|integer|min:0|max:2000000',
            'ghi_chu' => 'nullable|string|max:500',
        ], [
            'ten_xe.required' => 'Vui lòng nhập tên/mẫu xe.',
            'bien_so_xe.required' => 'Vui lòng nhập biển số xe.',
            'bien_so_xe.unique' => 'Biển số này đã có trong garage của bạn.',
            'nam_san_xuat.between' => 'Năm sản xuất không hợp lệ.',
            'so_km.max' => 'Số km không hợp lệ.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $v = $validator->validated();

        return [
            'ten_xe' => trim($v['ten_xe']),
            'bien_so_xe' => mb_strtoupper(trim($v['bien_so_xe'])),
            'dong_xe' => $this->blankToNull($v['dong_xe'] ?? null),
            'nam_san_xuat' => $v['nam_san_xuat'] ?? null,
            'mau_sac' => $this->blankToNull($v['mau_sac'] ?? null),
            'so_khung' => $this->blankToNull(isset($v['so_khung']) ? mb_strtoupper($v['so_khung']) : null),
            'so_km' => $v['so_km'] ?? null,
            'ghi_chu' => $this->blankToNull($v['ghi_chu'] ?? null),
        ];
    }

    private function blankToNull(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }
}
