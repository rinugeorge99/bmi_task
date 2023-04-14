<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'location',
        'investment_starting_date',
        'invested_amount',
        'inactivated_date',
        'status',
        'investment_return',
        'treasure_id',
    ];
}
