<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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
}
