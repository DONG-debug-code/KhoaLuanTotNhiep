<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SanPham;

class MauSac extends Model
{
    protected $table = 'mau_sacs';

    protected $fillable = [
        'ten',
        'ma_mau',
        'loai'
    ];

    // Quan hệ nhiều-nhiều với sản phẩm
    public function sanPhams()
    {
        return $this->belongsToMany(
            SanPham::class,
            'san_pham_mau_sac',
            'mau_sac_id',
            'san_pham_id'
        )->withPivot(['gia', 'so_luong'])
            ->withTimestamps();
    }
}
