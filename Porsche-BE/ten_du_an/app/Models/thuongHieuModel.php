<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class thuongHieuModel extends Model
{
    use HasFactory;
    protected $table = 'thuong_hieu';
    protected $fillable = ['ten', 'quoc_gia', 'logo_url'];
}
