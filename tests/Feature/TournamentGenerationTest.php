<?php
// Tests for the tournament generator: group layout, bracket size and validation.

use App\Enums\Tournament\MatchRound;
use App\Models\Tournament\Tournament;
use App\Tournaments\StructureResolver;
use App\Tournaments\TournamentCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function createTournament(int $teamCount): Tournament
{
    return app(TournamentCreator::class)->create([
        'name' => "Toernooi {$teamCount}",
        'sport' => 'football',
        'team_count' => $teamCount,
        'starts_at' => now()->addWeek()->toDateTimeString(),
    ]);
}

it('builds the expected layout for the common team counts', function (
    int $teams,
    int $groups,
    int $groupSize,
    int $knockoutSize,
    int $groupMatches,
    int $knockoutMatches
) {
    $tournament = createTournament($teams);

    expect($tournament->group_count)->toBe($groups)
        ->and($tournament->group_size)->toBe($groupSize)
        ->and($tournament->knockout_size)->toBe($knockoutSize)
        ->and($tournament->teams()->count())->toBe($teams)
        ->and($tournament->groups()->count())->toBe($groups)
        ->and($tournament->groupMatches()->count())->toBe($groupMatches)
        ->and($tournament->matches()->where('round', '!=', MatchRound::Group->value)->count())
            ->toBe($knockoutMatches);
})->with([
    '8 teams' => [8, 2, 4, 4, 12, 3],
    '16 teams' => [16, 4, 4, 8, 24, 7],
    '32 teams' => [32, 8, 4, 16, 48, 15],
    '64 teams' => [64, 16, 4, 32, 96, 31],
]);

it('gives every team a group and every group the right number of teams', function () {
    $tournament = createTournament(16);

    expect($tournament->teams()->whereNull('tournament_group_id')->count())->toBe(0);

    foreach ($tournament->groups as $group) {
        expect($group->teams()->count())->toBe(4);
    }
});

it('creates a bracket that ends in a single final', function () {
    $tournament = createTournament(32);

    $rounds = $tournament->matches()
        ->where('round', '!=', MatchRound::Group->value)
        ->get()
        ->groupBy(fn ($match) => $match->round->value)
        ->map->count();

    expect($rounds['round_of_16'])->toBe(8)
        ->and($rounds['quarter_final'])->toBe(4)
        ->and($rounds['semi_final'])->toBe(2)
        ->and($rounds['final'])->toBe(1);
});

it('links every knockout match to the next round except the final', function () {
    $tournament = createTournament(16);

    $knockout = $tournament->matches()->where('round', '!=', MatchRound::Group->value)->get();

    $final = $knockout->firstWhere('round', MatchRound::Final);
    expect($final->next_match_id)->toBeNull();

    $others = $knockout->where('round', '!=', MatchRound::Final);
    expect($others->whereNull('next_match_id')->count())->toBe(0);
});

it('labels the first knockout round with the group positions', function () {
    $tournament = createTournament(8);

    $semis = $tournament->matches()
        ->where('round', MatchRound::SemiFinal->value)
        ->orderBy('bracket_position')
        ->get();

    expect($semis->first()->home_source)->toBe('Winnaar groep A')
        ->and($semis->first()->away_source)->toBe('Nummer 2 groep B')
        ->and($semis->last()->home_source)->toBe('Winnaar groep B')
        ->and($semis->last()->away_source)->toBe('Nummer 2 groep A');
});

it('refuses fewer than 8 teams', function () {
    createTournament(7);
})->throws(ValidationException::class);

it('refuses more than 64 teams', function () {
    createTournament(65);
})->throws(ValidationException::class);

it('refuses a team count that cannot be split into clean groups', function () {
    createTournament(13);
})->throws(ValidationException::class);

it('lists the team counts an organiser can pick', function () {
    $counts = app(StructureResolver::class)->validTeamCounts();

    expect($counts)->toContain(8, 16, 32, 64)
        ->and(min($counts))->toBeGreaterThanOrEqual(8)
        ->and(max($counts))->toBeLessThanOrEqual(64);
});
