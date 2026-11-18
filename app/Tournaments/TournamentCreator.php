<?php
// Creates a tournament and immediately generates the full schedule.

namespace App\Tournaments;

use App\Enums\Tournament\TournamentStatus;
use App\Models\Tournament\Tournament;
use Illuminate\Validation\ValidationException;

class TournamentCreator
{
    public function __construct(
        private readonly StructureResolver $resolver,
        private readonly TournamentGenerator $generator,
    ) {
    }

    /**
     * @param  array{name:string, description?:string|null, sport:string, team_count:int, starts_at:string}  $data
     * @param  array<string>  $teamNames
     */
    public function create(array $data, array $teamNames = []): Tournament
    {
        $structure = $this->resolver->resolve((int) $data['team_count']);

        if (! $structure) {
            throw ValidationException::withMessages([
                'team_count' => $this->errorMessage((int) $data['team_count']),
            ]);
        }

        $tournament = Tournament::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'sport' => $data['sport'],
            'status' => TournamentStatus::Open,
            'starts_at' => $data['starts_at'],
            ...$structure->toArray(),
        ]);

        return $this->generator->generate($tournament, $teamNames);
    }

    private function errorMessage(int $teamCount): string
    {
        if ($teamCount < StructureResolver::MIN_TEAMS) {
            return 'Een toernooi heeft minimaal '.StructureResolver::MIN_TEAMS.' teams nodig.';
        }

        if ($teamCount > StructureResolver::MAX_TEAMS) {
            return 'Een toernooi mag maximaal '.StructureResolver::MAX_TEAMS.' teams hebben.';
        }

        $valid = implode(', ', $this->resolver->validTeamCounts());

        return "Met {$teamCount} teams is geen sluitende poule-indeling te maken. Kies een van deze aantallen: {$valid}.";
    }
}
