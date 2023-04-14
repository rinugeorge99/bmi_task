<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'bmi_id',
        'amount',
        'amount_cat_id',
        'date',
        'fund_collector_id',
        'transfer',
        'transferToBank',
        'transfer_verification',
        'transfer_date',
        'transferToBank_date',
        'remarks',
        'treasurer_id'
    ];
}
