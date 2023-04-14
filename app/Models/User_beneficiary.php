<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_beneficiary extends Model
{
    use HasFactory;
    protected $fillable = [
        'bmi_id',
        'name',
        'relation',
        'address',
        'contact',
        'national_id_proof',
        'document',
        'passport_no',
        'passport_expiry',
        'passport_upload',
        'dob',
        'acc_no',
        'ifsc',
        'branch',
        'bank_name',
        'beneficiary_share',
    ];
}
