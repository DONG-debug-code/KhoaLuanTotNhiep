<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class nhatKiVoucherModel extends Model
{
    use HasFactory;
    protected $table = 'nhat_ky_voucher';
    protected $fillable = ['users_id', 'voucher_id', 'ngay_su_dung'];
}
