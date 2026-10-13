<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class dongXeModel extends Model
{
    use HasFactory;
    protected $table = 'dong_xe';
    protected $fillable = ['thuong_hieu_id', 'ten', 'nam_bat_dau', 'nam_ket_thuc'];
}
