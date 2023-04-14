<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\EibKunoozController;
class Eib_kunooz extends Model
{
    use HasFactory;
    protected $table ='eib_kunoozs';
    protected $primaryKey = 'id';
   protected $fillable = [
    'date',
   'amount'

   ];
}
