<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class binhLuanDienDanModel extends Model
{
    use HasFactory;
    protected $table = 'binh_luan_dien_dan';
    protected $fillable = ['bai_viet_id', 'users_id', 'noi_dung', 'ngay_tao'];
}
