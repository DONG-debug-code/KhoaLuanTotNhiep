<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Lưu lịch sử thay đổi/nâng cấp của từng xe trong garage khách hàng.
 */
class VehicleHistoryService
{
    public const TYPES = ['nang_cap', 'thay_doi', 'bao_duong', 'cap_nhat_thong_tin', 'ghi_nhan'];

    private const TZ = 'Asia/Ho_Chi_Minh';

    private const FIELD_LABELS = [
        'ten_xe' => 'Tên xe',
        'bien_so_xe' => 'Biển số',
        'dong_xe' => 'Dòng xe',
        'nam_san_xuat' => 'Năm sản xuất',
        'mau_sac' => 'Màu sắc',
        'so_khung' => 'Số khung',
        'so_km' => 'Số km',
        'ghi_chu' => 'Ghi chú',
    ];

    /**
     * @param array{xe_khach_hang_id: int, user_id: int, loai: string, tieu_de: string, mo_ta?: ?string, thay_doi?: ?array, ngay_thuc_hien?: mixed, id_phieu_dat_lich?: ?int, nguon?: string, nguoi_tao_id?: ?int, nguoi_tao_ten?: ?string} $entry
     */
    public function record(array $entry): int
    {
        return DB::table('lich_su_xe_khach_hangs')->insertGetId([
            'xe_khach_hang_id' => $entry['xe_khach_hang_id'],
            'user_id' => $entry['user_id'],
            'loai' => $entry['loai'],
            'tieu_de' => mb_substr($entry['tieu_de'], 0, 200),
            'mo_ta' => $entry['mo_ta'] ?? null,
            'thay_doi' => empty($entry['thay_doi']) ? null : json_encode(array_values($entry['thay_doi']), JSON_UNESCAPED_UNICODE),
            'ngay_thuc_hien' => Carbon::parse($entry['ngay_thuc_hien'] ?? 'now', self::TZ)->toDateString(),
            'id_phieu_dat_lich' => $entry['id_phieu_dat_lich'] ?? null,
            'nguon' => $entry['nguon'] ?? 'he_thong',
            'nguoi_tao_id' => $entry['nguoi_tao_id'] ?? null,
            'nguoi_tao_ten' => $entry['nguoi_tao_ten'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array<int, array{truong: string, truoc: ?string, sau: ?string}> */
    public function diff(object $before, object $after): array
    {
        $changes = [];
        foreach (self::FIELD_LABELS as $field => $label) {
            $old = $before->{$field} ?? null;
            $new = $after->{$field} ?? null;
            if ((string) $old !== (string) $new) {
                $changes[] = ['truong' => $label, 'truoc' => $old === null ? null : (string) $old, 'sau' => $new === null ? null : (string) $new];
            }
        }

        return $changes;
    }

    public function recordVehicleCreated(object $vehicle, int $userId): void
    {
        $this->record([
            'xe_khach_hang_id' => $vehicle->id,
            'user_id' => $userId,
            'loai' => 'ghi_nhan',
            'tieu_de' => 'Thêm xe vào garage',
            'mo_ta' => trim($vehicle->ten_xe . ' · ' . $vehicle->bien_so_xe),
            'nguon' => 'khach_hang',
            'nguoi_tao_id' => $userId,
        ]);
    }

    public function recordVehicleUpdated(object $before, object $after, int $userId): void
    {
        $changes = $this->diff($before, $after);
        if ($changes === []) {
            return;
        }

        $this->record([
            'xe_khach_hang_id' => $after->id,
            'user_id' => $userId,
            'loai' => 'cap_nhat_thong_tin',
            'tieu_de' => 'Cập nhật thông tin xe',
            'thay_doi' => $changes,
            'nguon' => 'khach_hang',
            'nguoi_tao_id' => $userId,
        ]);
    }

    /** Ghi lịch sử khi một lịch workshop hoàn tất (chỉ một lần cho mỗi lịch). */
    public function recordAppointmentCompleted(int $appointmentId): void
    {
        $appointment = DB::table('phieu_dat_lichs')->where('id', $appointmentId)->first();
        if (!$appointment || !$appointment->id_khach_hang || !trim((string) $appointment->bien_so_xe)) {
            return;
        }
        if (DB::table('lich_su_xe_khach_hangs')->where('id_phieu_dat_lich', $appointmentId)->where('nguon', 'he_thong')->exists()) {
            return;
        }

        $userId = (int) $appointment->id_khach_hang;
        $vehicle = $this->findOrCreateVehicle($userId, $appointment);
        $parts = $this->servicedItems($appointment);
        $isUpgrade = !empty($appointment->id_don_hang) || ($appointment->loai_lich ?? null) === 'do-xe';

        $this->record([
            'xe_khach_hang_id' => $vehicle->id,
            'user_id' => $userId,
            'loai' => $isUpgrade ? 'nang_cap' : 'bao_duong',
            'tieu_de' => $appointment->tieu_de_cong_viec
                ?? (!empty($appointment->id_don_hang) ? 'Lắp đặt phụ tùng tại showroom' : 'Hoàn thành dịch vụ workshop'),
            'mo_ta' => $parts !== [] ? implode('; ', $parts) : ($appointment->ghi_chu_chuyen_gia ?? $appointment->ghi_chu ?? null),
            'thay_doi' => array_map(fn ($name) => ['truong' => 'Hạng mục', 'truoc' => null, 'sau' => $name], $parts),
            'ngay_thuc_hien' => $appointment->hoan_thanh_luc ?? 'now',
            'id_phieu_dat_lich' => $appointmentId,
            'nguon' => 'he_thong',
        ]);
    }

    public function recordAppointmentCompletedQuietly(int $appointmentId): void
    {
        try {
            $this->recordAppointmentCompleted($appointmentId);
        } catch (Throwable $e) {
            Log::error('Không thể ghi lịch sử xe khách hàng.', ['appointment_id' => $appointmentId, 'error' => $e->getMessage()]);
        }
    }

    private function findOrCreateVehicle(int $userId, object $appointment): object
    {
        $plate = mb_strtoupper(mb_substr(trim((string) $appointment->bien_so_xe), 0, 20));
        $vehicle = DB::table('xe_khach_hangs')->where('user_id', $userId)->where('bien_so_xe', $plate)->first();
        if ($vehicle) {
            return $vehicle;
        }

        $name = trim(preg_split('/[·|,]/u', (string) $appointment->thong_tin_xe)[0] ?? '');
        $id = DB::table('xe_khach_hangs')->insertGetId([
            'user_id' => $userId,
            'ten_xe' => $name !== '' ? mb_substr($name, 0, 150) : 'Xe Porsche',
            'bien_so_xe' => $plate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('xe_khach_hangs')->where('id', $id)->first();
    }

    /** @return array<int, string> */
    private function servicedItems(object $appointment): array
    {
        $items = DB::table('hoa_dons as h')
            ->join('hoa_don_chi_tiets as c', 'c.id_hoa_don', '=', 'h.id')
            ->where('h.id_phieu_dat_lich', $appointment->id)
            ->get(['c.ten_vat_tu', 'c.so_luong'])
            ->map(fn ($row) => $row->ten_vat_tu . ((int) $row->so_luong > 1 ? ' x' . (int) $row->so_luong : ''))
            ->all();

        if ($items === [] && !empty($appointment->id_don_hang)) {
            $items = DB::table('chi_tiet_don_hangs as d')
                ->leftJoin('san_phams as p', 'p.id', '=', 'd.san_pham_id')
                ->leftJoin('phu_tungs as a', 'a.id', '=', 'd.phu_tung_id')
                ->where('d.don_hang_id', $appointment->id_don_hang)
                ->select('d.so_luong', DB::raw('COALESCE(p.ten, a.ten) as ten'))
                ->get()
                ->filter(fn ($row) => $row->ten)
                ->map(fn ($row) => $row->ten . ((int) $row->so_luong > 1 ? ' x' . (int) $row->so_luong : ''))
                ->all();
        }

        return array_values($items);
    }
}
