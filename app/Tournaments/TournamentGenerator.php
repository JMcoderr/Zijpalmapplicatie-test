<?php
// Builds a full tournament: groups, teams, group matches and the knockout bracket.

namespace App\Tournaments;

use App\Enums\Tournament\MatchRound;
use App\Models\Tournament\Team;
use App\Models\Tournament\Tournament;
use App\Models\Tournament\TournamentGroup;
use App\Models\Tournament\TournamentMatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TournamentGenerator
{
    /**
     * @param  array<string>  $teamNames  Optional names, filled up automatically when too short.
     */
    public function generate(Tournament $tournament, array $teamNames = []): Tournament
    {
        return DB::transaction(function () use ($tournament, $teamNames) {
            $groups = $this->createGroups($tournament);
            $this->createTeams($tournament, $groups, $teamNames);
            $this->createGroupMatches($tournament, $groups);
            $this->createKnockoutBracket($tournament, $groups);

            return $tournament->fresh();
        });
    }

    /** @return array<int, TournamentGroup> */
    private function createGroups(Tournament $tournament): array
    {
        $groups = [];

        for ($i = 0; $i < $tournament->group_count; $i++) {
            $groups[$i] = $tournament->groups()->create([
                'name' => $this->groupName($i),
                'position' => $i,
            ]);
        }

        return $groups;
    }

    /** @param array<int, TournamentGroup> $groups */
    private function createTeams(Tournament $tournament, array $groups, array $teamNames): void
    {
        for ($i = 0; $i < $tournament->team_count; $i++) {
            $groupIndex = intdiv($i, $tournament->group_size);

            $tournament->teams()->create([
                'tournament_group_id' => $groups[$groupIndex]->id,
                'name' => $teamNames[$i] ?? 'Team '.($i + 1),
                'position_in_group' => $i % $tournament->group_size,
            ]);
        }
    }

    /** @param array<int, TournamentGroup> $groups */
    private function createGroupMatches(Tournament $tournament, array $groups): void
    {
        $kickoff = $tournament->starts_at->copy();

        foreach ($groups as $group) {
            $teams = $group->teams()->orderBy('position_in_group')->get()->all();

            // Round robin: every team plays every other team in the group once.
            for ($i = 0; $i < count($teams); $i++) {
                for ($j = $i + 1; $j < count($teams); $j++) {
                    $tournament->matches()->create([
                        'tournament_group_id' => $group->id,
                        'round' => MatchRound::Group->value,
                        'home_team_id' => $teams[$i]->id,
                        'away_team_id' => $teams[$j]->id,
                        'kickoff' => $kickoff->copy(),
                    ]);

                    $kickoff->addHours(3);
                }
            }
        }
    }

    /** @param array<int, TournamentGroup> $groups */
    private function createKnockoutBracket(Tournament $tournament, array $groups): void
    {
        $rounds = $this->roundsFor($tournament->knockout_size);

        // Knockout starts a day after the last group match.
        $kickoff = $tournament->matches()->max('kickoff');
        $kickoff = $kickoff ? Carbon::parse($kickoff)->addDay() : $tournament->starts_at->copy()->addDay();

        /** @var array<int, TournamentMatch> $previousRound */
        $previousRound = [];

        foreach ($rounds as $roundIndex => $round) {
            $matchCount = $this->matchCountFor($round);
            $currentRound = [];

            for ($position = 0; $position < $matchCount; $position++) {
                $match = $tournament->matches()->create([
                    'round' => $round->value,
                    'bracket_position' => $position,
                    'kickoff' => $kickoff->copy()->addHours($position * 3),
                ]);

                $currentRound[$position] = $match;
            }

            if ($roundIndex === 0) {
                $this->labelFirstKnockoutRound($tournament, $groups, $currentRound);
            } else {
                $this->linkRounds($previousRound, $currentRound);
            }

            $previousRound = $currentRound;
            $kickoff->addDays(3);
        }
    }

    /**
     * The first knockout round is fed by the group standings, so each slot gets a
     * label like "Winnaar groep A" until the group stage is finished.
     *
     * @param  array<int, TournamentGroup>  $groups
     * @param  array<int, TournamentMatch>  $matches
     */
    private function labelFirstKnockoutRound(Tournament $tournament, array $groups, array $matches): void
    {
        foreach ($this->qualificationSlots($tournament) as $position => $slot) {
            [$homeSlot, $awaySlot] = $slot;

            $matches[$position]->update([
                'home_source' => $this->slotLabel($groups[$homeSlot[0]], $homeSlot[1]),
                'away_source' => $this->slotLabel($groups[$awaySlot[0]], $awaySlot[1]),
            ]);
        }
    }

    /**
     * Which group position feeds which knockout slot.
     * With two qualifiers the groups are paired so the winner of one group meets
     * the runner up of the next group.
     *
     * @return array<int, array{0: array{0:int,1:int}, 1: array{0:int,1:int}}>
     */
    public function qualificationSlots(Tournament $tournament): array
    {
        $slots = [];
        $groupCount = $tournament->group_count;

        if ($tournament->qualifiers_per_group === 2) {
            for ($i = 0; $i < $groupCount; $i += 2) {
                $slots[] = [[$i, 1], [$i + 1, 2]];
                $slots[] = [[$i + 1, 1], [$i, 2]];
            }

            return $slots;
        }

        for ($i = 0; $i < $groupCount; $i += 2) {
            $slots[] = [[$i, 1], [$i + 1, 1]];
        }

        return $slots;
    }

    /**
     * @param  array<int, TournamentMatch>  $previous
     * @param  array<int, TournamentMatch>  $current
     */
    private function linkRounds(array $previous, array $current): void
    {
        foreach ($previous as $position => $match) {
            $nextPosition = intdiv($position, 2);
            $slot = $position % 2 === 0 ? 'home' : 'away';

            $match->update([
                'next_match_id' => $current[$nextPosition]->id,
                'next_slot' => $slot,
            ]);

            $current[$nextPosition]->update([
                $slot.'_source' => 'Winnaar wedstrijd '.($position + 1),
            ]);
        }
    }

    /** @return array<int, MatchRound> */
    private function roundsFor(int $knockoutSize): array
    {
        $rounds = [];
        $teams = $knockoutSize;

        while ($teams >= 2) {
            $rounds[] = MatchRound::forTeamCount($teams);
            $teams = intdiv($teams, 2);
        }

        return $rounds;
    }

    private function matchCountFor(MatchRound $round): int
    {
        return match ($round) {
            MatchRound::RoundOf32 => 16,
            MatchRound::RoundOf16 => 8,
            MatchRound::QuarterFinal => 4,
            MatchRound::SemiFinal => 2,
            MatchRound::Final => 1,
            MatchRound::Group => 0,
        };
    }

    private function slotLabel(TournamentGroup $group, int $position): string
    {
        return $position === 1
            ? "Winnaar groep {$group->name}"
            : "Nummer {$position} groep {$group->name}";
    }

    private function groupName(int $index): string
    {
        // A, B, ... Z, then AA, AB and so on for very large tournaments.
        $name = '';

        do {
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26) - 1;
        } while ($index >= 0);

        return $name;
    }
}
