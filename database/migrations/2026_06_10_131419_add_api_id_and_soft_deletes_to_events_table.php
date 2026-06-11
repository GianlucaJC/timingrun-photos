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
        Schema::table('events', function (Blueprint $table) {
            // Aggiungiamo un campo per memorizzare l'ID univoco dell'evento proveniente dall'API.
            // Questo sarà la nostra chiave per la sincronizzazione.
            $table->unsignedBigInteger('api_id')->unique()->nullable()->after('id');

            // Aggiungiamo il supporto per il soft delete, per "nascondere" gli eventi
            // che non sono più pubblicati sull'API senza cancellarli fisicamente.
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('api_id');
            $table->dropSoftDeletes();
        });
    }
};
