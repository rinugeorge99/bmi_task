<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\UserBankController;
class User_bank extends Model
{
    use HasFactory;
    protected $table ='user_banks';
    protected $primaryKey = 'id';
   protected $fillable = [
    'bmi_id',
    'abroad',
    'ifsc_code',
    'branch',
    'currency',
    'iban_no',
    'swift',
    'bank_name',
    'acc_name',
    'acc_no',

   ];
}
