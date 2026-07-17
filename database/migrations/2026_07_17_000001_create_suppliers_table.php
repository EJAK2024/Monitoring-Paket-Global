<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('category')->nullable();
            $table->unsignedTinyInteger('reliability_score')->nullable();
            $table->decimal('on_time_delivery_pct', 5, 2)->nullable();
            $table->unsignedTinyInteger('quality_rating')->nullable();
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->string('certification')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
