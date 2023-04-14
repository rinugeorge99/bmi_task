<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\CountryOfResidenceController;

class Country_of_residence extends Model
{
    use HasFactory;
    protected $table = 'country_of_residences';
    protected $primaryKey = 'id';
    protected $fillable = ['type', 'bank_status'];
}
