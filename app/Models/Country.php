<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'name', 'iso_code', 'iso_code_3', 'currency_code', 'region',
        'language', 'gdp', 'inflation', 'population', 'exports', 'imports',
    ];

    public function riskScores()
    {
        return $this->hasMany(RiskScore::class);
    }

    public function news()
    {
        return $this->hasMany(NewsCache::class);
    }

    public function watchlists()
    {
        return $this->hasMany(Watchlist::class);
    }
}
