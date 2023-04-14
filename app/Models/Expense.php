<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\ExpenseController;
class Expense extends Model
{
    use HasFactory;
    protected $table ='expenses';
    protected $primaryKey = 'id';
   protected $fillable = [
    'date',
    'amount',
    'purpose',
    'transferfromBank',
    'transferfromBank_date',
    'treasurer',
   ];
}
