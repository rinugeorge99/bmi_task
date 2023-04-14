<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\MonthlySipDetailsController;
class Monthly_sip_details extends Model
{
    use HasFactory;
    protected $table ='monthly_sip_details';
    protected $primaryKey = 'id';
   protected $fillable = [
    'bmi_id',
    'year',
    'month',
    'monthly_sip_id',
    'transaction_id',
    'status',
    'amount'

   ];
}
