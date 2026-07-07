<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Services\OpenMeteoService;
use App\Services\WorldBankService;
use Illuminate\Support\Facades\Cache;

class CountryController extends Controller
{
    public function index()
    {
        $countries = Cache::remember('countries.index', 300, function () {
            $countries = Country::all();
            $worldBank = app(WorldBankService::class);
            $weather = app(OpenMeteoService::class);

            foreach ($countries as $country) {
                $this->enrichFromDb($country);
                if ($this->needsLiveData($country)) {
                    $liveData = $worldBank->getCountryData($country->iso_code);
                    if ($liveData) {
                        $this->applyLiveData($country, $liveData);
                    }
                }
                $country->weather = $weather->getWeather($country->name);
            }

            return $countries;
        });

        return response()->json($countries);
    }

    public function show($id)
    {
        $country = Cache::remember("countries.show.{$id}", 300, function () use ($id) {
            $country = Country::findOrFail($id);
            $worldBank = app(WorldBankService::class);
            $weather = app(OpenMeteoService::class);

            $this->enrichFromDb($country);
            if ($this->needsLiveData($country)) {
                $liveData = $worldBank->getCountryData($country->iso_code);
                if ($liveData) {
                    $this->applyLiveData($country, $liveData);
                }
            }
            $country->weather = $weather->getWeather($country->name);

            return $country;
        });

        return response()->json($country);
    }

    private function enrichFromDb(Country $country): void
    {
        $country->region = $country->region;
        $country->gdp = $country->gdp;
        $country->inflation = $country->inflation;
        $country->population = $country->population;
        $country->exports = $country->exports;
        $country->imports = $country->imports;
    }

    private function needsLiveData(Country $country): bool
    {
        return is_null($country->gdp) || is_null($country->inflation)
            || is_null($country->population) || is_null($country->region);
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
