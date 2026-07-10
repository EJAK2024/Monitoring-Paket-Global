<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vessels', function (Blueprint $table) {
            $table->id();
            $table->string('mmsi', 15)->unique();
            $table->string('imo', 10)->nullable()->index();
            $table->string('name');
            $table->string('vessel_type')->default('container');
            $table->string('flag_country')->nullable();
            $table->string('flag_code', 5)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('speed', 6, 2)->nullable()->default(0);
            $table->decimal('course', 6, 2)->nullable()->default(0);
            $table->decimal('heading', 6, 2)->nullable()->default(0);
            $table->string('destination')->nullable();
            $table->string('nav_status')->nullable()->default('Unknown');
            $table->boolean('is_tracked')->default(false);
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();

            $table->index('vessel_type');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessels');
    }
};
