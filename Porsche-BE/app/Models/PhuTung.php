<?php

namespace App\Models;

use App\Models\ChiTietBanDo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhuTung extends Model
{
    use HasFactory;

    protected $table = 'phu_tungs';

    /**
     * Các thuộc tính có thể gán giá trị hàng loạt (Mass Assignment)
     */
    protected $fillable = [
        'ten',
        'gia',
        'so_luong', // <--- Đã bổ sung cột số lượng tồn kho
        'loai',
        'mo_ta',
        'bao_gom',
        'dong_xe',
        'anh',
    ];

    protected $casts = [
        'bao_gom' => 'array',
        'dong_xe' => 'array',
    ];

    protected $appends = ['loai_hien_thi'];

    public function getLoaiHienThiAttribute(): string
    {
        return [
            'hieu_suat' => 'Hiệu năng',
            'he_thong_xa' => 'Hệ thống xả',
            'he_thong_treo' => 'Hệ thống treo',
            'mam_xe' => 'Mâm xe',
            'phanh' => 'Phanh',
            'noi_that' => 'Nội thất',
            'ngoai_that' => 'Ngoại thất',
            'cham_soc_xe' => 'Chăm sóc xe',
        ][$this->loai] ?? $this->loai;
    }

    /**
     * Mối quan hệ với ChiTietBanDo
     */
    public function chiTietBanDo()
    {
        return $this->hasMany(ChiTietBanDo::class);
    }
}
