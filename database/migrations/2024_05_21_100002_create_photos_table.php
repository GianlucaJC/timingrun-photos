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
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->comment('Photographer ID')->constrained('users')->onDelete('cascade');
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->string('original_path');
            $table->string('watermarked_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->timestamp('taken_at')->nullable()->comment('From EXIF data');
            $table->string('status')->default('pending')->comment('pending, processing, published, error');
            $table->string('bib_number_detected')->nullable()->comment('From OCR');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};