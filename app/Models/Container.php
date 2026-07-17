<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Container extends Model
{
    protected $fillable = [
        'container_id', 'size', 'type', 'status', 'current_location',
        'vessel_id', 'origin', 'destination', 'shipper', 'consignee',
        'weight_kg', 'seal_number', 'last_scanned_at', 'estimated_arrival',
    ];

    protected $casts = [
        'last_scanned_at' => 'datetime',
        'estimated_arrival' => 'datetime',
        'weight_kg' => 'float',
    ];

    public function trackingEvents()
    {
        return $this->hasMany(ContainerTrackingEvent::class);
    }

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }
}
