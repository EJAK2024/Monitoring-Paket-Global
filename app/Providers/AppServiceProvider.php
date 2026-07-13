<?php

namespace App\Providers;

use App\Contracts\CountryDataProviderInterface;
use App\Contracts\CurrencyTrendProviderInterface;
use App\Contracts\EconomicDataProviderInterface;
use App\Contracts\ExchangeRateProviderInterface;
use App\Contracts\NewsProviderInterface;
use App\Contracts\PortRepositoryInterface;
use App\Contracts\RiskEngineInterface;
use App\Contracts\SentimentAnalyzerInterface;
use App\Contracts\VesselTrackingInterface;
use App\Contracts\WeatherServiceInterface;
use App\Services\ExchangeRateService;
use App\Services\GNewsService;
use App\Services\OpenExchangeRatesService;
use App\Services\OpenMeteoService;
use App\Services\RestCountriesService;
use App\Services\RiskService;
use App\Services\SentimentService;
use App\Services\VesselFinderService;
use App\Services\WorldBankService;
use App\Services\WorldPortIndexService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WeatherServiceInterface::class, OpenMeteoService::class);
        $this->app->bind(EconomicDataProviderInterface::class, WorldBankService::class);
        $this->app->bind(CountryDataProviderInterface::class, RestCountriesService::class);
        $this->app->bind(NewsProviderInterface::class, GNewsService::class);
        $this->app->bind(ExchangeRateProviderInterface::class, ExchangeRateService::class);
        $this->app->bind(CurrencyTrendProviderInterface::class, OpenExchangeRatesService::class);
        $this->app->bind(SentimentAnalyzerInterface::class, SentimentService::class);
        $this->app->bind(VesselTrackingInterface::class, VesselFinderService::class);
        $this->app->bind(PortRepositoryInterface::class, WorldPortIndexService::class);
        $this->app->bind(RiskEngineInterface::class, RiskService::class);
    }

    public function boot(): void
    {
        //
    }
}
