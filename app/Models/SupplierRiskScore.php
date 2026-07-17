<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierRiskScore extends Model
{
    protected $fillable = [
        'supplier_id', 'country_risk_score', 'delivery_risk',
        'quality_risk', 'compliance_risk', 'financial_risk',
        'total_score', 'risk_level', 'calculated_at',
    ];

    protected $casts = [
        'calculated_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
