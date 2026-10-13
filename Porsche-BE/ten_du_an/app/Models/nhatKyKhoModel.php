<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class nhatKyKhoModel extends Model
{
    use HasFactory;
    protected $table = 'nhat_ky_kho';
    protected $fillable = ['san_pham_id', 'so_luong_cu', 'so_luong_moi', 'loai_thay_doi', 'ngay_tao'];
}
