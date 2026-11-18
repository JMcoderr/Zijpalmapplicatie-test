<?php
// Moves teams forward: from the group tables into the first knockout round,
// and from one knockout round into the next.

namespace App\Tournaments;

use App\Enums\Tournament\MatchRound;
use App\Models\Tournament\Tournament;
use App\Models\Tournament\TournamentMatch;
use Illuminate\Support\Facades\DB;

class KnockoutProgression
{
    public function __construct(
        private readonly GroupStandings $standings,
        private readonly TournamentGenerator $generator,
    ) {
    }

    // Called after every result so the bracket always reflects the latest situation.
    public function sync(Tournament $tournament): void
    {
        DB::transaction(function () use ($tournament) {
            $this->qualifyFromGroups($tournament);
            $this->advanceKnockoutWinners($tournament);
        });
    }

    // The first knockout round only opens once every group match has a result.
    public function qualifyFromGroups(Tournament $tournament): void
    {
        if (! $this->groupStageIsComplete($tournament)) {
            return;
        }

        $firstRound = $this->firstKnockoutRound($tournament);

        if ($firstRound->isEmpty()) {
            return;
        }

        $groups = $tournament->groups()->get()->values();
        $tables = $groups->map(fn ($group) => $this->standings->for($group));

        foreach ($this->generator->qualificationSlots($tournament) as $position => $slot) {
            $match = $firstRound->get($position);

            if (! $match) {
                continue;
            }

            [$homeSlot, $awaySlot] = $slot;

            $match->update([
                'home_team_id' => $this->teamAt($tables, $homeSlot),
                'away_team_id' => $this->teamAt($tables, $awaySlot),
            ]);
        }
    }

    // A round only opens when the previous round has been fully processed.
    public function advanceKnockoutWinners(Tournament $tournament): void
    {
        $rules = $tournament->rules();

        $matches = $tournament->matches()
            ->where('round', '!=', MatchRound::Group->value)
            ->whereNotNull('next_match_id')
            ->orderBy('bracket_position')
            ->get();

        foreach ($matches as $match) {
            $winnerId = $rules->winnerTeamId($match);

            if ($winnerId === null) {
                continue;
            }

            $next = $matches->firstWhere('id', $match->next_match_id)
                ?? TournamentMatch::find($match->next_match_id);

            if (! $next) {
                continue;
            }

            $next->update([$match->next_slot.'_team_id' => $winnerId]);
        }
    }

    public function groupStageIsComplete(Tournament $tournament): bool
    {
        return ! $tournament->groupMatches()->whereNull('result_entered_at')->exists();
    }

    // True when every match of the round before this one has a result.
    public function roundIsOpen(Tournament $tournament, MatchRound $round): bool
    {
        if ($round === MatchRound::Group) {
            return true;
        }

        $order = $this->playedRounds($tournament);
        $index = array_search($round, $order, true);

        if ($index === false) {
            return false;
        }

        if ($index === 0) {
            return $this->groupStageIsComplete($tournament);
        }

        $previous = $order[$index - 1];

        return ! $tournament->matches()
            ->where('round', $previous->value)
            ->whereNull('result_entered_at')
            ->exists();
    }

    /** @return array<int, MatchRound> The knockout rounds this tournament actually plays. */
    private function playedRounds(Tournament $tournament): array
    {
        $rounds = [];
        $teams = $tournament->knockout_size;

        while ($teams >= 2) {
            $rounds[] = MatchRound::forTeamCount($teams);
            $teams = intdiv($teams, 2);
        }

        return $rounds;
    }

    private function firstKnockoutRound(Tournament $tournament)
    {
        $round = MatchRound::forTeamCount($tournament->knockout_size);

        return $tournament->matches()
            ->where('round', $round->value)
            ->orderBy('bracket_position')
            ->get()
            ->values();
    }

    /** @param array{0:int,1:int} $slot Group index and position in that group. */
    private function teamAt($tables, array $slot): ?int
    {
        [$groupIndex, $position] = $slot;

        $table = $tables->get($groupIndex);

        if (! $table) {
            return null;
        }

        return $table->get($position - 1)['team']->id ?? null;
    }
}
