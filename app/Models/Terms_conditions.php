<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\TermsConditionsController;


class Terms_conditions extends Model
{
    use HasFactory;
    protected $table ='terms_conditions';
    protected $primaryKey = 'id';
   protected $fillable = [
    'description',

   ];
}
