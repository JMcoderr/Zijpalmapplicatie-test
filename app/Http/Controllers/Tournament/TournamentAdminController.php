<?php
// Admin pages for creating tournaments and checking the generated schedule.

namespace App\Http\Controllers\Tournament;

use App\Http\Controllers\Controller;
use App\Models\Tournament\Tournament;
use App\Models\Tournament\TournamentMatch;
use App\Sports\SportRegistry;
use App\Tournaments\StructureResolver;
use App\Tournaments\TournamentCreator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TournamentAdminController extends Controller
{
    public function __construct(
        private readonly TournamentCreator $creator,
        private readonly StructureResolver $resolver,
        private readonly SportRegistry $sports,
    ) {
    }

    public function index(): View
    {
        return view('tournament.admin.index', [
            'tournaments' => Tournament::withCount(['teams', 'matches'])->orderByDesc('starts_at')->get(),
            'sports' => $this->sports->options(),
            'validTeamCounts' => $this->resolver->validTeamCounts(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'sport' => ['required', 'string'],
            'team_count' => [
                'required',
                'integer',
                'min:'.StructureResolver::MIN_TEAMS,
                'max:'.StructureResolver::MAX_TEAMS,
            ],
            'starts_at' => ['required', 'date', 'after:now'],
            'team_names' => ['nullable', 'string'],
        ], [
            'team_count.min' => 'Een toernooi heeft minimaal '.StructureResolver::MIN_TEAMS.' teams nodig.',
            'team_count.max' => 'Een toernooi mag maximaal '.StructureResolver::MAX_TEAMS.' teams hebben.',
            'starts_at.after' => 'De startdatum moet in de toekomst liggen.',
        ]);

        abort_unless($this->sports->has($validated['sport']), 422, 'Onbekend sporttype.');

        // One team name per line, blank lines are ignored.
        $teamNames = collect(preg_split('/\r\n|\r|\n/', $validated['team_names'] ?? ''))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->values()
            ->all();

        $tournament = $this->creator->create($validated, $teamNames);

        return redirect()
            ->route('tournament.admin.matches', $tournament)
            ->with('success', 'Toernooi aangemaakt. Controleer hieronder het gegenereerde schema.');
    }

    // The organiser can check and correct the generated matches here.
    public function matches(Tournament $tournament): View
    {
        $tournament->load('groups');

        $matches = $tournament->matches()
            ->with(['homeTeam', 'awayTeam', 'group'])
            ->orderBy('kickoff')
            ->get()
            ->groupBy(fn ($match) => $match->round->value);

        return view('tournament.admin.matches', compact('tournament', 'matches'));
    }

    public function updateMatch(Request $request, TournamentMatch $match): RedirectResponse
    {
        $validated = $request->validate([
            'kickoff' => ['required', 'date'],
        ]);

        $match->update($validated);

        return back()->with('success', 'Wedstrijd bijgewerkt.');
    }

    public function updateTeam(Request $request, Tournament $tournament): RedirectResponse
    {
        $validated = $request->validate([
            'team_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:100'],
        ]);

        $team = $tournament->teams()->findOrFail($validated['team_id']);
        $team->update(['name' => $validated['name']]);

        return back()->with('success', 'Teamnaam bijgewerkt.');
    }

    public function destroy(Tournament $tournament): RedirectResponse
    {
        $tournament->delete();

        return redirect()->route('tournament.admin.index')->with('success', 'Toernooi verwijderd.');
    }
}
