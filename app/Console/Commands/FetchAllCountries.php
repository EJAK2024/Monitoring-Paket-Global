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
        return [
            ['name' => 'Afghanistan', 'iso_code' => 'AF', 'iso_code_3' => 'AFG', 'region' => 'Asia'],
            ['name' => 'Albania', 'iso_code' => 'AL', 'iso_code_3' => 'ALB', 'region' => 'Europe'],
            ['name' => 'Algeria', 'iso_code' => 'DZ', 'iso_code_3' => 'DZA', 'region' => 'Africa'],
            ['name' => 'Angola', 'iso_code' => 'AO', 'iso_code_3' => 'AGO', 'region' => 'Africa'],
            ['name' => 'Argentina', 'iso_code' => 'AR', 'iso_code_3' => 'ARG', 'region' => 'Americas'],
            ['name' => 'Australia', 'iso_code' => 'AU', 'iso_code_3' => 'AUS', 'region' => 'Oceania', 'currency_code' => 'AUD', 'language' => 'English'],
            ['name' => 'Austria', 'iso_code' => 'AT', 'iso_code_3' => 'AUT', 'region' => 'Europe'],
            ['name' => 'Bangladesh', 'iso_code' => 'BD', 'iso_code_3' => 'BGD', 'region' => 'Asia'],
            ['name' => 'Belgium', 'iso_code' => 'BE', 'iso_code_3' => 'BEL', 'region' => 'Europe'],
            ['name' => 'Brazil', 'iso_code' => 'BR', 'iso_code_3' => 'BRA', 'region' => 'Americas'],
            ['name' => 'Canada', 'iso_code' => 'CA', 'iso_code_3' => 'CAN', 'region' => 'Americas'],
            ['name' => 'Chile', 'iso_code' => 'CL', 'iso_code_3' => 'CHL', 'region' => 'Americas'],
            ['name' => 'China', 'iso_code' => 'CN', 'iso_code_3' => 'CHN', 'region' => 'Asia', 'currency_code' => 'CNY', 'language' => 'Chinese'],
            ['name' => 'Colombia', 'iso_code' => 'CO', 'iso_code_3' => 'COL', 'region' => 'Americas'],
            ['name' => 'Denmark', 'iso_code' => 'DK', 'iso_code_3' => 'DNK', 'region' => 'Europe'],
            ['name' => 'Egypt', 'iso_code' => 'EG', 'iso_code_3' => 'EGY', 'region' => 'Africa'],
            ['name' => 'Finland', 'iso_code' => 'FI', 'iso_code_3' => 'FIN', 'region' => 'Europe'],
            ['name' => 'France', 'iso_code' => 'FR', 'iso_code_3' => 'FRA', 'region' => 'Europe'],
            ['name' => 'Germany', 'iso_code' => 'DE', 'iso_code_3' => 'DEU', 'region' => 'Europe', 'currency_code' => 'EUR', 'language' => 'German'],
            ['name' => 'Greece', 'iso_code' => 'GR', 'iso_code_3' => 'GRC', 'region' => 'Europe'],
            ['name' => 'Hong Kong', 'iso_code' => 'HK', 'iso_code_3' => 'HKG', 'region' => 'Asia'],
            ['name' => 'India', 'iso_code' => 'IN', 'iso_code_3' => 'IND', 'region' => 'Asia', 'currency_code' => 'INR', 'language' => 'Hindi'],
            ['name' => 'Indonesia', 'iso_code' => 'ID', 'iso_code_3' => 'IDN', 'region' => 'Asia', 'currency_code' => 'IDR', 'language' => 'Indonesian'],
            ['name' => 'Iran', 'iso_code' => 'IR', 'iso_code_3' => 'IRN', 'region' => 'Asia'],
            ['name' => 'Iraq', 'iso_code' => 'IQ', 'iso_code_3' => 'IRQ', 'region' => 'Asia'],
            ['name' => 'Ireland', 'iso_code' => 'IE', 'iso_code_3' => 'IRL', 'region' => 'Europe'],
            ['name' => 'Israel', 'iso_code' => 'IL', 'iso_code_3' => 'ISR', 'region' => 'Asia'],
            ['name' => 'Italy', 'iso_code' => 'IT', 'iso_code_3' => 'ITA', 'region' => 'Europe'],
            ['name' => 'Japan', 'iso_code' => 'JP', 'iso_code_3' => 'JPN', 'region' => 'Asia', 'currency_code' => 'JPY', 'language' => 'Japanese'],
            ['name' => 'Kazakhstan', 'iso_code' => 'KZ', 'iso_code_3' => 'KAZ', 'region' => 'Asia'],
            ['name' => 'Kenya', 'iso_code' => 'KE', 'iso_code_3' => 'KEN', 'region' => 'Africa'],
            ['name' => 'Kuwait', 'iso_code' => 'KW', 'iso_code_3' => 'KWT', 'region' => 'Asia'],
            ['name' => 'Malaysia', 'iso_code' => 'MY', 'iso_code_3' => 'MYS', 'region' => 'Asia', 'currency_code' => 'MYR', 'language' => 'Malay'],
            ['name' => 'Mexico', 'iso_code' => 'MX', 'iso_code_3' => 'MEX', 'region' => 'Americas'],
            ['name' => 'Morocco', 'iso_code' => 'MA', 'iso_code_3' => 'MAR', 'region' => 'Africa'],
            ['name' => 'Netherlands', 'iso_code' => 'NL', 'iso_code_3' => 'NLD', 'region' => 'Europe'],
            ['name' => 'New Zealand', 'iso_code' => 'NZ', 'iso_code_3' => 'NZL', 'region' => 'Oceania'],
            ['name' => 'Nigeria', 'iso_code' => 'NG', 'iso_code_3' => 'NGA', 'region' => 'Africa'],
            ['name' => 'Norway', 'iso_code' => 'NO', 'iso_code_3' => 'NOR', 'region' => 'Europe'],
            ['name' => 'Pakistan', 'iso_code' => 'PK', 'iso_code_3' => 'PAK', 'region' => 'Asia'],
            ['name' => 'Peru', 'iso_code' => 'PE', 'iso_code_3' => 'PER', 'region' => 'Americas'],
            ['name' => 'Philippines', 'iso_code' => 'PH', 'iso_code_3' => 'PHL', 'region' => 'Asia'],
            ['name' => 'Poland', 'iso_code' => 'PL', 'iso_code_3' => 'POL', 'region' => 'Europe'],
            ['name' => 'Portugal', 'iso_code' => 'PT', 'iso_code_3' => 'PRT', 'region' => 'Europe'],
            ['name' => 'Qatar', 'iso_code' => 'QA', 'iso_code_3' => 'QAT', 'region' => 'Asia'],
            ['name' => 'Romania', 'iso_code' => 'RO', 'iso_code_3' => 'ROU', 'region' => 'Europe'],
            ['name' => 'Russia', 'iso_code' => 'RU', 'iso_code_3' => 'RUS', 'region' => 'Europe'],
            ['name' => 'Saudi Arabia', 'iso_code' => 'SA', 'iso_code_3' => 'SAU', 'region' => 'Asia'],
            ['name' => 'Singapore', 'iso_code' => 'SG', 'iso_code_3' => 'SGP', 'region' => 'Asia', 'currency_code' => 'SGD', 'language' => 'English'],
            ['name' => 'South Africa', 'iso_code' => 'ZA', 'iso_code_3' => 'ZAF', 'region' => 'Africa'],
            ['name' => 'South Korea', 'iso_code' => 'KR', 'iso_code_3' => 'KOR', 'region' => 'Asia'],
            ['name' => 'Spain', 'iso_code' => 'ES', 'iso_code_3' => 'ESP', 'region' => 'Europe'],
            ['name' => 'Sweden', 'iso_code' => 'SE', 'iso_code_3' => 'SWE', 'region' => 'Europe'],
            ['name' => 'Switzerland', 'iso_code' => 'CH', 'iso_code_3' => 'CHE', 'region' => 'Europe'],
            ['name' => 'Taiwan', 'iso_code' => 'TW', 'iso_code_3' => 'TWN', 'region' => 'Asia'],
            ['name' => 'Thailand', 'iso_code' => 'TH', 'iso_code_3' => 'THA', 'region' => 'Asia'],
            ['name' => 'Turkey', 'iso_code' => 'TR', 'iso_code_3' => 'TUR', 'region' => 'Europe'],
            ['name' => 'Ukraine', 'iso_code' => 'UA', 'iso_code_3' => 'UKR', 'region' => 'Europe'],
            ['name' => 'United Arab Emirates', 'iso_code' => 'AE', 'iso_code_3' => 'ARE', 'region' => 'Asia'],
            ['name' => 'United Kingdom', 'iso_code' => 'GB', 'iso_code_3' => 'GBR', 'region' => 'Europe', 'currency_code' => 'GBP', 'language' => 'English'],
            ['name' => 'United States', 'iso_code' => 'US', 'iso_code_3' => 'USA', 'region' => 'Americas', 'currency_code' => 'USD', 'language' => 'English'],
            ['name' => 'Vietnam', 'iso_code' => 'VN', 'iso_code_3' => 'VNM', 'region' => 'Asia'],
        ];
    }
}
