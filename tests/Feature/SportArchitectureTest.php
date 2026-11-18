<?php
// Proves the sport architecture is really modular by adding a second sport
// that only exists inside this test, following docs/adding-a-sport.md.

use App\Models\Tournament\Prediction;
use App\Models\Tournament\Tournament;
use App\Models\Tournament\TournamentMatch;
use App\Models\User;
use App\Sports\Football\FootballRules;
use App\Sports\SportRegistry;
use App\Tournaments\ResultService;
use App\Tournaments\TournamentCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\TestBasketballRules;

uses(RefreshDatabase::class);

beforeEach(function () {
    $registry = new SportRegistry();
    $registry->register(new FootballRules());
    $registry->register(new TestBasketballRules());

    $this->app->instance(SportRegistry::class, $registry);
});

it('offers every registered sport to the organiser', function () {
    expect(app(SportRegistry::class)->options())
        ->toHaveKey('football')
        ->toHaveKey('basketball');
});

it('creates and plays a tournament with the new sport', function () {
    $tournament = app(TournamentCreator::class)->create([
        'name' => 'Basketbaltoernooi',
        'sport' => 'basketball',
        'team_count' => 8,
        'starts_at' => now()->addWeek()->toDateTimeString(),
    ]);

    expect($tournament->rules())->toBeInstanceOf(TestBasketballRules::class)
        ->and($tournament->matches()->count())->toBe(15);
});

it('uses the scoring rules of the chosen sport', function () {
    $tournament = app(TournamentCreator::class)->create([
        'name' => 'Basketbaltoernooi',
        'sport' => 'basketball',
        'team_count' => 8,
        'starts_at' => now()->addWeek()->toDateTimeString(),
    ]);

    $match = $tournament->groupMatches()->first();
    $user = User::factory()->create();

    Prediction::create([
        'user_id' => $user->id,
        'tournament_match_id' => $match->id,
        'home_score' => 80,
        'away_score' => 70,
    ]);

    app(ResultService::class)->store($match, ['home_score' => 80, 'away_score' => 70]);

    // One point for the outcome plus five for the exact score.
    expect(Prediction::where('user_id', $user->id)->value('points'))->toBe(6);
});

it('applies the draw rule of the sport to group matches too', function () {
    $tournament = app(TournamentCreator::class)->create([
        'name' => 'Basketbaltoernooi',
        'sport' => 'basketball',
        'team_count' => 8,
        'starts_at' => now()->addWeek()->toDateTimeString(),
    ]);

    // Football allows a draw in the group stage, basketball does not.
    app(ResultService::class)->store(
        $tournament->groupMatches()->first(),
        ['home_score' => 70, 'away_score' => 70]
    );
})->throws(ValidationException::class);

it('keeps football working exactly as before', function () {
    $tournament = app(TournamentCreator::class)->create([
        'name' => 'Voetbaltoernooi',
        'sport' => 'football',
        'team_count' => 8,
        'starts_at' => now()->addWeek()->toDateTimeString(),
    ]);

    $match = $tournament->groupMatches()->first();
    $user = User::factory()->create();

    Prediction::create([
        'user_id' => $user->id,
        'tournament_match_id' => $match->id,
        'home_score' => 2,
        'away_score' => 1,
    ]);

    app(ResultService::class)->store($match, ['home_score' => 2, 'away_score' => 1]);

    expect(Prediction::where('user_id', $user->id)->value('points'))->toBe(4);
});

it('does not let a tournament use an unknown sport', function () {
    app(TournamentCreator::class)->create([
        'name' => 'Onbekend',
        'sport' => 'korfbal',
        'team_count' => 8,
        'starts_at' => now()->addWeek()->toDateTimeString(),
    ])->rules();
})->throws(InvalidArgumentException::class);

it('keeps the general tournament logic free of football references', function () {
    $generic = [
        app_path('Tournaments/TournamentGenerator.php'),
        app_path('Tournaments/StructureResolver.php'),
        app_path('Tournaments/ScoreCalculator.php'),
        app_path('Tournaments/KnockoutProgression.php'),
        app_path('Tournaments/Ranking.php'),
        app_path('Models/Tournament/TournamentMatch.php'),
    ];

    foreach ($generic as $file) {
        expect(strtolower(file_get_contents($file)))
            ->not->toContain('football')
            ->not->toContain('penalty_winner_team_id === ');
    }
});
