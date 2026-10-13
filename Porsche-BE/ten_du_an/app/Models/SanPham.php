<?php

use App\Models\danhMucModel;
use App\Models\hinhAnhSanPhamModel;
use App\Models\MauSac;
use App\Models\MauXe;
use Illuminate\Database\Eloquent\Model;

class SanPham extends Model
{
    protected $table = 'san_phams';

    protected $fillable = [
        'mau_xe_id',
        'ten',
        'mo_ta',
        'gia',
        'so_luong'
    ];

    // Ảnh
    public function anhSanPhams()
    {
        return $this->hasMany(hinhAnhSanPhamModel::class);
    }

    // Danh mục (nếu có cột)
    public function danhMuc()
    {
        return $this->belongsTo(danhMucModel::class);
    }

    // Hãng xe
    // public function hangXe()
    // {
    //     return $this->belongsTo(hangXe::class);
    // }

    // Mẫu xe
    public function mauXe()
    {
        return $this->belongsTo(MauXe::class);
    }

    // Nhiều màu (nâng cao)
    public function mauXes()
    {
        return $this->belongsToMany(
            MauXe::class,
            'san_pham_mau_xe'
        );
    }
    public function mauSacs()
    {
        return $this->belongsToMany(
            MauSac::class,
            'san_pham_mau_sac',
            'san_pham_id',
            'mau_sac_id'
        )->withPivot(['gia', 'so_luong'])
            ->withTimestamps();
    }
}
