<?php
// app/Models/Config.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    protected $fillable = [
        'price_course',
        'price_session',
        'price_booking',
        'stripe_default_region', // "es" | "us"
        'singleton',
    ];

    protected $hidden = ['singleton', 'created_at', 'updated_at'];
}

