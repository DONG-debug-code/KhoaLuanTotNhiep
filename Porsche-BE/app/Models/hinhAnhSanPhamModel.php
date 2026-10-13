<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class hinhAnhSanPhamModel extends Model
{
    use HasFactory;

    protected $table = 'anh_san_phams';

    protected $fillable = [
        'san_pham_id',
        'duong_dan',
        'la_anh_chinh',
    ];

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }
}
