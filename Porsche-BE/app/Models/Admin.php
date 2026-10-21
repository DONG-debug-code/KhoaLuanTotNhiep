<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\VaiTro;

class Admin extends Authenticatable
{

    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'admins';

    protected $fillable = [
        'ho_ten',
        'email',
        'so_dien_thoai',
        'password',
        'avatar',
        'vai_tro_id',
        'diem_tich_luy',
        'created_at',
        'update_at',
    ];

    public function vaiTro()
    {
        return $this->belongsTo(VaiTro::class, 'vai_tro_id');
    }

    public function hasRole(string $roleName): bool
    {
        return $this->vaiTro()
            ->where(function ($query) use ($roleName) {
                $query->where('vai_tros.ten', $roleName)
                    ->orWhere('vai_tros.ma_vai_tro', $roleName);
            })
            ->exists();
    }

    public function hasPermission(string $permissionName): bool
    {
        if ($this->hasRole($permissionName) || $this->hasRole('super_admin')) {
            return true;
        }

        return $this->vaiTro()
            ->whereHas('permissions', function ($query) use ($permissionName) {
                $query->where('permissions.ma_chuc_nang', $permissionName)
                    ->orWhere('permissions.ten_chuc_nang', $permissionName);
            })
            ->exists();
    }
}
