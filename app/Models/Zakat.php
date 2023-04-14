<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\ZakatController;
class Zakat extends Model
{
    use HasFactory;
    protected $table ='zakats';
    protected $primaryKey = 'id';
   protected $fillable = [
    'date',
    'from_date',
    'to_date',
    'profit',
    'zakat',
    'zakat_details',
    'transferFromBank',
    'transferFromBank_date',
    'treasurer'

   ];
}
