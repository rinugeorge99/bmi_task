<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\FundCollectorController;

class Fund_collector extends Model
{
    use HasFactory;
    protected $fillable = [
        'bmi_id',
        'starting_date',
        'ending_date',
        'fund_collector_activity',
        'status',
    ];
}
