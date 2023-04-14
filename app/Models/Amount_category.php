<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Amount_category extends Model
{
    use HasFactory;
    protected $fillable = ['amount_type', 'description', 'code'];
}
