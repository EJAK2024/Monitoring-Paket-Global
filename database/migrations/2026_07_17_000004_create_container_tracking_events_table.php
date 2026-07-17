<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('container_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('container_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->string('location')->nullable();
            $table->foreignId('vessel_id')->nullable()->constrained()->nullOnDelete();
            $table->string('remarks')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['container_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('container_tracking_events');
    }
};
