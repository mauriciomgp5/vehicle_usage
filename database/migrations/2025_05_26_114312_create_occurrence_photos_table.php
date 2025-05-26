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
        Schema::create('occurrence_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('occurrence_id')->constrained('occurrences')->cascadeOnDelete();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('path');
            $table->string('mime_type');
            $table->bigInteger('size');
            $table->string('whatsapp_media_id')->nullable(); // ID da mídia no WhatsApp
            $table->string('caption')->nullable(); // Legenda da foto
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('occurrence_photos');
    }
};
