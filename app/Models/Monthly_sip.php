<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\MonthlySipController;
class Monthly_sip extends Model
{
    use HasFactory;
    protected $table ='monthly_sips';
    protected $primaryKey = 'id';
   protected $fillable = [
    'bmi_id',
   'amount',
    'year',
    'percentage'

   ];
}
