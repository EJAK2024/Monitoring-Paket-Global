<?php

namespace Tests\Feature;

use App\Services\WorldBankService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WorldBankServiceTest extends TestCase
{
    public function test_get_country_data_returns_null_on_failure(): void
    {
        Http::fake(['*' => Http::response(null, 500)]);

        $service = new WorldBankService;
        $result = $service->getCountryData('XX');

        $this->assertNull($result);
    }

    public function test_get_country_data_returns_merged_info_and_indicators(): void
    {
        Http::fake([
            'api.worldbank.org/v2/country/US/indicator/NY.GDP.MKTP.CD*' => Http::response([
                [['page' => 1]],
                [['value' => '27360000000000']],
            ]),
            'api.worldbank.org/v2/country/US/indicator/FP.CPI.TOTL.ZG*' => Http::response([
                [['page' => 1]],
                [['value' => '2.9']],
            ]),
            'api.worldbank.org/v2/country/US/indicator/SP.POP.TOTL*' => Http::response([
                [['page' => 1]],
                [['value' => '335000000']],
            ]),
            'api.worldbank.org/v2/country/US/indicator/NE.EXP.GNFS.CD*' => Http::response([
                [['page' => 1]],
                [['value' => '2020000000000']],
            ]),
            'api.worldbank.org/v2/country/US/indicator/NE.IMP.GNFS.CD*' => Http::response([
                [['page' => 1]],
                [['value' => '3170000000000']],
            ]),
            '*' => Http::response([
                [['page' => 1, 'pages' => 1, 'per_page' => 50, 'total' => 1]],
                [['name' => 'United States', 'region' => ['value' => 'Americas'], 'capitalCity' => 'Washington DC', 'latitude' => '38.89', 'longitude' => '-77.03']],
            ]),
        ]);

        $service = new WorldBankService;
        $result = $service->getCountryData('US');

        $this->assertNotNull($result);
        $this->assertEquals('United States', $result['name']);
        $this->assertEquals('Americas', $result['region']);
        $this->assertEquals(27360.0, $result['gdp']);
        $this->assertEquals(2.9, $result['inflation']);
        $this->assertEquals(335000000.0, $result['population']);
        $this->assertEquals(2020.0, $result['exports']);
        $this->assertEquals(3170.0, $result['imports']);
    }

    public function test_get_country_data_returns_null_for_empty_response(): void
    {
        Http::fake(['*' => Http::response([[['page' => 1]], []])]);

        $service = new WorldBankService;
        $result = $service->getCountryData('US');

        $this->assertNull($result);
    }

    public function test_handles_null_indicator_values_gracefully(): void
    {
        Http::fake([
            'api.worldbank.org/v2/country/XX/indicator/*' => Http::response([
                [['page' => 1]],
                [['value' => null]],
            ]),
            '*' => Http::response([
                [['page' => 1]],
                [['name' => 'Testland', 'region' => ['value' => 'Test'], 'capitalCity' => 'Test City', 'latitude' => '0', 'longitude' => '0']],
            ]),
        ]);

        $service = new WorldBankService;
        $result = $service->getCountryData('XX');

        $this->assertNotNull($result);
        $this->assertEquals('Testland', $result['name']);
        $this->assertNull($result['gdp']);
    }
}
