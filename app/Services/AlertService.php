<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Container;
use App\Models\Country;
use App\Models\RiskScore;
use App\Models\SupplierRiskScore;
use Illuminate\Support\Facades\Log;

class AlertService
{
    public function generateAll(): int
    {
        $count = 0;

        $count += $this->checkCountryRisk();
        $count += $this->checkSupplierRisk();
        $count += $this->checkContainerDelays();
        $count += $this->checkWeatherStorms();

        Log::info("AlertService: {$count} new alerts generated.");

        return $count;
    }

    public function checkCountryRisk(): int
    {
        $count = 0;
        $recent = now()->subHours(24);

        $highRisks = RiskScore::with('country')
            ->where('total_score', '>', 60)
            ->where('calculated_at', '>=', $recent)
            ->get();

        foreach ($highRisks as $risk) {
            $exists = Alert::where('type', 'country_risk_high')
                ->where('source_type', RiskScore::class)
                ->where('source_id', $risk->id)
                ->exists();

            if ($exists) {
                continue;
            }

            $level = $risk->total_score > 80 ? 'critical' : 'high';
            $country = $risk->country;

            Alert::create([
                'type' => 'country_risk_high',
                'title' => "High Risk: {$country->name}",
                'message' => "Country risk score reached {$risk->total_score}% (Risk Level: {$risk->risk_level}). Weather: {$risk->weather_risk}, Inflation: {$risk->inflation_risk}, News: {$risk->news_sentiment_risk}, Currency: {$risk->currency_risk}.",
                'severity' => $level,
                'context' => [
                    'country_id' => $country->id,
                    'country_name' => $country->name,
                    'total_score' => $risk->total_score,
                    'risk_level' => $risk->risk_level,
                    'components' => [
                        'weather' => $risk->weather_risk,
                        'inflation' => $risk->inflation_risk,
                        'news' => $risk->news_sentiment_risk,
                        'currency' => $risk->currency_risk,
                    ],
                ],
                'source_type' => RiskScore::class,
                'source_id' => $risk->id,
            ]);

            $count++;
        }

        return $count;
    }

    public function checkSupplierRisk(): int
    {
        $count = 0;
        $recent = now()->subHours(24);

        $highRisks = SupplierRiskScore::with('supplier.country')
            ->where('total_score', '>', 60)
            ->where('calculated_at', '>=', $recent)
            ->get();

        foreach ($highRisks as $risk) {
            $exists = Alert::where('type', 'supplier_risk_high')
                ->where('source_type', SupplierRiskScore::class)
                ->where('source_id', $risk->id)
                ->exists();

            if ($exists) {
                continue;
            }

            $supplier = $risk->supplier;

            Alert::create([
                'type' => 'supplier_risk_high',
                'title' => "Supplier Risk: {$supplier->name}",
                'message' => "Supplier risk score reached {$risk->total_score}%. Delivery: {$risk->delivery_risk}, Quality: {$risk->quality_risk}, Compliance: {$risk->compliance_risk}, Financial: {$risk->financial_risk}.",
                'severity' => 'high',
                'context' => [
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->name,
                    'total_score' => $risk->total_score,
                    'components' => [
                        'delivery' => $risk->delivery_risk,
                        'quality' => $risk->quality_risk,
                        'compliance' => $risk->compliance_risk,
                        'financial' => $risk->financial_risk,
                    ],
                ],
                'source_type' => SupplierRiskScore::class,
                'source_id' => $risk->id,
            ]);

            $count++;
        }

        return $count;
    }

    public function checkContainerDelays(): int
    {
        $count = 0;

        $delayed = Container::where('status', 'delayed')
            ->whereDoesntHave('trackingEvents', function ($q) {
                $q->where('event_type', 'resolved')
                    ->where('occurred_at', '>=', now()->subDays(7));
            })
            ->get();

        foreach ($delayed as $container) {
            $exists = Alert::where('type', 'container_delayed')
                ->where('source_type', Container::class)
                ->where('source_id', $container->id)
                ->where('created_at', '>=', now()->subHours(24))
                ->exists();

            if ($exists) {
                continue;
            }

            $delayEvent = $container->trackingEvents()
                ->where('event_type', 'delayed')
                ->latest('occurred_at')
                ->first();

            Alert::create([
                'type' => 'container_delayed',
                'title' => "Container Delayed: {$container->container_id}",
                'message' => "Container {$container->container_id} ({$container->origin} → {$container->destination}) is delayed.".($delayEvent?->remarks ? " Reason: {$delayEvent->remarks}" : ''),
                'severity' => 'medium',
                'context' => [
                    'container_id' => $container->container_id,
                    'origin' => $container->origin,
                    'destination' => $container->destination,
                    'remarks' => $delayEvent?->remarks,
                ],
                'source_type' => Container::class,
                'source_id' => $container->id,
            ]);

            $count++;
        }

        return $count;
    }

    public function checkWeatherStorms(): int
    {
        $count = 0;
        $recent = now()->subHours(24);

        $stormRisks = RiskScore::with('country')
            ->where('weather_risk', '>', 70)
            ->where('calculated_at', '>=', $recent)
            ->get();

        foreach ($stormRisks as $risk) {
            $exists = Alert::where('type', 'weather_storm')
                ->where('source_type', Country::class)
                ->where('source_id', $risk->country_id)
                ->where('created_at', '>=', now()->subHours(6))
                ->exists();

            if ($exists) {
                continue;
            }

            $country = $risk->country;

            Alert::create([
                'type' => 'weather_storm',
                'title' => "Storm Warning: {$country->name}",
                'message' => "Severe weather detected in {$country->name}. Storm risk score: {$risk->weather_risk}%.",
                'severity' => 'high',
                'context' => [
                    'country_id' => $country->id,
                    'country_name' => $country->name,
                    'weather_risk' => $risk->weather_risk,
                ],
                'source_type' => Country::class,
                'source_id' => $country->id,
            ]);

            $count++;
        }

        return $count;
    }

    public function unreadCount(): int
    {
        return Alert::unread()->count();
    }

    public function recentAlerts(int $limit = 20)
    {
        return Alert::with('source')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function markAsRead(int $id): bool
    {
        $alert = Alert::find($id);

        if (! $alert) {
            return false;
        }

        return $alert->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAllAsRead(): int
    {
        return Alert::unread()->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function dismiss(int $id): bool
    {
        return (bool) Alert::where('id', $id)->delete();
    }
}
