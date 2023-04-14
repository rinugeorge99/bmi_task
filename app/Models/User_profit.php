<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_profit extends Model
{
    use HasFactory;
    protected $fillable = ['bmi_id', 'company_id', 'amount', 'date_of_payment','company_transaction_id'];
}
