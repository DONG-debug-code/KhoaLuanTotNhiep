<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BanDo extends Model
{
    protected $table = 'ban_dos';

    protected $fillable = [
        'user_id',
        'mau_xe_id',
        'ten',
        'tong_gia'
    ];

    protected $casts = [
        'tong_gia' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function chiTiet()
    {
        return $this->hasMany(ChiTietBanDo::class, 'ban_do_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mauXe()
    {
        return $this->belongsTo(MauXe::class);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC
    |--------------------------------------------------------------------------
    */

    // Tính tổng giá realtime từ DB (tối ưu hơn load collection)
    public function tinhTongGia()
    {
        return $this->chiTiet()
            ->selectRaw('COALESCE(SUM(gia * so_luong), 0) as total')
            ->value('total');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeCuaUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
