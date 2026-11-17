<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Đánh giá sản phẩm (xe/gói độ, phụ tùng) và chuyên gia.
 * Ai cũng xem được; chỉ khách đã mua sản phẩm hoặc đã hoàn thành lịch với chuyên gia mới được đánh giá.
 */
class ReviewController extends Controller
{
    private const PAGE_SIZE = 10;

    /** Mô tả bảng đánh giá theo loại đối tượng được đánh giá. */
    private const TARGETS = [
        'san_pham' => ['table' => 'danh_gia_san_phams', 'target' => 'san_pham_id', 'user' => 'user_id'],
        'phu_tung' => ['table' => 'danh_gia_phu_tungs', 'target' => 'phu_tung_id', 'user' => 'user_id'],
        'chuyen_gia' => ['table' => 'danh_gia_chuyen_gias', 'target' => 'id_chuyen_gia', 'user' => 'id_khach_hang'],
    ];

    /** Công khai: điểm trung bình, phân bố sao và danh sách đánh giá. */
    public function danhSach(string $loai, int $id): JsonResponse
    {
        $target = self::TARGETS[$loai] ?? null;
        if (!$target) {
            return response()->json(['status' => false, 'message' => 'Loại đánh giá không hợp lệ.'], 404);
        }

        $base = DB::table($target['table'])->where($target['target'], $id);
        $distribution = (clone $base)->selectRaw('so_sao, COUNT(*) as so_luong')->groupBy('so_sao')->pluck('so_luong', 'so_sao');
        $count = (int) $distribution->sum();
        $average = $count ? round((float) (clone $base)->avg('so_sao'), 1) : null;

        $query = (clone $base)->leftJoin('users as u', 'u.id', '=', $target['table'] . '.' . $target['user'])
            ->orderByDesc($target['table'] . '.id')
            ->select($target['table'] . '.id', $target['table'] . '.so_sao', $target['table'] . '.noi_dung', $target['table'] . '.created_at', 'u.ho_ten');
        if ($loai === 'chuyen_gia') {
            $query->addSelect($target['table'] . '.ten_khach_hang');
        }
        $page = $query->paginate(self::PAGE_SIZE);

        $items = collect($page->items())->map(fn ($row) => [
            'id' => $row->id,
            'so_sao' => (int) $row->so_sao,
            'noi_dung' => $row->noi_dung,
            'ten_khach_hang' => $this->maskName($row->ho_ten ?? $row->ten_khach_hang ?? null),
            'created_at' => $row->created_at,
        ]);

        return response()->json([
            'status' => true,
            'data' => [
                'trung_binh' => $average,
                'so_luong' => $count,
                'phan_bo' => collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($star) => [$star => (int) ($distribution[$star] ?? 0)]),
                'danh_gia' => $items,
                'trang_hien_tai' => $page->currentPage(),
                'so_trang' => $page->lastPage(),
            ],
        ]);
    }

    /** Khách đăng nhập: có được đánh giá đối tượng này không và đánh giá hiện tại của mình. */
    public function dieuKien(Request $request): JsonResponse
    {
        $data = $request->validate([
            'loai' => 'required|string|in:san_pham,phu_tung,chuyen_gia',
            'id' => 'required|integer|min:1',
        ]);
        $userId = (int) $request->user()->id;

        if ($data['loai'] === 'chuyen_gia') {
            $appointment = $this->reviewableAppointment($userId, (int) $data['id']);

            return response()->json(['status' => true, 'data' => [
                'co_the_danh_gia' => $appointment !== null,
                'ly_do' => $appointment ? null : 'Bạn cần hoàn thành một lịch hẹn với chuyên gia này để đánh giá.',
                'danh_gia_cua_toi' => null,
            ]]);
        }

        $mine = $this->myReview($data['loai'], $userId, (int) $data['id']);
        $purchased = $this->hasPurchased($data['loai'], $userId, (int) $data['id']);

        return response()->json(['status' => true, 'data' => [
            'co_the_danh_gia' => $purchased,
            'ly_do' => $purchased ? null : 'Chỉ khách hàng đã nhận sản phẩm này mới có thể đánh giá.',
            'danh_gia_cua_toi' => $mine ? ['so_sao' => (int) $mine->so_sao, 'noi_dung' => $mine->noi_dung] : null,
        ]]);
    }

    public function guiSanPham(Request $request): JsonResponse
    {
        $data = $request->validate([
            'loai' => 'required|string|in:san_pham,phu_tung',
            'id' => 'required|integer|min:1',
            'so_sao' => 'required|integer|between:1,5',
            'noi_dung' => 'nullable|string|max:1000',
        ]);
        $userId = (int) $request->user()->id;
        $target = self::TARGETS[$data['loai']];

        if (!DB::table($data['loai'] === 'san_pham' ? 'san_phams' : 'phu_tungs')->where('id', $data['id'])->exists()) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy sản phẩm.'], 404);
        }
        if (!$this->hasPurchased($data['loai'], $userId, (int) $data['id'])) {
            return response()->json(['status' => false, 'message' => 'Chỉ khách hàng đã nhận sản phẩm này mới có thể đánh giá.'], 403);
        }

        $values = ['so_sao' => $data['so_sao'], 'noi_dung' => $this->clean($data['noi_dung'] ?? null), 'updated_at' => now()];
        $existing = $this->myReview($data['loai'], $userId, (int) $data['id']);
        if ($existing) {
            DB::table($target['table'])->where('id', $existing->id)->update($values);
        } else {
            DB::table($target['table'])->insert($values + [$target['user'] => $userId, $target['target'] => $data['id'], 'created_at' => now()]);
        }

        if ($data['loai'] === 'phu_tung') {
            $average = DB::table($target['table'])->where($target['target'], $data['id'])->avg('so_sao');
            DB::table('phu_tungs')->where('id', $data['id'])->update(['danh_gia' => number_format((float) $average, 1, '.', '')]);
        }

        return response()->json(['status' => true, 'message' => $existing ? 'Đã cập nhật đánh giá của bạn.' : 'Cảm ơn bạn đã đánh giá sản phẩm.'], $existing ? 200 : 201);
    }

    public function guiChuyenGia(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_chuyen_gia' => 'required|integer|exists:chuyen_gias,id',
            'so_sao' => 'required|integer|between:1,5',
            'noi_dung' => 'nullable|string|max:1000',
        ]);
        $user = $request->user();

        $appointment = $this->reviewableAppointment((int) $user->id, (int) $data['id_chuyen_gia']);
        if (!$appointment) {
            return response()->json(['status' => false, 'message' => 'Bạn cần hoàn thành một lịch hẹn với chuyên gia này (chưa được đánh giá) để gửi đánh giá.'], 403);
        }

        DB::table('danh_gia_chuyen_gias')->insert([
            'id_chuyen_gia' => $data['id_chuyen_gia'],
            'id_khach_hang' => $user->id,
            'ten_khach_hang' => $user->ho_ten ?? null,
            'id_phieu_dat_lich' => $appointment->id,
            'so_sao' => $data['so_sao'],
            'noi_dung' => $this->clean($data['noi_dung'] ?? null),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'Cảm ơn bạn đã đánh giá chuyên gia.'], 201);
    }

    private function hasPurchased(string $loai, int $userId, int $targetId): bool
    {
        $column = $loai === 'phu_tung' ? 'phu_tung_id' : 'san_pham_id';

        return DB::table('chi_tiet_don_hangs as d')
            ->join('don_hangs as o', 'o.id', '=', 'd.don_hang_id')
            ->where('o.users_id', $userId)
            ->where('o.trang_thai', 'hoan_thanh')
            ->where("d.{$column}", $targetId)
            ->exists();
    }

    private function myReview(string $loai, int $userId, int $targetId): ?object
    {
        $target = self::TARGETS[$loai];

        return DB::table($target['table'])->where($target['user'], $userId)->where($target['target'], $targetId)->first();
    }

    /** Lịch đã hoàn thành với chuyên gia mà khách chưa đánh giá (lịch cũ nhất trước). */
    private function reviewableAppointment(int $userId, int $expertId): ?object
    {
        return DB::table('phieu_dat_lichs as p')
            ->leftJoin('danh_gia_chuyen_gias as r', 'r.id_phieu_dat_lich', '=', 'p.id')
            ->where('p.id_khach_hang', $userId)
            ->where('p.id_chuyen_gia', $expertId)
            ->where('p.trang_thai', 2)
            ->whereNull('r.id')
            ->orderBy('p.id')
            ->first(['p.id']);
    }

    /** Ẩn bớt tên người đánh giá khi hiển thị công khai (Nguyễn Văn An -> Nguyễn V*** A**). */
    private function maskName(?string $name): string
    {
        $parts = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
        if (!$parts) {
            return 'Khách hàng';
        }

        return collect($parts)->map(fn ($part, $index) => $index === 0
            ? $part
            : Str::substr($part, 0, 1) . str_repeat('*', max(1, min(3, Str::length($part) - 1))))->implode(' ');
    }

    private function clean(?string $text): ?string
    {
        $text = trim(strip_tags((string) $text));

        return $text === '' ? null : $text;
    }
}
