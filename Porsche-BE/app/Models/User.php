<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\VaiTro;
use App\Models\DiaChiNguoiDung;
use App\Models\BanDo;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'ho_ten',
        'email',
        'so_dien_thoai',
        'password',
        'avatar',
        'vai_tro_id',
        'diem_tich_luy',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIP
    |--------------------------------------------------------------------------
    */

    public function vaiTro()
    {
        return $this->belongsTo(VaiTro::class, 'vai_tro_id');
    }

    public function diaChi()
    {
        return $this->hasMany(DiaChiNguoiDung::class, 'users_id');
    }

    public function banDos()
    {
        return $this->hasMany(BanDo::class);
    }

    /*
    |--------------------------------------------------------------------------
    | PERMISSION SYSTEM
    |--------------------------------------------------------------------------
    */

    public function permissions()
    {
        return $this->vaiTro
            ? $this->vaiTro->permissions
            : collect();
    }

    public function hasPermission($permissionName)
    {
        return $this->permissions()
            ->contains('ten', $permissionName);
    }

    public function hasRole($roleName)
    {
        return $this->vaiTro
            && $this->vaiTro->ten === $roleName;
    }
}
