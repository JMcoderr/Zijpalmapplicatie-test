<?php
// Admin page where the official results are entered.

namespace App\Http\Controllers\Tournament;

use App\Http\Controllers\Controller;
use App\Models\Tournament\Tournament;
use App\Models\Tournament\TournamentMatch;
use App\Tournaments\KnockoutProgression;
use App\Tournaments\ResultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function __construct(
        private readonly ResultService $results,
        private readonly KnockoutProgression $progression,
    ) {
    }

    public function index(Tournament $tournament): View
    {
        $matches = $tournament->matches()
            ->with(['homeTeam', 'awayTeam', 'group', 'penaltyWinner'])
            ->orderBy('kickoff')
            ->get()
            ->groupBy(fn ($match) => $match->round->value);

        return view('tournament.admin.results', [
            'tournament' => $tournament,
            'matches' => $matches,
            'groupStageComplete' => $this->progression->groupStageIsComplete($tournament),
        ]);
    }

    public function store(Request $request, TournamentMatch $match): RedirectResponse
    {
        if (! $match->isReadyForPredictions()) {
            return back()->withErrors([
                'result' => 'Deze wedstrijd heeft nog geen teams, een uitslag invoeren kan nog niet.',
            ]);
        }

        $rules = $match->tournament->rules();
        $validated = $request->validate($rules->resultValidationRules($match));

        // The sport decides whether this result is allowed, for example about draws.
        $this->results->store($match, [
            'home_score' => (int) $validated['home_score'],
            'away_score' => (int) $validated['away_score'],
            'penalty_winner_team_id' => $validated['penalty_winner_team_id'] ?? null,
        ]);

        return back()->with('success', 'Uitslag opgeslagen, de punten en ranglijst zijn bijgewerkt.');
    }
}
