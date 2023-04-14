<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\UserExpenseController;
class User_expense extends Model
{
    use HasFactory;
    protected $table ='user_expenses';
    protected $primaryKey = 'id';
   protected $fillable = [
   'bmi_id',
   'expense_id',
   'amount'

   ];
}
