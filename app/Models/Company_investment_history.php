<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company_investment_history extends Model
{
    use HasFactory;
    protected $fillable=[
        'company_id',
        'invested_amount',
        'date',
        'investment_return'
    ];
}
