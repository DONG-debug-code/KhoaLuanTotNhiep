<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class voucherModel extends Model
{
    use HasFactory;
    protected $table = 'voucher';
    protected $fillable = [
        'ma_voucher',
        'tieu_de',
        'loai_giam_gia',
        'gia_tri_giam',
        'don_toi_thieu',
        'giam_toi_da',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'gioi_han_su_dung'
    ];
}
