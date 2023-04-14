<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\NotificationController;

class Notification extends Model
 {
    use HasFactory;
    protected $fillable = [
        'date',
        'content',
        'viewers',
        'viewed',
        'status'
    ];
}
