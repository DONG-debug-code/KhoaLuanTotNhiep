<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MauXe extends Model
{
    public function banDos()
    {
        return $this->hasMany(BanDo::class);
    }
}
