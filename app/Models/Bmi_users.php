<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bmi_users extends Model
{
    use HasFactory;
    protected $fillable=[

        'bmi_id',
        'full_name',
        'dob',
        'contact',
        'contact2',
        'email',
        'country_of_residence',
        'national_id_proof',
        'proof_details',
        'passport_no',
        'passport_expiry',
        'image',
        'passport_upload'
    ];
}
