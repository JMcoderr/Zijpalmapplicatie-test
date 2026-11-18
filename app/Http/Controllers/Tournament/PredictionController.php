<?php
// Lets a participant enter and change predictions, within the rules about deadlines.

namespace App\Http\Controllers\Tournament;

use App\Http\Controllers\Controller;
use App\Models\Tournament\Prediction;
use App\Models\Tournament\TournamentMatch;
use App\Tournaments\KnockoutProgression;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PredictionController extends Controller
{
    public function __construct(private readonly KnockoutProgression $progression)
    {
    }

    public function index(Request $request): View
    {
        $predictions = Prediction::with(['match.homeTeam', 'match.awayTeam', 'match.tournament'])
            ->where('user_id', $request->user()->id)
            ->get()
            ->sortBy(fn (Prediction $prediction) => $prediction->match->kickoff);

        return view('tournament.predictions', compact('predictions'));
    }

    public function store(Request $request, TournamentMatch $match): RedirectResponse
    {
        $tournament = $match->tournament;

        // Everything closes the moment the tournament starts.
        if ($tournament->starts_at->isPast()) {
            return back()->withErrors([
                'deadline' => 'De deadline is verstreken, je kunt je voorspelling niet meer wijzigen.',
            ]);
        }

        if (! $match->isReadyForPredictions()) {
            return back()->withErrors([
                'deadline' => 'Deze wedstrijd heeft nog geen teams, voorspellen kan nog niet.',
            ]);
        }

        if ($match->round->isKnockout() && ! $this->progression->roundIsOpen($tournament, $match->round)) {
            return back()->withErrors([
                'deadline' => 'Deze ronde is nog niet open, de vorige ronde moet eerst afgerond zijn.',
            ]);
        }

        $validated = $request->validate([
            'home_score' => ['required', 'integer', 'min:0', 'max:99'],
            'away_score' => ['required', 'integer', 'min:0', 'max:99'],
            'penalty_winner_team_id' => ['nullable', 'integer'],
        ]);

        $penaltyWinner = $this->penaltyWinnerFor($match, $validated);

        // A knockout prediction needs a winner, so a draw must name a penalty winner.
        if ($match->round->isKnockout()
            && $validated['home_score'] === $validated['away_score']
            && ! $penaltyWinner) {
            return back()->withErrors([
                'penalty_winner_team_id' => 'Kies bij een gelijkspel wie de penaltyserie wint.',
            ]);
        }

        Prediction::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'tournament_match_id' => $match->id,
            ],
            [
                'home_score' => $validated['home_score'],
                'away_score' => $validated['away_score'],
                'penalty_winner_team_id' => $penaltyWinner,
            ]
        );

        return back()->with('success', 'Je voorspelling is opgeslagen.');
    }

    // Only keep the penalty pick when it makes sense for this match.
    private function penaltyWinnerFor(TournamentMatch $match, array $validated): ?int
    {
        if (! $match->round->isKnockout()) {
            return null;
        }

        if ($validated['home_score'] !== $validated['away_score']) {
            return null;
        }

        $chosen = $validated['penalty_winner_team_id'] ?? null;

        return in_array((int) $chosen, [$match->home_team_id, $match->away_team_id], true)
            ? (int) $chosen
            : null;
    }
}
