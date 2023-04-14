<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bmi_id extends Model
 {
    use HasFactory;
    protected $fillable = [
        'bmi_id',
        'name',
        'email',
        'contact',
        'password',
        'mail_status',
        'verify',
        'status',
        'deactivated_by',
        'userid',
        'joining_date',
        'inactive_date',
        'accept_terms_condition',
        'form_status'
    ];
}
