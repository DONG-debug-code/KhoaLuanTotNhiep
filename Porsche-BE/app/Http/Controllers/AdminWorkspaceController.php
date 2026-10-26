<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminWorkspaceController extends Controller
{
    private const TZ = 'Asia/Ho_Chi_Minh';
    private const OPEN_ORDER_STATES = ['cho_xac_nhan', 'da_xac_nhan', 'dang_giao', 'hoan_thanh'];

    private function vn($value): ?Carbon
    {
        return $value ? Carbon::parse($value, 'UTC')->setTimezone(self::TZ) : null;
    }

    private function serviceTitle(object $row): string
    {
        if (!empty($row->tieu_de_cong_viec)) {
            return $row->tieu_de_cong_viec;
        }

        return $row->loai_lich === 'do-xe' ? 'Lắp đặt / độ xe' : 'Tư vấn kỹ thuật';
    }

    private function appointmentRows(): Collection
    {
        return DB::table('phieu_dat_lichs as l')
            ->leftJoin('chuyen_gias as c', 'c.id', '=', 'l.id_chuyen_gia')
            ->leftJoin('khu_vuc_workshops as k', 'k.id', '=', 'l.id_khu_vuc_workshop')
            ->orderByDesc('l.ngay_dat')->orderByDesc('l.gio_dat')
            ->select('l.*', 'c.ho_ten as ten_chuyen_gia', 'k.ten_khu_vuc')
            ->get()
            ->map(function ($row) {
                $row->dich_vu = $this->serviceTitle($row);
                $row->ngay_tao = $this->vn($row->created_at)?->format('Y-m-d');

                return $row;
            });
    }

    public function dashboard(): JsonResponse
    {
        $lich = $this->appointmentRows();
        $orders = DB::table('don_hangs')->get();
        $paidOrders = $orders->filter(fn ($o) => $o->trang_thai_thanh_toan === 'da_thanh_toan'
            && in_array($o->trang_thai, self::OPEN_ORDER_STATES, true));

        $standaloneDone = $lich->filter(fn ($l) => (int) $l->trang_thai === 2 && !$l->id_don_hang);
        $revenue = (float) $paidOrders->sum('thanh_tien') + (float) $standaloneDone->sum('gia_tri');

        $today = Carbon::now(self::TZ)->startOfDay();
        $days = collect(range(6, 0))->map(function ($offset) use ($today, $lich, $orders) {
            $day = $today->copy()->subDays($offset);
            $key = $day->format('Y-m-d');

            return [
                'ngay' => $key,
                'nhan' => $day->format('d/m'),
                'lich_hen' => $lich->filter(fn ($l) => $l->ngay_tao === $key)->count(),
                'don_hang' => $orders->filter(fn ($o) => $this->vn($o->created_at)?->format('Y-m-d') === $key)->count(),
            ];
        })->values();

        $months = collect(range(5, 0))->map(function ($offset) use ($today, $paidOrders, $standaloneDone) {
            $month = $today->copy()->startOfMonth()->subMonths($offset);
            $key = $month->format('Y-m');
            $sum = $paidOrders->filter(fn ($o) => $this->vn($o->thanh_toan_luc ?: $o->created_at)?->format('Y-m') === $key)->sum('thanh_tien')
                + $standaloneDone->filter(fn ($l) => str_starts_with((string) $l->ngay_dat, $key))->sum('gia_tri');

            return ['thang' => $month->format('m/Y'), 'doanh_thu' => (float) $sum];
        })->values();

        $mix = $lich->groupBy('loai_lich')->map(fn ($group, $type) => [
            'ten' => $type === 'do-xe' ? 'Lắp đặt / độ xe' : 'Tư vấn kỹ thuật',
            'so_luong' => $group->count(),
        ])->values();

        $activities = collect();
        foreach ($orders->sortByDesc('created_at')->take(6) as $o) {
            $activities->push(['thoi_gian' => $o->created_at, 'loai' => 'don_hang', 'noi_dung' => 'Đơn DH-' . str_pad((string) $o->id, 6, '0', STR_PAD_LEFT) . ' của ' . ($o->ten_nguoi_nhan ?: 'khách hàng') . ' được đặt']);
            if ($o->thanh_toan_luc) {
                $activities->push(['thoi_gian' => $o->thanh_toan_luc, 'loai' => 'thanh_toan', 'noi_dung' => 'Đơn DH-' . str_pad((string) $o->id, 6, '0', STR_PAD_LEFT) . ' đã thanh toán']);
            }
        }
        foreach ($lich->sortByDesc('created_at')->take(6) as $l) {
            $activities->push(['thoi_gian' => $l->created_at, 'loai' => 'lich_hen', 'noi_dung' => 'Lịch ' . $l->ma_dat_lich . ' của ' . $l->ten_khach_hang . ' được tạo']);
        }
        foreach (DB::table('users')->orderByDesc('created_at')->limit(4)->get() as $u) {
            $activities->push(['thoi_gian' => $u->created_at, 'loai' => 'khach_hang', 'noi_dung' => 'Khách hàng ' . $u->ho_ten . ' đã đăng ký']);
        }
        $activities = $activities->filter(fn ($a) => $a['thoi_gian'])->sortByDesc('thoi_gian')->take(6)->values()
            ->map(function ($a) {
                $a['thoi_gian'] = Carbon::parse($a['thoi_gian'], 'UTC')->setTimezone(self::TZ)->locale('vi')->diffForHumans();

                return $a;
            });

        $upcoming = $lich->filter(fn ($l) => in_array((int) $l->trang_thai, [0, 1], true) && $l->ngay_dat >= $today->format('Y-m-d'))
            ->sortBy(fn ($l) => $l->ngay_dat . ' ' . $l->gio_dat)->take(5)->values();

        $experts = DB::table('chuyen_gias')->where('trang_thai', 1)->get()->map(function ($e) use ($lich) {
            $rating = DB::table('danh_gia_chuyen_gias')->where('id_chuyen_gia', $e->id)->selectRaw('AVG(so_sao) as tb, COUNT(*) as so_luong')->first();
            $done = $lich->filter(fn ($l) => (int) $l->id_chuyen_gia === (int) $e->id && (int) $l->trang_thai === 2)->count();
            $total = $lich->filter(fn ($l) => (int) $l->id_chuyen_gia === (int) $e->id && (int) $l->trang_thai !== 3)->count();

            return [
                'id' => $e->id, 'ho_ten' => $e->ho_ten, 'chuc_danh' => $e->chuc_danh, 'anh' => $e->anh,
                'diem' => $rating->so_luong ? round((float) $rating->tb, 1) : null,
                'so_danh_gia' => (int) $rating->so_luong,
                'so_ca_hoan_tat' => max($done, (int) $e->so_du_an_hoan_thanh),
                'tai_cong_viec' => $total,
            ];
        })->sortByDesc(fn ($e) => [$e['diem'] ?? 0, $e['so_ca_hoan_tat']])->take(4)->values();

        return response()->json(['status' => true, 'data' => [
            'thong_ke' => [
                'tong_lich' => $lich->count(),
                'can_xu_ly' => $lich->where('trang_thai', 0)->count(),
                'lich_hoan_thanh' => $lich->where('trang_thai', 2)->count(),
                'tong_don_hang' => $orders->count(),
                'don_can_xu_ly' => $orders->whereIn('trang_thai', ['cho_xac_nhan', 'yeu_cau_huy'])->count(),
                'doanh_thu' => $revenue,
                'khach_hang' => DB::table('users')->count(),
                'chuyen_gia' => DB::table('chuyen_gias')->where('trang_thai', 1)->count(),
                'gia_tri_trung_binh_lich' => $lich->where('trang_thai', '!=', 3)->whereNotNull('gia_tri')->count()
                    ? round((float) $lich->where('trang_thai', '!=', 3)->whereNotNull('gia_tri')->avg('gia_tri'))
                    : null,
            ],
            'bieu_do_7_ngay' => $days,
            'doanh_thu_6_thang' => $months,
            'co_cau_dich_vu' => $mix,
            'hoat_dong' => $activities,
            'lich_sap_toi' => $upcoming,
            'chuyen_gia_noi_bat' => $experts,
        ]]);
    }

    public function lichHen(): JsonResponse
    {
        return response()->json(['status' => true, 'data' => $this->appointmentRows()->values()]);
    }

    public function donHang(): JsonResponse
    {
        $rows = DB::table('don_hangs as o')
            ->leftJoin('users as u', 'u.id', '=', 'o.users_id')
            ->leftJoin('admins as a', 'a.id', '=', 'o.xac_nhan_boi')
            ->orderByDesc('o.id')
            ->select('o.*', 'u.ho_ten as ten_khach_hang', 'a.ho_ten as nhan_vien_xac_nhan')
            ->get()
            ->map(function ($o) {
                $o->ma_don_hang = 'DH-' . str_pad((string) $o->id, 6, '0', STR_PAD_LEFT);
                $o->so_dong = DB::table('chi_tiet_don_hangs')->where('don_hang_id', $o->id)->count();

                return $o;
            });

        return response()->json(['status' => true, 'data' => $rows]);
    }

    public function hoaDon(): JsonResponse
    {
        $rows = DB::table('hoa_dons as h')
            ->leftJoin('users as u', 'u.id', '=', 'h.users_id')
            ->orderByDesc('h.id')
            ->select('h.*', 'u.ho_ten as ten_khach_hang')
            ->get();

        return response()->json(['status' => true, 'data' => $rows]);
    }

    public function khachHang(): JsonResponse
    {
        $rows = DB::table('users')->orderByDesc('id')->get()->map(function ($u) {
            $orders = DB::table('don_hangs')->where('users_id', $u->id)->get();
            $spent = $orders->filter(fn ($o) => $o->trang_thai_thanh_toan === 'da_thanh_toan'
                && in_array($o->trang_thai, self::OPEN_ORDER_STATES, true))->sum('thanh_tien');

            return [
                'id' => $u->id, 'ho_ten' => $u->ho_ten, 'email' => $u->email, 'so_dien_thoai' => $u->so_dien_thoai,
                'dia_chi' => $u->dia_chi, 'trang_thai' => (int) $u->trang_thai, 'ngay_dang_ky' => $this->vn($u->created_at)?->format('d/m/Y'),
                'so_don_hang' => $orders->count(), 'tong_chi_tieu' => (float) $spent,
                'so_lich_hen' => DB::table('phieu_dat_lichs')->where('id_khach_hang', $u->id)->count(),
            ];
        });

        return response()->json(['status' => true, 'data' => $rows]);
    }

    public function chuyenGia(): JsonResponse
    {
        $rows = DB::table('chuyen_gias')->orderBy('id')->get()->map(function ($e) {
            $rating = DB::table('danh_gia_chuyen_gias')->where('id_chuyen_gia', $e->id)->selectRaw('AVG(so_sao) as tb, COUNT(*) as n')->first();
            $jobs = DB::table('phieu_dat_lichs')->where('id_chuyen_gia', $e->id);

            return [
                'id' => $e->id, 'ho_ten' => $e->ho_ten, 'email' => $e->email, 'so_dien_thoai' => $e->so_dien_thoai,
                'chuc_danh' => $e->chuc_danh, 'so_nam_kinh_nghiem' => $e->so_nam_kinh_nghiem,
                'chuyen_mon' => is_string($e->chuyen_mon) ? (json_decode($e->chuyen_mon, true) ?: []) : [],
                'trang_thai' => (int) $e->trang_thai, 'anh' => $e->anh,
                'diem' => $rating->n ? round((float) $rating->tb, 1) : null, 'so_danh_gia' => (int) $rating->n,
                'so_lich' => (clone $jobs)->where('trang_thai', '!=', 3)->count(),
                'so_ngay_lam_viec' => DB::table('lich_lam_viecs')->where('id_chuyen_gia', $e->id)->where('ngay_lam_viec', '>=', Carbon::now(self::TZ)->format('Y-m-d'))->count(),
            ];
        });

        return response()->json(['status' => true, 'data' => $rows]);
    }

    public function lichLamViec(): JsonResponse
    {
        $from = Carbon::now(self::TZ)->format('Y-m-d');
        $rows = DB::table('lich_lam_viecs as w')
            ->join('chuyen_gias as c', 'c.id', '=', 'w.id_chuyen_gia')
            ->where('w.ngay_lam_viec', '>=', $from)
            ->orderBy('w.ngay_lam_viec')->orderBy('c.id')
            ->limit(200)
            ->select('w.id', 'w.id_chuyen_gia', 'w.ngay_lam_viec', 'w.ca_lam_viec', 'c.ho_ten as ten_chuyen_gia')
            ->get()
            ->map(function ($w) {
                $w->so_lich = DB::table('phieu_dat_lichs')->where('id_chuyen_gia', $w->id_chuyen_gia)
                    ->where('ngay_dat', $w->ngay_lam_viec)->where('trang_thai', '!=', 3)->count();

                return $w;
            });

        return response()->json(['status' => true, 'data' => $rows]);
    }

    public function nhanVien(): JsonResponse
    {
        $rows = DB::table('admins as a')->leftJoin('vai_tros as v', 'v.id', '=', 'a.vai_tro_id')
            ->orderBy('a.id')
            ->select('a.id', 'a.ho_ten', 'a.email', 'a.so_dien_thoai', 'a.trang_thai', 'v.ten as vai_tro', 'v.ma_vai_tro')
            ->get()
            ->map(function ($a) {
                $a->so_don_xac_nhan = DB::table('don_hangs')->where('xac_nhan_boi', $a->id)->count();

                return $a;
            });

        return response()->json(['status' => true, 'data' => $rows]);
    }

    public function khuVuc(): JsonResponse
    {
        $today = Carbon::now(self::TZ)->format('Y-m-d');
        $rows = DB::table('khu_vuc_workshops')->orderBy('id')->get()->map(function ($k) use ($today) {
            $base = DB::table('phieu_dat_lichs')->where('id_khu_vuc_workshop', $k->id)->where('trang_thai', '!=', 3);
            $k->lich_hom_nay = (clone $base)->where('ngay_dat', $today)->count();
            $k->lich_sap_toi = (clone $base)->where('ngay_dat', '>=', $today)->count();

            return $k;
        });

        return response()->json(['status' => true, 'data' => $rows]);
    }

    public function sanPham(): JsonResponse
    {
        $cars = DB::table('san_phams')->orderBy('id')->get()->map(fn ($p) => [
            'loai' => 'Xe', 'id' => $p->id, 'ten' => $p->ten ?: $p->ten_san_pham, 'ma' => $p->ma_san_pham,
            'gia' => (float) $p->gia, 'ton_kho' => (int) ($p->so_luong_kho ?: $p->so_luong), 'trang_thai' => (int) $p->trang_thai,
        ]);
        $parts = DB::table('phu_tungs')->orderBy('id')->get()->map(fn ($p) => [
            'loai' => 'Phụ tùng', 'id' => $p->id, 'ten' => $p->ten, 'ma' => $p->hang,
            'gia' => (float) $p->gia, 'ton_kho' => (int) $p->so_luong, 'trang_thai' => 1,
        ]);

        return response()->json(['status' => true, 'data' => $cars->concat($parts)->values()]);
    }
}
