<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vessel extends Model
{
    protected $fillable = [
        'mmsi', 'imo', 'name', 'vessel_type', 'flag_country', 'flag_code',
        'latitude', 'longitude', 'speed', 'course', 'heading',
        'destination', 'nav_status', 'is_tracked', 'last_updated',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'speed' => 'float',
        'course' => 'float',
        'heading' => 'float',
        'is_tracked' => 'boolean',
        'last_updated' => 'datetime',
    ];

    public function scopeSearch($query, string $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('mmsi', 'like', "%{$keyword}%")
                ->orWhere('imo', 'like', "%{$keyword}%");
        });
    }
}
