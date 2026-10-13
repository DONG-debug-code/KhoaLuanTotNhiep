<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class hinhAnhSanPhamModel extends Model
{
    use HasFactory;

    protected $table = 'hinh_anh_san_pham';

    protected $fillable = [
        'san_pham_id',
        'url_anh',
        'thu_tu',
    ];

    public function sanPham()
    {
        return $this->belongsTo(sanPhamModel::class, 'san_pham_id');
    }
}
