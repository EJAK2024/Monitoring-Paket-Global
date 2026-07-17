<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContainerTrackingEvent extends Model
{
    protected $fillable = [
        'container_id', 'event_type', 'location',
        'vessel_id', 'remarks', 'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function container()
    {
        return $this->belongsTo(Container::class);
    }

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }
}
