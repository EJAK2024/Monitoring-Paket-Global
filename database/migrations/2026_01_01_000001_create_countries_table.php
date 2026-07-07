<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->char('iso_code', 2)->unique();
            $table->char('iso_code_3', 3)->nullable()->unique();
            $table->string('currency_code', 3)->nullable();
            $table->string('region')->nullable();
            $table->string('language')->nullable();
            $table->decimal('gdp', 20, 2)->nullable();
            $table->decimal('inflation', 5, 2)->nullable();
            $table->bigInteger('population')->nullable();
            $table->decimal('exports', 20, 2)->nullable();
            $table->decimal('imports', 20, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
