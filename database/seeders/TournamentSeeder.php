<?php

namespace Database\Seeders;

use App\Models\Tournament\Tournament;
use App\Tournaments\TournamentCreator;
use Illuminate\Database\Seeder;

class TournamentSeeder extends Seeder
{
    public function run(): void
    {
        $name = 'Zijpalm demo voetbalpoule';

        if (Tournament::query()->where('name', $name)->exists()) {
            return;
        }

        app(TournamentCreator::class)->create([
            'name' => $name,
            'description' => 'Voorbeeldtoernooi om de voetbalpoule te bekijken.',
            'sport' => 'football',
            'team_count' => 8,
            'starts_at' => now()->addMonths(2)->startOfDay()->toDateTimeString(),
        ], [
            'Nederland',
            'Duitsland',
            'Frankrijk',
            'Spanje',
            'Italië',
            'België',
            'Portugal',
            'Engeland',
        ]);
    }
}
