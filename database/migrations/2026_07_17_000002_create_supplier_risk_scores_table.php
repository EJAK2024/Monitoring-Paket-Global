<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_risk_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->decimal('country_risk_score', 5, 2)->nullable();
            $table->decimal('delivery_risk', 5, 2)->nullable();
            $table->decimal('quality_risk', 5, 2)->nullable();
            $table->decimal('compliance_risk', 5, 2)->nullable();
            $table->decimal('financial_risk', 5, 2)->nullable();
            $table->decimal('total_score', 5, 2);
            $table->string('risk_level', 10);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_risk_scores');
    }
};
