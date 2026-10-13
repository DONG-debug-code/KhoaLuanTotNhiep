<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class danhMucModel extends Model
{
    use HasFactory;
    protected $table = 'danh_muc';
    protected $fillable = ['ten', 'cha_id'];
}
