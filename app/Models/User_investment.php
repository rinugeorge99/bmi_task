<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_investment extends Model
{
    use HasFactory;
    protected $fillable = [
        'bmi_id',
        'company_id',
        'invested_amount',
        'date',
        'percentage',
        'investment_return',
        'company_transaction_id'
    ];
}
