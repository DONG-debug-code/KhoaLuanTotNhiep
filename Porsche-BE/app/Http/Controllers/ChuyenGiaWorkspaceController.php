<?php

namespace App\Http\Controllers;

use App\Mail\LichHenMail;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ChuyenGiaWorkspaceController extends Controller
{
    private const TZ = 'Asia/Ho_Chi_Minh';
    private const ACTIVE_STAGES = ['dang_thi_cong', 'dang_test_dyno'];
    private const DEFAULT_PARAMS = ['Công suất', 'Mô-men xoắn', 'ECU', 'Dyno'];

    private static function today(): string
    {
        return Carbon::now(self::TZ)->toDateString();
    }

    private function expertId(Request $request): int
    {
        return (int) ($request->attributes->get('id_chuyen_gia') ?? $request->user()->id);
    }

    private function stage(object $row): string
    {
        return match ((int) $row->trang_thai) {
            3 => 'da_huy',
            2 => 'da_ban_giao',
            0 => 'cho_xac_nhan',
            default => $row->giai_doan ?: 'cho_nhan_xe',
        };
    }

    private function customerKey(object $row): string
    {
        if (!empty($row->id_khach_hang)) {
            return 'u' . $row->id_khach_hang;
        }
        return 'p' . preg_replace('/\D+/', '', (string) $row->dien_thoai);
    }

    // Gắn tên công việc, giá trị, khung giờ và trạng thái chuẩn hóa cho các phiếu đặt lịch.
    private function enrich(Collection $rows): Collection
    {
        $orderIds = $rows->pluck('id_don_hang')->filter()->unique()->values();
        $items = collect();
        if ($orderIds->isNotEmpty()) {
            $items = DB::table('chi_tiet_don_hangs as ct')
                ->leftJoin('phu_tungs as pt', 'pt.id', '=', 'ct.phu_tung_id')
                ->leftJoin('san_phams as sp', 'sp.id', '=', 'ct.san_pham_id')
                ->whereIn('ct.don_hang_id', $orderIds)
                ->select('ct.don_hang_id', 'ct.so_luong', 'ct.thanh_tien', DB::raw('COALESCE(pt.ten, sp.ten_san_pham, sp.ten) as ten'))
                ->get()
                ->groupBy('don_hang_id');
        }

        return $rows->map(function ($row) use ($items) {
            $orderItems = $items->get($row->id_don_hang, collect());
            $names = $orderItems->pluck('ten')->filter()->values();
            $title = $row->tieu_de_cong_viec
                ?: ($names->isNotEmpty()
                    ? 'Lắp đặt: ' . $names->take(2)->implode(', ') . ($names->count() > 2 ? ' +' . ($names->count() - 2) : '')
                    : ($row->loai_lich === 'tu-van' ? 'Tư vấn độ xe' : 'Đặt lịch độ xe'));
            $start = Carbon::parse(substr((string) $row->ngay_dat, 0, 10) . ' ' . ($row->gio_dat ?: '08:00'));
            $duration = (int) ($row->thoi_luong_phut ?: 90);
            $row->giai_doan_hien_tai = $this->stage($row);
            $row->tieu_de = $title;
            $row->gia_tri_ca = (float) ($row->gia_tri ?? $orderItems->sum('thanh_tien'));
            $row->gio_ket_thuc = $start->copy()->addMinutes($duration)->format('H:i');
            $row->thoi_luong = $duration;
            $row->ten_xe = trim(explode('·', (string) $row->thong_tin_xe)[0]) ?: null;
            $row->khach_key = $this->customerKey($row);
            return $row;
        });
    }

    private function baseQuery()
    {
        return DB::table('phieu_dat_lichs as p')
            ->leftJoin('khu_vuc_workshops as k', 'k.id', '=', 'p.id_khu_vuc_workshop')
            ->leftJoin('chuyen_gias as c', 'c.id', '=', 'p.id_chuyen_gia')
            ->select('p.*', 'k.ten_khu_vuc', 'c.ho_ten as ten_chuyen_gia');
    }

    private function progress(object $row): int
    {
        if ($row->giai_doan_hien_tai === 'dang_test_dyno') {
            return 80;
        }
        $started = $row->bat_dau_luc ? Carbon::parse($row->bat_dau_luc) : null;
        if (!$started) {
            return 10;
        }
        $elapsed = $started->diffInMinutes(now());
        return (int) max(10, min(75, round($elapsed / max(1, $row->thoi_luong) * 100)));
    }

    private function bays(): Collection
    {
        $bays = DB::table('khu_vuc_workshops')->whereIn('trang_thai', [1, 2])->orderBy('id')->get();
        $active = $this->enrich($this->baseQuery()
            ->where('p.trang_thai', 1)
            ->whereIn('p.giai_doan', self::ACTIVE_STAGES)
            ->whereNotNull('p.id_khu_vuc_workshop')
            ->get())->keyBy('id_khu_vuc_workshop');

        return $bays->map(function ($bay) use ($active) {
            $job = $active->get($bay->id);
            $status = (int) $bay->trang_thai === 2 ? 'bao_tri' : ($job ? $job->giai_doan_hien_tai : 'trong');
            return [
                'id' => $bay->id,
                'ten' => $bay->ten_khu_vuc,
                'ghi_chu' => $bay->ghi_chu,
                'trang_thai' => $status,
                'cong_viec' => $job ? [
                    'id' => $job->id,
                    'xe' => $job->ten_xe,
                    'bien_so' => $job->bien_so_xe,
                    'tieu_de' => $job->tieu_de,
                    'chuyen_gia' => $job->ten_chuyen_gia,
                    'gio_bat_dau' => $job->bat_dau_luc ? Carbon::parse($job->bat_dau_luc)->setTimezone(self::TZ)->format('H:i') : $job->gio_dat,
                    'gio_ket_thuc' => $job->bat_dau_luc ? Carbon::parse($job->bat_dau_luc)->setTimezone(self::TZ)->addMinutes($job->thoi_luong)->format('H:i') : $job->gio_ket_thuc,
                    'tien_do' => $this->progress($job),
                ] : null,
            ];
        })->values();
    }

    public function bangLamViec(Request $request): JsonResponse
    {
        $expertId = $this->expertId($request);
        $date = Carbon::parse($request->query('ngay', self::today()))->toDateString();

        $expert = DB::table('chuyen_gias')->where('id', $expertId)->first();
        $rating = DB::table('danh_gia_chuyen_gias')->where('id_chuyen_gia', $expertId)
            ->selectRaw('COALESCE(AVG(so_sao), 0) as tb, COUNT(*) as so_luong')->first();
        $doneTotal = DB::table('phieu_dat_lichs')->where('id_chuyen_gia', $expertId)->where('trang_thai', 2)->count();

        $today = $this->enrich($this->baseQuery()
            ->where('p.id_chuyen_gia', $expertId)
            ->whereDate('p.ngay_dat', $date)
            ->where('p.trang_thai', '!=', 3)
            ->orderBy('p.gio_dat')
            ->get());

        $pending = $this->enrich($this->baseQuery()
            ->where('p.id_chuyen_gia', $expertId)
            ->where('p.trang_thai', 0)
            ->whereDate('p.ngay_dat', '>=', self::today())
            ->orderBy('p.ngay_dat')->orderBy('p.gio_dat')
            ->get());

        $dynoBusy = DB::table('phieu_dat_lichs')->where('trang_thai', 1)->where('giai_doan', 'dang_test_dyno')->exists();

        return response()->json(['status' => true, 'data' => [
            'ngay' => $date,
            'chuyen_gia' => [
                'ho_ten' => $expert->ho_ten,
                'chuc_danh' => $expert->chuc_danh,
                'dia_chi' => $expert->dia_chi,
                'anh' => $expert->anh,
                'so_nam_kinh_nghiem' => $expert->so_nam_kinh_nghiem,
                'chuyen_mon' => json_decode((string) $expert->chuyen_mon, true) ?: [],
                'diem_danh_gia' => round((float) $rating->tb, 1),
                'so_danh_gia' => (int) $rating->so_luong,
                'so_ca_hoan_tat' => max($doneTotal, (int) $expert->so_du_an_hoan_thanh),
            ],
            'thong_ke' => [
                'ca_hom_nay' => $today->count(),
                'da_hoan_tat' => $today->where('giai_doan_hien_tai', 'da_ban_giao')->count(),
                'dang_thi_cong' => $today->where('giai_doan_hien_tai', 'dang_thi_cong')->count(),
                'cho_test_dyno' => $today->where('giai_doan_hien_tai', 'dang_test_dyno')->count(),
                'gia_tri_ca' => $today->sum('gia_tri_ca'),
            ],
            'lich' => $today->values(),
            'cho_xac_nhan' => $pending->values(),
            'bay' => $this->bays(),
            'dyno_ban' => $dynoBusy,
        ]], 200);
    }

    public function khuVucWorkshop(): JsonResponse
    {
        $bays = $this->bays();
        $waiting = $this->enrich($this->baseQuery()
            ->where('p.trang_thai', 1)
            ->where('p.giai_doan', 'cho_nhan_xe')
            ->whereDate('p.ngay_dat', self::today())
            ->orderBy('p.gio_dat')
            ->get());

        return response()->json(['status' => true, 'data' => [
            'bay' => $bays,
            'dang_co_xe' => $bays->whereIn('trang_thai', self::ACTIVE_STAGES)->count(),
            'bay_trong' => $bays->where('trang_thai', 'trong')->count(),
            'dang_bao_tri' => $bays->where('trang_thai', 'bao_tri')->count(),
            'xe_cho_nhan_ca' => $waiting->values(),
        ]], 200);
    }

    public function khachHangPhuTrach(Request $request): JsonResponse
    {
        $expertId = $this->expertId($request);
        $mine = $this->enrich($this->baseQuery()->where('p.id_chuyen_gia', $expertId)->orderByDesc('p.ngay_dat')->get());
        $keys = $mine->pluck('khach_key')->unique()->values();

        $notes = DB::table('ghi_chu_khach_chuyen_gias')->where('id_chuyen_gia', $expertId)->pluck('noi_dung', 'khach_key');
        $params = DB::table('thong_so_tuning_xes')->where('id_chuyen_gia', $expertId)->get()->groupBy('khach_key');

        $userIds = $mine->pluck('id_khach_hang')->filter()->unique();
        $allJobs = $userIds->isEmpty() ? collect() : $this->enrich($this->baseQuery()
            ->whereIn('p.id_khach_hang', $userIds)->where('p.trang_thai', 2)->get());

        $customers = $keys->map(function ($key) use ($mine, $notes, $params, $allJobs, $expertId) {
            $jobs = $mine->where('khach_key', $key)->values();
            $first = $jobs->first();
            $valid = $jobs->where('trang_thai', '!=', 3);
            $done = $jobs->where('trang_thai', 2);
            $spend = $valid->sum('gia_tri_ca');

            $history = $key[0] === 'u'
                ? $allJobs->where('khach_key', $key)
                : $done;
            $history = $history->sortByDesc('ngay_dat')->take(10)->map(fn ($j) => [
                'id' => $j->id,
                'tieu_de' => $j->tieu_de,
                'ngay' => substr((string) $j->ngay_dat, 0, 10),
                'bay' => $j->ten_khu_vuc,
                'gia_tri' => $j->gia_tri_ca,
                'ghi_chu' => $j->ghi_chu_chuyen_gia,
                'chuyen_gia' => $j->ten_chuyen_gia,
            ])->values();

            $vehicles = $jobs->filter(fn ($j) => $j->bien_so_xe || $j->ten_xe)
                ->unique(fn ($j) => $j->bien_so_xe . '|' . $j->ten_xe)
                ->map(function ($j) use ($params, $key) {
                    $plate = (string) $j->bien_so_xe;
                    $saved = collect($params->get($key, []))->where('bien_so_xe', $plate)->keyBy('hang_muc');
                    $rows = collect(self::DEFAULT_PARAMS)->merge($saved->keys())->unique()->map(fn ($name) => [
                        'hang_muc' => $name,
                        'truoc' => $saved->get($name)->gia_tri_truoc ?? '',
                        'hien_tai' => $saved->get($name)->gia_tri_hien_tai ?? '',
                    ])->values();
                    return ['bien_so' => $plate, 'ten_xe' => $j->ten_xe, 'thong_so' => $rows];
                })->values();

            $lastJob = $done->first() ?? $jobs->first();
            $customerNote = $jobs->pluck('ghi_chu')->filter()->first();

            return [
                'key' => $key,
                'ho_ten' => $first->ten_khach_hang,
                'so_dien_thoai' => $first->dien_thoai,
                'email' => $first->email,
                'hang' => $spend >= 500_000_000 ? 'Platinum' : ($spend >= 200_000_000 ? 'Gold' : ($spend >= 50_000_000 ? 'Silver' : 'Thành viên')),
                'lan_gan_nhat' => substr((string) $lastJob->ngay_dat, 0, 10),
                'tong_chi_tieu' => $spend,
                'so_ca_da_lam' => $done->count(),
                'so_lich_hen' => $jobs->count(),
                'ghi_chu_chuyen_gia' => $notes->get($key),
                'ghi_chu_khach' => $customerNote,
                'xe' => $vehicles,
                'lich_su' => $history,
            ];
        })->sortByDesc('lan_gan_nhat')->values();

        return response()->json(['status' => true, 'data' => $customers], 200);
    }

    public function luuThongSoTuning(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'khach_key' => 'required|string|max:60',
            'bien_so_xe' => 'nullable|string|max:30',
            'thong_so' => 'required|array|min:1|max:20',
            'thong_so.*.hang_muc' => 'required|string|max:60',
            'thong_so.*.truoc' => 'nullable|string|max:120',
            'thong_so.*.hien_tai' => 'nullable|string|max:120',
        ]);
        $expertId = $this->expertId($request);

        $isMine = DB::table('phieu_dat_lichs')->where('id_chuyen_gia', $expertId)->get()
            ->contains(fn ($row) => $this->customerKey($row) === $validated['khach_key']);
        if (!$isMine) {
            return response()->json(['status' => false, 'message' => 'Bạn không phụ trách khách hàng này.'], 403);
        }

        foreach ($validated['thong_so'] as $item) {
            DB::table('thong_so_tuning_xes')->updateOrInsert(
                [
                    'id_chuyen_gia' => $expertId,
                    'khach_key' => $validated['khach_key'],
                    'bien_so_xe' => $validated['bien_so_xe'] ?? '',
                    'hang_muc' => $item['hang_muc'],
                ],
                ['gia_tri_truoc' => $item['truoc'] ?? null, 'gia_tri_hien_tai' => $item['hien_tai'] ?? null, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        return response()->json(['status' => true, 'message' => 'Đã lưu thông số tuning.'], 200);
    }

    public function luuGhiChuKhach(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'khach_key' => 'required|string|max:60',
            'noi_dung' => 'nullable|string|max:1000',
        ]);
        $expertId = $this->expertId($request);

        $isMine = DB::table('phieu_dat_lichs')->where('id_chuyen_gia', $expertId)->get()
            ->contains(fn ($row) => $this->customerKey($row) === $validated['khach_key']);
        if (!$isMine) {
            return response()->json(['status' => false, 'message' => 'Bạn không phụ trách khách hàng này.'], 403);
        }

        DB::table('ghi_chu_khach_chuyen_gias')->updateOrInsert(
            ['id_chuyen_gia' => $expertId, 'khach_key' => $validated['khach_key']],
            ['noi_dung' => $validated['noi_dung'] ?? null, 'updated_at' => now(), 'created_at' => now()]
        );

        return response()->json(['status' => true, 'message' => 'Đã lưu ghi chú khách hàng.'], 200);
    }

    // Luồng thi công: nhận xe vào bay -> test Dyno (tùy chọn) -> hoàn tất & bàn giao.
    public function capNhatTienDo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer',
            'hanh_dong' => 'required|string|in:bat-dau,test-dyno,hoan-tat',
            'id_khu_vuc_workshop' => 'nullable|integer',
            'ghi_chu_chuyen_gia' => 'nullable|string|max:1000',
        ]);
        $expertId = $this->expertId($request);

        $job = DB::table('phieu_dat_lichs')->where('id', $validated['id'])->where('id_chuyen_gia', $expertId)->first();
        if (!$job) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy ca được phân công cho bạn.'], 404);
        }
        if ((int) $job->trang_thai !== 1) {
            return response()->json(['status' => false, 'message' => 'Cần xác nhận lịch hẹn trước khi thi công.'], 422);
        }

        $stage = $job->giai_doan ?: 'cho_nhan_xe';
        $isConsult = $job->loai_lich === 'tu-van';
        $update = ['updated_at' => now()];
        $subject = null;
        $note = $validated['ghi_chu_chuyen_gia'] ?? null;
        if ($note !== null && $note !== '') {
            $update['ghi_chu_chuyen_gia'] = $note;
        }

        switch ($validated['hanh_dong']) {
            case 'bat-dau':
                if ($stage !== 'cho_nhan_xe') {
                    return response()->json(['status' => false, 'message' => 'Ca này đã được bắt đầu.'], 422);
                }
                $bayId = $validated['id_khu_vuc_workshop'] ?? $job->id_khu_vuc_workshop;
                if (!$isConsult) {
                    $bay = $bayId ? DB::table('khu_vuc_workshops')->where('id', $bayId)->first() : null;
                    if (!$bay || (int) $bay->trang_thai !== 1) {
                        return response()->json(['status' => false, 'message' => 'Vui lòng chọn bay đang hoạt động để nhận xe.'], 422);
                    }
                    $busy = DB::table('phieu_dat_lichs')->where('id_khu_vuc_workshop', $bayId)->where('id', '!=', $job->id)
                        ->where('trang_thai', 1)->whereIn('giai_doan', self::ACTIVE_STAGES)->exists();
                    if ($busy) {
                        return response()->json(['status' => false, 'message' => 'Bay này đang có xe thi công.'], 422);
                    }
                    $update['id_khu_vuc_workshop'] = $bayId;
                }
                $update += ['giai_doan' => 'dang_thi_cong', 'bat_dau_luc' => now()];
                $subject = $isConsult ? 'Buổi tư vấn của bạn đã bắt đầu' : 'Xe của bạn đã vào bay thi công';
                break;

            case 'test-dyno':
                if ($isConsult || $stage !== 'dang_thi_cong') {
                    return response()->json(['status' => false, 'message' => 'Chỉ ca đang thi công mới chuyển sang test Dyno.'], 422);
                }
                if (DB::table('phieu_dat_lichs')->where('id', '!=', $job->id)->where('trang_thai', 1)->where('giai_doan', 'dang_test_dyno')->exists()) {
                    return response()->json(['status' => false, 'message' => 'Thiết bị Dyno đang được sử dụng cho xe khác.'], 422);
                }
                $update['giai_doan'] = 'dang_test_dyno';
                $subject = 'Xe của bạn đang được test Dyno';
                break;

            default:
                if (!in_array($stage, self::ACTIVE_STAGES, true)) {
                    return response()->json(['status' => false, 'message' => 'Ca chưa được bắt đầu thi công.'], 422);
                }
                $update += ['trang_thai' => 2, 'giai_doan' => 'da_ban_giao', 'hoan_thanh_luc' => now(), 'ban_giao_luc' => now()];
                $subject = 'Xe của bạn đã hoàn tất và sẵn sàng bàn giao';
        }

        DB::transaction(function () use ($job, $update, $validated, $expertId) {
            DB::table('phieu_dat_lichs')->where('id', $job->id)->update($update);
            if ($validated['hanh_dong'] === 'hoan-tat') {
                $enriched = $this->enrich($this->baseQuery()->where('p.id', $job->id)->get())->first();
                DB::table('lich_su_cong_viec_chuyen_gias')->insert([
                    'id_chuyen_gia' => $expertId,
                    'tieu_de' => $enriched->tieu_de,
                    'ten_xe' => $enriched->ten_xe ?: $job->bien_so_xe,
                    'mo_ta' => $update['ghi_chu_chuyen_gia'] ?? 'Hoàn thành và bàn giao xe cho khách hàng.',
                    'ngay_hoan_thanh' => self::today(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('chuyen_gias')->where('id', $expertId)->increment('so_du_an_hoan_thanh');
            }
        });

        try {
            if ($job->email && $subject) {
                Mail::to($job->email)->send(new LichHenMail(DB::table('phieu_dat_lichs')->where('id', $job->id)->first(), $subject));
            }
        } catch (\Throwable $e) {
            Log::warning('Không gửi được email tiến độ thi công.', ['error' => $e->getMessage()]);
        }

        return response()->json(['status' => true, 'message' => 'Đã cập nhật tiến độ ca làm việc.'], 200);
    }
}
