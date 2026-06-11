<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crea alcuni utenti fotografi di esempio.
        // La password per tutti è 'password'.
        \App\Models\User::factory()->create([
            'name' => 'Mario Rossi',
            'email' => 'mario.rossi@example.com',
        ]);

        \App\Models\User::factory()->create([
            'name' => 'Anna Verdi',
            'email' => 'anna.verdi@example.com',
        ]);

        \App\Models\User::factory()->create([
            'name' => 'Luca Bianchi',
            'email' => 'luca.bianchi@example.com',
        ]);

        \App\Models\User::factory()->create([
            'name' => 'Giulia Neri',
            'email' => 'giulia.neri@example.com',
        ]);

        $this->call([
            EventSeeder::class,
        ]);
    }
}
