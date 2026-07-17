<?php

namespace App\Services;

use App\Models\RiskScore;
use App\Models\Supplier;
use App\Models\SupplierRiskScore;

class SupplierRiskService
{
    public function calculate(Supplier $supplier): array
    {
        $countryRisk = $this->countryRiskScore($supplier);
        $deliveryRisk = $this->deliveryRisk($supplier);
        $qualityRisk = $this->qualityRisk($supplier);
        $complianceRisk = $this->complianceRisk($supplier);
        $financialRisk = $this->financialRisk($supplier);

        $total = (int) round(
            $countryRisk * 0.40 +
            $deliveryRisk * 0.20 +
            $qualityRisk * 0.15 +
            $complianceRisk * 0.15 +
            $financialRisk * 0.10
        );

        $level = $total <= 30 ? 'low' : ($total <= 60 ? 'medium' : 'high');

        return [
            'supplier_id' => $supplier->id,
            'supplier' => $supplier->only(['id', 'name', 'category', 'status']),
            'country' => $supplier->country->only(['id', 'name', 'iso_code']),
            'country_risk_score' => $countryRisk,
            'delivery_risk' => $deliveryRisk,
            'quality_risk' => $qualityRisk,
            'compliance_risk' => $complianceRisk,
            'financial_risk' => $financialRisk,
            'total_score' => $total,
            'risk_level' => $level,
            'calculated_at' => now()->toDateTimeString(),
        ];
    }

    protected function countryRiskScore(Supplier $supplier): int
    {
        $latest = RiskScore::where('country_id', $supplier->country_id)
            ->latest('calculated_at')
            ->first();

        return $latest ? (int) round($latest->total_score) : 30;
    }

    protected function deliveryRisk(Supplier $supplier): int
    {
        $otd = $supplier->on_time_delivery_pct;
        $leadTime = $supplier->lead_time_days;

        $otdScore = match (true) {
            $otd === null => 40,
            $otd >= 98 => 10,
            $otd >= 95 => 25,
            $otd >= 90 => 50,
            $otd >= 80 => 70,
            default => 90,
        };

        $leadScore = match (true) {
            $leadTime === null => 30,
            $leadTime <= 7 => 10,
            $leadTime <= 14 => 25,
            $leadTime <= 30 => 50,
            $leadTime <= 60 => 70,
            default => 90,
        };

        return (int) round($otdScore * 0.6 + $leadScore * 0.4);
    }

    protected function qualityRisk(Supplier $supplier): int
    {
        $rating = $supplier->quality_rating;

        return match (true) {
            $rating === null => 40,
            $rating >= 95 => 10,
            $rating >= 85 => 25,
            $rating >= 70 => 50,
            $rating >= 50 => 70,
            default => 90,
        };
    }

    protected function complianceRisk(Supplier $supplier): int
    {
        $risk = 50;

        if ($supplier->certification) {
            $cert = strtolower($supplier->certification);
            if (str_contains($cert, 'iso')) {
                $risk -= 20;
            }
            if (str_contains($cert, '9001') || str_contains($cert, '14001')) {
                $risk -= 10;
            }
        }

        if ($supplier->status === 'active') {
            $risk -= 10;
        } elseif ($supplier->status === 'suspended') {
            $risk += 30;
        }

        return (int) max(0, min(100, $risk));
    }

    protected function financialRisk(Supplier $supplier): int
    {
        $score = $supplier->reliability_score;

        return match (true) {
            $score === null => 40,
            $score >= 90 => 10,
            $score >= 75 => 30,
            $score >= 60 => 50,
            $score >= 40 => 70,
            default => 90,
        };
    }

    public function historicalSeries(Supplier $supplier, int $days = 90): array
    {
        $scores = SupplierRiskScore::where('supplier_id', $supplier->id)
            ->where('calculated_at', '>=', now()->subDays($days))
            ->orderBy('calculated_at')
            ->get();

        if ($scores->isEmpty()) {
            $current = $this->calculate($supplier);

            return [
                [
                    'date' => now()->format('Y-m-d'),
                    'total_score' => $current['total_score'],
                    'delivery_risk' => $current['delivery_risk'],
                    'quality_risk' => $current['quality_risk'],
                    'compliance_risk' => $current['compliance_risk'],
                    'financial_risk' => $current['financial_risk'],
                    'country_risk_score' => $current['country_risk_score'],
                ],
            ];
        }

        return $scores->map(fn ($s) => [
            'date' => $s->calculated_at->format('Y-m-d'),
            'total_score' => (int) round($s->total_score),
            'delivery_risk' => (int) round($s->delivery_risk ?? 0),
            'quality_risk' => (int) round($s->quality_risk ?? 0),
            'compliance_risk' => (int) round($s->compliance_risk ?? 0),
            'financial_risk' => (int) round($s->financial_risk ?? 0),
            'country_risk_score' => (int) round($s->country_risk_score ?? 0),
        ])->toArray();
    }
}
