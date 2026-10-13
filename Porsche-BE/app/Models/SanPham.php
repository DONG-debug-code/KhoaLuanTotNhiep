<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SanPham extends Model
{
    protected $table = 'san_phams';

    protected $fillable = [
        'mau_xe_id',
        'ten',
        'mo_ta',
        'gia',
        'so_luong',
        'loai_san_pham',
        'thong_so_chi_tiet',
        'ten_san_pham',
        'so_luong_kho',
        'slug',
    ];

    public function anhSanPhams()
    {
        return $this->hasMany(hinhAnhSanPhamModel::class, 'san_pham_id');
    }

    public function mauXe()
    {
        return $this->belongsTo(MauXe::class);
    }

    public function mauSacs()
    {
        return $this->belongsToMany(
            MauSac::class,
            'san_pham_mau_sac',
            'san_pham_id',
            'mau_sac_id'
        )->withPivot(['gia_them', 'so_luong'])->withTimestamps();
    }
}
