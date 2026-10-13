<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use PhuTung;

class ChiTietBanDo extends Model
{
    protected $table = 'chi_tiet_ban_dos';

    public function banDo()
    {
        return $this->belongsTo(BanDo::class);
    }

    public function phuTung()
    {
        return $this->belongsTo(PhuTung::class);
    }
}
