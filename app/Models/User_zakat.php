<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_zakat extends Model
{
    use HasFactory;
    protected $fillable=[
        'bmi_id',
        'zakat_id',
        'profit',
        'amount'
    ];
}
