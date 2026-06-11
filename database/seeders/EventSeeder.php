<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Event;
use Illuminate\Support\Str;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Event::create([
            'name' => 'Maratona di Roma',
            'slug' => Str::slug('Maratona di Roma'),
            'date' => '2024-03-17',
            'location' => 'Roma, IT'
        ]);

        Event::create([
            'name' => 'Milano Marathon',
            'slug' => Str::slug('Milano Marathon'),
            'date' => '2024-04-07',
            'location' => 'Milano, IT'
        ]);
    }
}

