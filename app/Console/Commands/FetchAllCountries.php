<?php

namespace App\Console\Commands;

use App\Models\Country;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchAllCountries extends Command
{
    protected $signature = 'countries:fetch-all';

    protected $description = 'Import all countries from World Bank API (free, no key)';

    public function handle(): int
    {
        $this->fetchBasic();
        $this->fetchEconomics();

        return self::SUCCESS;
    }

    private function fetchBasic(): void
    {
        $this->info('Fetching country list from World Bank API...');
        $countries = $this->fetchCountryList();

        if (empty($countries)) {
            $this->warn('API unavailable. Using static fallback list.');
            $countries = $this->fallbackCountries();
        }

        $this->info('Importing '.count($countries).' countries...');
        $bar = $this->output->createProgressBar(count($countries));
        $bar->start();

        $imported = 0;
        foreach ($countries as $data) {
            if (! $data['iso_code'] || ! $data['name']) {
                $bar->advance();

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
                    'gdp' => $existing->gdp ?? ($data['gdp'] ?? null),
                    'inflation' => $existing->inflation ?? ($data['inflation'] ?? null),
                    'exports' => $existing->exports ?? ($data['exports'] ?? null),
                    'imports' => $existing->imports ?? ($data['imports'] ?? null),
                ]
            );

            $imported++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done! {$imported} countries imported.");
    }

    private function fetchEconomics(): void
    {
        $this->info('Fetching economic data for all countries from World Bank (batch mode)...');

        $indicators = config('worldbank.indicators');

        $this->output->writeln('  Making '.count($indicators).' batch API calls (one per indicator)...');
        $updated = 0;

        foreach ($indicators as $field => $code) {
            $this->output->write("  Fetching {$field} ({$code})... ");
            $data = $this->fetchBatchIndicator($code);
            if (empty($data)) {
                $this->output->writeln('no data');

                continue;
            }
            $this->output->writeln(count($data).' countries');

            $count = 0;
            foreach ($data as $iso2 => $value) {
                if ($value === null) {
                    continue;
                }
                $country = Country::where('iso_code', $iso2)->first();
                if (! $country) {
                    continue;
                }
                if ($country->{$field} !== null) {
                    continue;
                }
                $country->{$field} = $value;
                $country->save();
                $count++;
            }
            $updated += $count;
        }

        $this->newLine();
        $this->info("Done! {$updated} economic data points filled across all countries.");
    }

    private function fetchBatchIndicator(string $indicatorCode): array
    {
        $normalizeKeys = config('worldbank.normalize');
        $indicatorKeys = array_flip(config('worldbank.indicators'));
        $field = $indicatorKeys[$indicatorCode] ?? null;

        try {
            $response = Http::timeout(30)
                ->get(config('worldbank.base_url')."/country/all/indicator/{$indicatorCode}", [
                    'format' => 'json',
                    'per_page' => 5000,
                    'date' => '2020:2024',
                ])->json();
        } catch (\Exception $e) {
            $this->warn("HTTP request failed for {$indicatorCode}: {$e->getMessage()}");

            return [];
        }

        $raw = $response;

        if (empty($raw[1])) {
            return [];
        }

        $results = [];
        foreach ($raw[1] as $entry) {
            $iso2 = $entry['country']['id'] ?? null;
            $val = $entry['value'] ?? null;
            if (! $iso2 || $val === null) {
                continue;
            }
            if (strlen($iso2) !== 2) {
                continue;
            }

            $year = (int) $entry['date'];
            if (! isset($results[$iso2]) || $year > $results[$iso2]['year']) {
                $numeric = (float) $val;
                if ($field && in_array($field, $normalizeKeys, true)) {
                    $numeric = round($numeric / 1e9, 2);
                } else {
                    $numeric = round($numeric, 2);
                }
                $results[$iso2] = ['value' => $numeric, 'year' => $year];
            }
        }

        return array_map(fn ($r) => $r['value'], $results);
    }

    private function fetchCountryList(): array
    {
        try {
            $response = Http::timeout(15)
                ->get(config('worldbank.base_url').'/country', [
                    'format' => 'json',
                    'per_page' => 300,
                ])->json();
        } catch (\Exception $e) {
            return [];
        }

        $raw = $response;

        if (empty($raw[1])) {
            return [];
        }

        $countries = [];
        foreach ($raw[1] as $c) {
            if (! empty($c['region']['value']) && $c['region']['value'] !== 'Aggregates') {
                $countries[] = [
                    'name' => $c['name'],
                    'iso_code' => $c['iso2Code'],
                    'iso_code_3' => $c['iso3Code'] ?? null,
                    'region' => $c['region']['value'] ?? null,
                    'population' => $c['population'] ?? null,
                ];
            }
        }

        return $countries;
    }

    private function fallbackCountries(): array
    {
        $path = database_path('data/fallback_countries.json');
        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }
}
