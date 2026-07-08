<?php

namespace App\Http\Controllers\Api;

use App\Contracts\EconomicDataProviderInterface;
use App\Contracts\WeatherServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Support\Facades\Cache;

class CountryController extends Controller
{
    public function __construct(
        protected WeatherServiceInterface $weather,
        protected EconomicDataProviderInterface $worldBank,
    ) {}

    public function index()
    {
        return response()->json(
            Cache::remember('countries.index', 3600, function () {
                return Country::all();
            })
        );
    }

    public function show($id)
    {
        $country = Cache::remember("countries.show.{$id}", 300, function () use ($id) {
            $country = Country::findOrFail($id);

            if ($this->needsLiveData($country)) {
                $liveData = $this->worldBank->getCountryData($country->iso_code);
                if ($liveData) {
                    $this->applyLiveData($country, $liveData);
                }
            }

            $country->weather = $this->weather->getWeather($country->name);

            return $country;
        });

        return response()->json($country);
    }

    private function needsLiveData(Country $country): bool
    {
        return is_null($country->gdp) || is_null($country->inflation)
            || is_null($country->population);
    }

    private function applyLiveData(Country $country, array $liveData): void
    {
        $country->gdp = $liveData['gdp'] ?? $country->gdp;
        $country->inflation = $liveData['inflation'] ?? $country->inflation;
        $country->population = $liveData['population'] ?? $country->population;
        $country->exports = $liveData['exports'] ?? $country->exports;
        $country->imports = $liveData['imports'] ?? $country->imports;
        $country->region = $liveData['region'] ?? $country->region;
    }
}
