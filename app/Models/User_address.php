<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_address extends Model
{
    use HasFactory;
    protected $fillable = [
        'bmi_id',
        'resi_city',
        'street_name',
        'resi_house_name',
        'resi_landmark',
        'po_box',
        'house_name',
        'post',
        'district',
        'state',
        'pincode',
        'landmark',
        'contact',
    ];
}
