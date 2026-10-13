<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Permission;
use App\Models\User;

class VaiTro extends Model
{
    protected $table = 'vai_tros';

    protected $fillable = [
        'ten',
    ];

    public $timestamps = false;

    // Role có nhiều permission
    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permission',
            'vai_tro_id',
            'permission_id'
        );
    }

    // Role có nhiều user
    public function users()
    {
        return $this->hasMany(User::class, 'vai_tro_id');
    }
}
