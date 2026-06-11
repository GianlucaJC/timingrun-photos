<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_bibs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_id')->constrained()->onDelete('cascade');
            $table->string('bib_number');
            $table->float('confidence')->nullable(); // Punteggio di confidenza restituito dall'API
            $table->timestamps();

            $table->unique(['photo_id', 'bib_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_bibs');
    }
};
