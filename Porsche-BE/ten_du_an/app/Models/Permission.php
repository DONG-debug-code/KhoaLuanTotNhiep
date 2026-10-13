<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';

    protected $fillable = [
        'ten'
    ];

    // Quan hệ với vai_tro (many-to-many)
    public function vaiTros()
    {
        return $this->belongsToMany(
            VaiTro::class,
            'role_permission',
            'permission_id',
            'vai_tro_id'
        );
    }
}
