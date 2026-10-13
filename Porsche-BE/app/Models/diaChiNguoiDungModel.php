<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiaChiNguoiDung extends Model
{
    use HasFactory;

    protected $table = 'dia_chi_user';
    protected $fillable = [
        'users_id',
        'ho_ten',
        'so_dien_thoai',
        'dia_chi',
        'thanh_pho',
        'quan_huyen',
        'phuong_xa',
        'mac_dinh'
    ];

    public function nguoiDung()
    {
        return $this->belongsTo(userModel::class, 'users_id');
    }
}
