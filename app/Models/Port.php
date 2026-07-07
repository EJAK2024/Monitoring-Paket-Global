<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Port extends Model
{
    protected $fillable = [
        'name', 'country', 'country_code',
        'latitude', 'longitude', 'port_type',
    ];
}
