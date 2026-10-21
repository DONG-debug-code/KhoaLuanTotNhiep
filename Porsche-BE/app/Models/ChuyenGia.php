<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class ChuyenGia extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'chuyen_gias';

    protected $hidden = [
        'password',
        'ma_kich_hoat',
    ];
}
