<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Services\RestCountriesService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $service = app(RestCountriesService::class);

        $countries = $service->getAll();

        if (empty($countries)) {
            $this->command?->warn('REST Countries API unavailable. Using fallback list.');
            Log::info('CountrySeeder: using static fallback list.');
            $countries = $service->getFallbackList();
        }

        $this->command?->info('Importing '.count($countries).' countries...');

        $imported = 0;
        foreach ($countries as $data) {
            if (empty($data['iso_code']) || empty($data['name'])) {
                continue;
            }

            $existing = Country::where('iso_code', $data['iso_code'])->first();

            Country::updateOrCreate(
                ['iso_code' => $data['iso_code']],
                [
                    'name' => $data['name'],
                    'iso_code_3' => $data['iso_code_3'] ?? null,
                    'currency_code' => $data['currency_code'] ?? ($existing->currency_code ?? null),
                    'region' => $data['region'] ?? null,
                    'language' => $data['language'] ?? ($existing->language ?? null),
                    'population' => $data['population'] ?? ($existing->population ?? null),
                    'gdp' => $existing->gdp ?? null,
                    'inflation' => $existing->inflation ?? null,
                    'exports' => $existing->exports ?? null,
                    'imports' => $existing->imports ?? null,
                ]
            );

            $imported++;
        }

        $this->command?->info("Done! {$imported} countries imported/updated.");

        if ($imported > 0) {
            $this->command?->info('Country data is now stable — reads from database, no live API calls needed for metadata.');
        }
    }
}
