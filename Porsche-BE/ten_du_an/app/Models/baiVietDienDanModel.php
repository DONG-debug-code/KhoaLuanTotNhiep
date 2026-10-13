<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class baiVietDienDanModel extends Model
{
    use HasFactory;
    protected $table = 'bai_viet_dien_dan';
    protected $fillable = ['users_id', 'tieu_de', 'noi_dung', 'ngay_tao'];
}
