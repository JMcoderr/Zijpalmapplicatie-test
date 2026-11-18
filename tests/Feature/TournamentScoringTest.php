<?php
// Tests for the scoring rules and for teams moving from the group stage into the bracket.

use App\Enums\Tournament\MatchRound;
use App\Models\Tournament\Prediction;
use App\Models\Tournament\Tournament;
use App\Models\Tournament\TournamentMatch;
use App\Models\User;
use App\Tournaments\ResultService;
use App\Tournaments\ScoreCalculator;
use App\Tournaments\TournamentCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function tournamentWithTeams(int $teams = 8): Tournament
{
    return app(TournamentCreator::class)->create([
        'name' => 'Testtoernooi',
        'sport' => 'football',
        'team_count' => $teams,
        'starts_at' => now()->addDay()->toDateTimeString(),
    ]);
}

function predict(User $user, TournamentMatch $match, int $home, int $away, ?int $penaltyWinner = null): Prediction
{
    return Prediction::create([
        'user_id' => $user->id,
        'tournament_match_id' => $match->id,
        'home_score' => $home,
        'away_score' => $away,
        'penalty_winner_team_id' => $penaltyWinner,
    ]);
}

it('gives 1 point for the right outcome and 4 for the exact score', function () {
    $tournament = tournamentWithTeams();
    $match = $tournament->groupMatches()->first();

    $exact = User::factory()->create();
    $outcome = User::factory()->create();
    $wrong = User::factory()->create();

    predict($exact, $match, 2, 1);
    predict($outcome, $match, 3, 1);
    predict($wrong, $match, 0, 2);

    app(ResultService::class)->store($match, ['home_score' => 2, 'away_score' => 1]);

    expect(Prediction::where('user_id', $exact->id)->value('points'))->toBe(4)
        ->and(Prediction::where('user_id', $outcome->id)->value('points'))->toBe(1)
        ->and(Prediction::where('user_id', $wrong->id)->value('points'))->toBe(0);
});

it('gives points for correctly predicting a draw', function () {
    $tournament = tournamentWithTeams();
    $match = $tournament->groupMatches()->first();

    $user = User::factory()->create();
    predict($user, $match, 1, 1);

    app(ResultService::class)->store($match, ['home_score' => 2, 'away_score' => 2]);

    expect(Prediction::where('user_id', $user->id)->value('points'))->toBe(1);
});

it('never scores the same result twice', function () {
    $tournament = tournamentWithTeams();
    $match = $tournament->groupMatches()->first();

    $user = User::factory()->create();
    predict($user, $match, 2, 1);

    app(ResultService::class)->store($match, ['home_score' => 2, 'away_score' => 1]);
    app(ScoreCalculator::class)->recalculateMatch($match->fresh());
    app(ScoreCalculator::class)->recalculateMatch($match->fresh());

    expect(Prediction::where('user_id', $user->id)->value('points'))->toBe(4)
        ->and(app(ScoreCalculator::class)->totalFor($tournament, $user->id))->toBe(4);
});

it('refuses a knockout match that ends in a draw without a penalty winner', function () {
    $tournament = tournamentWithTeams();
    $semi = $tournament->matches()->where('round', MatchRound::SemiFinal->value)->first();

    app(ResultService::class)->store($semi, ['home_score' => 1, 'away_score' => 1]);
})->throws(ValidationException::class);

it('refuses a penalty winner on a group match', function () {
    $tournament = tournamentWithTeams();
    $match = $tournament->groupMatches()->first();

    app(ResultService::class)->store($match, [
        'home_score' => 1,
        'away_score' => 1,
        'penalty_winner_team_id' => $match->home_team_id,
    ]);
})->throws(ValidationException::class);

it('gives the penalty winner a virtual goal when scoring predictions', function () {
    $tournament = tournamentWithTeams();

    // Play the whole group stage so the semi finals get their teams.
    playGroupStage($tournament);

    $semi = $tournament->matches()
        ->where('round', MatchRound::SemiFinal->value)
        ->orderBy('bracket_position')
        ->first()
        ->fresh();

    $exact = User::factory()->create();
    $missed = User::factory()->create();

    // 1-1 with the home team winning on penalties counts as 2-1.
    predict($exact, $semi, 1, 1, $semi->home_team_id);
    predict($missed, $semi, 1, 1, $semi->away_team_id);

    app(ResultService::class)->store($semi, [
        'home_score' => 1,
        'away_score' => 1,
        'penalty_winner_team_id' => $semi->home_team_id,
    ]);

    expect(Prediction::where('user_id', $exact->id)->value('points'))->toBe(4)
        ->and(Prediction::where('user_id', $missed->id)->value('points'))->toBe(0);
});

it('moves the group winners into the first knockout round', function () {
    $tournament = tournamentWithTeams(8);

    $semis = $tournament->matches()->where('round', MatchRound::SemiFinal->value)->get();
    expect($semis->whereNotNull('home_team_id')->count())->toBe(0);

    playGroupStage($tournament);

    $semis = $tournament->matches()
        ->where('round', MatchRound::SemiFinal->value)
        ->orderBy('bracket_position')
        ->get();

    expect($semis->whereNull('home_team_id')->count())->toBe(0)
        ->and($semis->whereNull('away_team_id')->count())->toBe(0);

    // The winner of group A must face the runner up of group B.
    $groupA = $tournament->groups()->where('name', 'A')->first();
    $groupB = $tournament->groups()->where('name', 'B')->first();

    expect($semis->first()->homeTeam->tournament_group_id)->toBe($groupA->id)
        ->and($semis->first()->awayTeam->tournament_group_id)->toBe($groupB->id);
});

it('plays a full tournament from the first group match to the final', function () {
    $tournament = tournamentWithTeams(8);

    playGroupStage($tournament);
    playKnockout($tournament);

    $final = $tournament->matches()->where('round', MatchRound::Final->value)->first()->fresh();

    expect($final->hasResult())->toBeTrue()
        ->and($tournament->rules()->winnerTeamId($final))->not->toBeNull()
        ->and($tournament->fresh()->hasScoringStarted())->toBeTrue()
        ->and($tournament->matches()->whereNull('result_entered_at')->count())->toBe(0);
});

// Plays every group match with a result that gives a clear ranking.
function playGroupStage(Tournament $tournament): void
{
    $service = app(ResultService::class);
    $matches = $tournament->groupMatches()->orderBy('id')->get();

    foreach ($matches as $index => $match) {
        // Alternate results so teams end up with different points.
        $service->store($match->fresh(), [
            'home_score' => ($index % 3) + 1,
            'away_score' => $index % 2,
        ]);
    }
}

// Plays every knockout round in order, always giving the home team the win.
function playKnockout(Tournament $tournament): void
{
    $service = app(ResultService::class);

    foreach (MatchRound::knockoutOrder() as $round) {
        $matches = $tournament->matches()
            ->where('round', $round->value)
            ->orderBy('bracket_position')
            ->get();

        foreach ($matches as $match) {
            $service->store($match->fresh(), ['home_score' => 2, 'away_score' => 0]);
        }
    }
}
