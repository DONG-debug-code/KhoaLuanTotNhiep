<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class datLichDichVuModel extends Model
{
    use HasFactory;
    protected $table = 'dat_lich_dich_vu';
    protected $fillable = ['users_id', 'loai_dich_vu', 'dong_xe_id', 'ngay_dat', 'ghi_chu', 'trang_thai'];
}
