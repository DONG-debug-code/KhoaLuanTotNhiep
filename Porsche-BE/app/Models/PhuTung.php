<?php

namespace App\Models;

use App\Models\ChiTietBanDo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhuTung extends Model
{
    use HasFactory;

    protected $table = 'phu_tungs';

    /**
     * Các thuộc tính có thể gán giá trị hàng loạt (Mass Assignment)
     */
    protected $fillable = [
        'ten',
        'gia',
        'so_luong', // <--- Đã bổ sung cột số lượng tồn kho
        'loai',
        'mo_ta',
        'anh',
    ];

    /**
     * Mối quan hệ với ChiTietBanDo
     */
    public function chiTietBanDo()
    {
        return $this->hasMany(ChiTietBanDo::class);
    }
}
