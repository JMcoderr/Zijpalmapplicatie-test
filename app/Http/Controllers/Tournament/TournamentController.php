<?php
// Public pages of the tournament: overview, group tables and the bracket.

namespace App\Http\Controllers\Tournament;

use App\Enums\Tournament\MatchRound;
use App\Http\Controllers\Controller;
use App\Models\Tournament\Prediction;
use App\Models\Tournament\Tournament;
use App\Tournaments\GroupStandings;
use App\Tournaments\KnockoutProgression;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TournamentController extends Controller
{
    public function __construct(
        private readonly GroupStandings $standings,
        private readonly KnockoutProgression $progression,
    ) {
    }

    public function index(): View
    {
        $tournaments = Tournament::withCount('teams')->orderByDesc('starts_at')->get();

        return view('tournament.index', compact('tournaments'));
    }

    public function show(Request $request, Tournament $tournament): View
    {
        $tournament->load(['groups.teams']);

        $groupMatches = $tournament->groupMatches()
            ->with(['homeTeam', 'awayTeam', 'group'])
            ->orderBy('kickoff')
            ->get()
            ->groupBy('tournament_group_id');

        $tables = $tournament->groups->mapWithKeys(
            fn ($group) => [$group->id => $this->standings->for($group)]
        );

        return view('tournament.show', [
            'tournament' => $tournament,
            'groupMatches' => $groupMatches,
            'tables' => $tables,
            'myPredictions' => $this->predictionsOf($request, $tournament),
        ]);
    }

    public function bracket(Request $request, Tournament $tournament): View
    {
        $rounds = $tournament->matches()
            ->where('round', '!=', MatchRound::Group->value)
            ->with(['homeTeam', 'awayTeam', 'penaltyWinner'])
            ->orderBy('bracket_position')
            ->get()
            ->groupBy(fn ($match) => $match->round->value);

        // A round is only open once the previous one has been fully processed.
        $openRounds = collect(MatchRound::knockoutOrder())
            ->mapWithKeys(fn ($round) => [
                $round->value => $this->progression->roundIsOpen($tournament, $round),
            ]);

        return view('tournament.bracket', [
            'tournament' => $tournament,
            'rounds' => $rounds,
            'openRounds' => $openRounds,
            'myPredictions' => $this->predictionsOf($request, $tournament),
        ]);
    }

    private function predictionsOf(Request $request, Tournament $tournament)
    {
        if (! $request->user()) {
            return collect();
        }

        return Prediction::where('user_id', $request->user()->id)
            ->whereIn('tournament_match_id', $tournament->matches()->select('id'))
            ->get()
            ->keyBy('tournament_match_id');
    }
}
