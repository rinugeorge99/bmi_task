<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company_transaction extends Model
 {
    use HasFactory;
    protected $fillable = [
        'company_id',
        'amount',
        'amount_cat_id',
        'date',
        'fund_collector_id',
        'transfer',
        'transfer_date',
        'transfer_verification',
        'transfer_verification_date',
        'transferToBank',
        'transferToBank_date',
        'remarks',
        'collected_from',
        'treasurer_id', 'profit_status'
    ];
}
