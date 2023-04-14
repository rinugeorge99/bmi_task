<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_eib_kunooz extends Model
{
    use HasFactory;
    protected $fillable=[
        'bmi_id',
        'eib_kunooz_id',
        'amount',
        'date_of_payment'
    ];
}
