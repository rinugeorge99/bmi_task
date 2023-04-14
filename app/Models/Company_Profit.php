<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company_Profit extends Model {
    use HasFactory;
    protected $table = 'company_profits';
    protected $fillable = [
        'company_id',
        'month',
        'year',
        'profit',
        'company_transaction_id'
    ];
}
