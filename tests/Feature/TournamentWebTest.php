<?php
// Tests for the tournament pages, predictions, participant admin and exports.

use App\Enums\Tournament\MatchRound;
use App\Models\Tournament\Prediction;
use App\Models\Tournament\Tournament;
use App\Models\User;
use App\Tournaments\ResultService;
use App\Tournaments\TournamentCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeTournament(array $overrides = []): Tournament
{
    return app(TournamentCreator::class)->create([
        'name' => 'WK 2026',
        'sport' => 'football',
        'team_count' => 8,
        'starts_at' => now()->addWeek()->toDateTimeString(),
        ...$overrides,
    ]);
}

function admin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

it('shows the tournament overview to everyone', function () {
    makeTournament();

    $this->get(route('tournament.index'))->assertOk()->assertSee('WK 2026');
});

it('shows the group stage with the standings', function () {
    $tournament = makeTournament();

    $this->get(route('tournament.show', $tournament))
        ->assertOk()
        ->assertSee('Poule A')
        ->assertSee('Poule B');
});

it('shows the bracket with the group positions before the groups are played', function () {
    $tournament = makeTournament();

    $this->get(route('tournament.bracket', $tournament))
        ->assertOk()
        ->assertSee('Halve finales')
        ->assertSee('Winnaar groep A');
});

it('keeps non admins out of the tournament admin', function () {
    $tournament = makeTournament();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get(route('tournament.admin.index'))->assertRedirect();
    $this->actingAs($user)->get(route('tournament.admin.results', $tournament))->assertRedirect();
});

it('lets an admin create a tournament through the form', function () {
    $this->actingAs(admin())
        ->post(route('tournament.admin.store'), [
            'name' => 'Nieuw toernooi',
            'sport' => 'football',
            'team_count' => 16,
            'starts_at' => now()->addMonth()->format('Y-m-d\TH:i'),
        ])
        ->assertSessionHasNoErrors();

    $tournament = Tournament::where('name', 'Nieuw toernooi')->first();

    expect($tournament)->not->toBeNull()
        ->and($tournament->teams()->count())->toBe(16)
        ->and($tournament->matches()->count())->toBe(31);
});

it('shows a clear error for an invalid team count', function () {
    $this->actingAs(admin())
        ->post(route('tournament.admin.store'), [
            'name' => 'Te klein',
            'sport' => 'football',
            'team_count' => 5,
            'starts_at' => now()->addMonth()->format('Y-m-d\TH:i'),
        ])
        ->assertSessionHasErrors('team_count');

    expect(Tournament::count())->toBe(0);
});

it('lets a participant store and change a prediction before the deadline', function () {
    $tournament = makeTournament();
    $match = $tournament->groupMatches()->first();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('tournament.prediction.store', $match), [
        'home_score' => 1,
        'away_score' => 0,
    ])->assertSessionHasNoErrors();

    $this->actingAs($user)->post(route('tournament.prediction.store', $match), [
        'home_score' => 3,
        'away_score' => 2,
    ]);

    expect(Prediction::where('user_id', $user->id)->count())->toBe(1)
        ->and(Prediction::where('user_id', $user->id)->value('home_score'))->toBe(3);
});

it('blocks predictions once the tournament has started', function () {
    $tournament = makeTournament(['starts_at' => now()->addSecond()->toDateTimeString()]);
    $tournament->update(['starts_at' => now()->subMinute()]);

    $match = $tournament->groupMatches()->first();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('tournament.prediction.store', $match), ['home_score' => 1, 'away_score' => 0])
        ->assertSessionHasErrors('deadline');

    expect(Prediction::count())->toBe(0);
});

it('blocks a knockout prediction while the round is not open', function () {
    $tournament = makeTournament();
    $semi = $tournament->matches()->where('round', MatchRound::SemiFinal->value)->first();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('tournament.prediction.store', $semi), ['home_score' => 1, 'away_score' => 0])
        ->assertSessionHasErrors('deadline');
});

it('lets a participant sign up and an admin manage the payment status', function () {
    $tournament = makeTournament();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('tournament.join', $tournament))->assertSessionHasNoErrors();
    expect($tournament->participants()->count())->toBe(1);

    $this->actingAs(admin())
        ->put(route('tournament.admin.togglePaid', [$tournament, $user]))
        ->assertSessionHasNoErrors();

    expect($tournament->participants()->first()->pivot->paid_at)->not->toBeNull();
});

it('refuses to remove a participant once scoring has started', function () {
    $tournament = makeTournament();
    $user = User::factory()->create();
    $tournament->participants()->attach($user->id);

    app(ResultService::class)->store(
        $tournament->groupMatches()->first(),
        ['home_score' => 1, 'away_score' => 0]
    );

    $this->actingAs(admin())
        ->delete(route('tournament.admin.removeParticipant', [$tournament, $user]))
        ->assertSessionHasErrors('participants');

    expect($tournament->participants()->count())->toBe(1);
});

it('shows only the display name on the public ranking', function () {
    $tournament = makeTournament();
    $user = User::factory()->create([
        'firstName' => 'Geheime',
        'lastName' => 'Achternaam',
        'display_name' => 'Topvoorspeller',
        'email' => 'geheim@example.com',
    ]);
    $tournament->participants()->attach($user->id);

    $this->get(route('tournament.ranking', $tournament))
        ->assertOk()
        ->assertSee('Topvoorspeller')
        ->assertDontSee('Geheime')
        ->assertDontSee('geheim@example.com');
});

it('lets anyone download the public excel ranking', function () {
    $tournament = makeTournament();

    $this->get(route('tournament.ranking.excel', $tournament))->assertOk();
});

it('only lets admins download the board version', function () {
    $tournament = makeTournament();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('tournament.ranking.excel', [$tournament, 'bestuur' => 1]))
        ->assertForbidden();

    $this->actingAs(admin())
        ->get(route('tournament.ranking.excel', [$tournament, 'bestuur' => 1]))
        ->assertOk();
});

it('can render the ranking as pdf', function () {
    $tournament = makeTournament();

    $response = $this->get(route('tournament.ranking.pdf', $tournament));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});
