<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('containers', function (Blueprint $table) {
            $table->id();
            $table->string('container_id', 15)->unique();
            $table->string('size')->default('20ft');
            $table->string('type')->default('dry');
            $table->string('status')->default('at_port');
            $table->string('current_location')->nullable();
            $table->foreignId('vessel_id')->nullable()->constrained()->nullOnDelete();
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->string('shipper')->nullable();
            $table->string('consignee')->nullable();
            $table->decimal('weight_kg', 10, 2)->nullable();
            $table->string('seal_number')->nullable();
            $table->timestamp('last_scanned_at')->nullable();
            $table->timestamp('estimated_arrival')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('containers');
    }
};
