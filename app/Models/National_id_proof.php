<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\NationalIdProofController;
class National_id_proof extends Model
{
    use HasFactory;
    protected $table ='national_id_proofs';
    protected $primaryKey = 'id';
   protected $fillable = [
   'type',
   'expiry_status'

   ];
}
