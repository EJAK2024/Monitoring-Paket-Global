<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name', 'country_id', 'contact_email', 'contact_phone',
        'address', 'category', 'reliability_score',
        'on_time_delivery_pct', 'quality_rating', 'lead_time_days',
        'certification', 'status', 'notes',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function riskScores()
    {
        return $this->hasMany(SupplierRiskScore::class);
    }
}
