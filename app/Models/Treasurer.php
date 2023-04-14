<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\TreasurerController;
class Treasurer extends Model
{
    use HasFactory;
    protected $table ='treasurers';
    protected $primaryKey = 'id';
   protected $fillable = [
    'bmi_id',
    'starting_date',
    'ending_date',
    'treasurer',
    'status',
   ];
}
