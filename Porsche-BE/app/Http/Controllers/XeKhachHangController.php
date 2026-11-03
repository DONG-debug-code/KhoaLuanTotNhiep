<?php

namespace App\Http\Controllers;

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

            return response()->json([
                'status' => true,
                'message' => 'Đã thêm xe vào garage.',
                'data' => self::present(DB::table('xe_khach_hangs')->find($id)),
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

            return response()->json([
                'status' => true,
                'message' => 'Đã cập nhật thông tin xe.',
                'data' => self::present(DB::table('xe_khach_hangs')->find($id)),
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

        return response()->json(['status' => true, 'message' => 'Đã xóa xe khỏi garage.'], 200);
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
