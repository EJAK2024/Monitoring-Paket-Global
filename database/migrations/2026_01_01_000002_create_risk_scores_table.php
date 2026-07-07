<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->decimal('weather_risk', 5, 2)->nullable();
            $table->decimal('inflation_risk', 5, 2)->nullable();
            $table->decimal('news_sentiment_risk', 5, 2)->nullable();
            $table->decimal('currency_risk', 5, 2)->nullable();
            $table->decimal('total_score', 5, 2);
            $table->string('risk_level', 10); // low, medium, high
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_scores');
    }
};
