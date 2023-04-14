<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\SuperadminController;
class Superadmin extends Model
{
    use HasFactory;
    protected $table ='superadmins';
    protected $primaryKey = 'id';
   protected $fillable = [
    'username',
    'email',
   'password',
    'userid',
   ];
}
