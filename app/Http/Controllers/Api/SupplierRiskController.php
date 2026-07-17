<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierRiskScore;
use App\Services\SupplierRiskService;
use Illuminate\Http\Request;

class SupplierRiskController extends Controller
{
    public function index(Request $request, SupplierRiskService $risk)
    {
        $query = SupplierRiskScore::with('supplier.country');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $stored = $query->latest('calculated_at')->get();

        if ($stored->isNotEmpty()) {
            return response()->json($stored);
        }

        $suppliers = Supplier::with('country')
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                $q->where('id', $request->supplier_id);
            })
            ->where('status', 'active')
            ->get();

        $risks = $suppliers->map(function (Supplier $supplier) use ($risk) {
            return $this->persist($supplier, $risk->calculate($supplier));
        });

        return response()->json($risks);
    }

    public function show(int $id, Request $request, SupplierRiskService $risk)
    {
        $supplier = Supplier::with('country')->findOrFail($id);

        $stored = SupplierRiskScore::where('supplier_id', $id)
            ->latest('calculated_at')
            ->first();

        if ($stored && $request->boolean('cached')) {
            return response()->json($stored->load('supplier.country'));
        }

        $data = $risk->calculate($supplier);
        $result = $this->persist($supplier, $data);

        return response()->json($result);
    }

    public function history(int $id, Request $request, SupplierRiskService $risk)
    {
        $supplier = Supplier::findOrFail($id);

        $series = $risk->historicalSeries($supplier, (int) $request->days ?: 90);

        return response()->json([
            'supplier' => $supplier->only(['id', 'name']),
            'series' => $series,
        ]);
    }

    private function persist(Supplier $supplier, array $data): array
    {
        $existing = SupplierRiskScore::where('supplier_id', $supplier->id)
            ->whereDate('calculated_at', now()->toDateString())
            ->first();

        $payload = [
            'country_risk_score' => $data['country_risk_score'],
            'delivery_risk' => $data['delivery_risk'],
            'quality_risk' => $data['quality_risk'],
            'compliance_risk' => $data['compliance_risk'],
            'financial_risk' => $data['financial_risk'],
            'total_score' => $data['total_score'],
            'risk_level' => $data['risk_level'],
            'calculated_at' => now(),
        ];

        if ($existing) {
            $existing->update($payload);

            return $existing->load('supplier.country')->toArray();
        }

        return SupplierRiskScore::create(
            array_merge(['supplier_id' => $supplier->id], $payload)
        )->load('supplier.country')->toArray();
    }
}
