<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_usage_id')->constrained('vehicle_usages')->cascadeOnDelete();
            $table->text('description');
            $table->enum('type', ['incident', 'maintenance', 'damage', 'other'])->default('incident');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('occurrences');
    }
};
