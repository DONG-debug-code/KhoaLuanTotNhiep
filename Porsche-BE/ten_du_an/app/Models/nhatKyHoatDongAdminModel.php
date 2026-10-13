<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class nhatKyHoatDongAdminModel extends Model
{
    use HasFactory;
    protected $table = 'nhat_ky_hoat_dong_admin';
    protected $fillable = ['admin_id', 'hanh_dong', 'doi_tuong', 'ngay_tao'];
}
