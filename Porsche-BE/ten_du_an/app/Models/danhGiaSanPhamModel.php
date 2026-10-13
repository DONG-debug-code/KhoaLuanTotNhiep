<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class danhGiaSanPhamModel extends Model
{
    use HasFactory;
    protected $table = 'danh_gia_san_pham';
    protected $fillable = ['users_id', 'san_pham_id', 'diem_danh_gia', 'binh_luan', 'ngay_tao'];
}
