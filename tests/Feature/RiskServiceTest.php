<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\NegativeWord;
use App\Models\NewsCache;
use App\Models\PositiveWord;
use App\Services\RiskService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RiskServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        PositiveWord::create(['word' => 'growth']);
        PositiveWord::create(['word' => 'stable']);
        PositiveWord::create(['word' => 'recovery']);

        NegativeWord::create(['word' => 'decline', 'category' => 'negative']);
        NegativeWord::create(['word' => 'sanction', 'category' => 'crisis']);
        NegativeWord::create(['word' => 'crisis', 'category' => 'crisis']);
        NegativeWord::create(['word' => 'war', 'category' => 'crisis']);
        NegativeWord::create(['word' => 'inflation', 'category' => 'negative']);

        $this->country = Country::create([
            'name' => 'Testland',
            'iso_code' => 'TL',
            'iso_code_3' => 'TST',
            'currency_code' => 'TST',
            'region' => 'Test Region',
            'inflation' => 4.5,
        ]);
    }

    public function test_weather_score_clear_sky(): void
    {
        Http::fake([
            'geocoding-api.open-meteo.com/*' => Http::response([
                'results' => [['latitude' => -6.2, 'longitude' => 106.8, 'name' => 'Testland']],
            ]),
            'api.open-meteo.com/*' => Http::response([
                'current' => [
                    'temperature_2m' => 25,
                    'precipitation' => 0,
                    'wind_speed_10m' => 5,
                    'weather_code' => 0,
                ],
            ]),
        ]);

        $risk = app(RiskService::class);
        $result = $risk->calculate($this->country);

        $this->assertEquals(10, $result['weather_risk']);
    }

    public function test_weather_score_storm_with_penalties(): void
    {
        Http::fake([
            'geocoding-api.open-meteo.com/*' => Http::response([
                'results' => [['latitude' => -6.2, 'longitude' => 106.8, 'name' => 'Testland']],
            ]),
            'api.open-meteo.com/*' => Http::response([
                'current' => [
                    'temperature_2m' => -2,
                    'precipitation' => 20,
                    'wind_speed_10m' => 60,
                    'weather_code' => 95,
                ],
            ]),
        ]);

        $risk = app(RiskService::class);
        $result = $risk->calculate($this->country);

        $this->assertEquals(100, $result['weather_risk']);
    }

    public function test_inflation_score_range_based(): void
    {
        Http::fake(['*' => Http::response(null, 500)]);

        $risk = app(RiskService::class);
        $result = $risk->calculate($this->country);

        $this->assertEquals(50, $result['inflation_risk']);
    }

    public function test_news_score_weighted_average(): void
    {
        NewsCache::create([
            'country_id' => $this->country->id,
            'title' => 'Economy shows growth and stability',
            'description' => 'Positive economic recovery continues',
            'source' => 'Test',
            'url' => 'https://test.com',
            'published_at' => now(),
            'sentiment' => 'positive',
        ]);

        NewsCache::create([
            'country_id' => $this->country->id,
            'title' => 'Trade volumes decline this quarter',
            'description' => 'Export numbers show steady decline',
            'source' => 'Test',
            'url' => 'https://test.com',
            'published_at' => now(),
            'sentiment' => 'negative',
        ]);

        NewsCache::create([
            'country_id' => $this->country->id,
            'title' => 'New sanctions and crisis loom',
            'description' => 'War risk escalates in the region',
            'source' => 'Test',
            'url' => 'https://test.com',
            'published_at' => now(),
            'sentiment' => 'negative',
        ]);

        Http::fake(['*' => Http::response(null, 500)]);

        $risk = app(RiskService::class);
        $result = $risk->calculate($this->country);

        $this->assertArrayHasKey('news_sentiment_risk', $result);
        $this->assertGreaterThan(20, $result['news_sentiment_risk']);
    }

    public function test_total_score_calculation(): void
    {
        Http::fake([
            'geocoding-api.open-meteo.com/*' => Http::response([
                'results' => [['latitude' => -6.2, 'longitude' => 106.8, 'name' => 'Testland']],
            ]),
            'api.open-meteo.com/*' => Http::response([
                'current' => [
                    'temperature_2m' => 25,
                    'precipitation' => 0,
                    'wind_speed_10m' => 5,
                    'weather_code' => 0,
                ],
            ]),
            '*' => Http::response(null, 500),
        ]);

        $risk = app(RiskService::class);
        $result = $risk->calculate($this->country);

        $this->assertArrayHasKey('total_score', $result);
        $this->assertArrayHasKey('risk_level', $result);
        $this->assertIsInt($result['total_score']);
        $this->assertContains($result['risk_level'], ['low', 'medium', 'high']);
    }

    public function test_total_score_weighted_average(): void
    {
        $weather = 10;
        $inflation = 50;
        $news = 40;
        $fx = 30;

        $expected = (int) round(
            $weather * 0.25 + $inflation * 0.25 + $news * 0.25 + $fx * 0.25
        );

        $this->assertEquals(33, $expected);
    }

    public function test_risk_level_classification(): void
    {
        $this->assertEquals('low', $this->classify(0));
        $this->assertEquals('low', $this->classify(30));
        $this->assertEquals('medium', $this->classify(31));
        $this->assertEquals('medium', $this->classify(60));
        $this->assertEquals('high', $this->classify(61));
        $this->assertEquals('high', $this->classify(100));
    }

    private function classify(int $score): string
    {
        return $score <= 30 ? 'low' : ($score <= 60 ? 'medium' : 'high');
    }
}
