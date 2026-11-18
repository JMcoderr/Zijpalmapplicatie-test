<?php
// Works out the table of a group based on the results that have been entered.

namespace App\Tournaments;

use App\Models\Tournament\TournamentGroup;
use Illuminate\Support\Collection;

class GroupStandings
{
    /**
     * Returns the teams of a group in ranking order, each with their table row.
     *
     * @return Collection<int, array{team: \App\Models\Tournament\Team, played:int, won:int, drawn:int, lost:int, goals_for:int, goals_against:int, goal_difference:int, points:int}>
     */
    public function for(TournamentGroup $group): Collection
    {
        $rules = $group->tournament->rules();
        $teams = $group->teams()->orderBy('position_in_group')->get();

        $rows = $teams->mapWithKeys(fn ($team) => [$team->id => [
            'team' => $team,
            'played' => 0,
            'won' => 0,
            'drawn' => 0,
            'lost' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'goal_difference' => 0,
            'points' => 0,
        ]])->all();

        foreach ($group->matches()->get() as $match) {
            if (! $match->hasResult() || ! isset($rows[$match->home_team_id], $rows[$match->away_team_id])) {
                continue;
            }

            $home = (int) $match->home_score;
            $away = (int) $match->away_score;

            $rows[$match->home_team_id]['played']++;
            $rows[$match->away_team_id]['played']++;

            $rows[$match->home_team_id]['goals_for'] += $home;
            $rows[$match->home_team_id]['goals_against'] += $away;
            $rows[$match->away_team_id]['goals_for'] += $away;
            $rows[$match->away_team_id]['goals_against'] += $home;

            if ($home > $away) {
                $rows[$match->home_team_id]['won']++;
                $rows[$match->away_team_id]['lost']++;
                $rows[$match->home_team_id]['points'] += $rules->standingPoints('win');
                $rows[$match->away_team_id]['points'] += $rules->standingPoints('loss');
            } elseif ($away > $home) {
                $rows[$match->away_team_id]['won']++;
                $rows[$match->home_team_id]['lost']++;
                $rows[$match->away_team_id]['points'] += $rules->standingPoints('win');
                $rows[$match->home_team_id]['points'] += $rules->standingPoints('loss');
            } else {
                $rows[$match->home_team_id]['drawn']++;
                $rows[$match->away_team_id]['drawn']++;
                $rows[$match->home_team_id]['points'] += $rules->standingPoints('draw');
                $rows[$match->away_team_id]['points'] += $rules->standingPoints('draw');
            }
        }

        foreach ($rows as $teamId => $row) {
            $rows[$teamId]['goal_difference'] = $row['goals_for'] - $row['goals_against'];
        }

        return collect($rows)
            ->sortBy([
                fn ($a, $b) => $b['points'] <=> $a['points'],
                fn ($a, $b) => $b['goal_difference'] <=> $a['goal_difference'],
                fn ($a, $b) => $b['goals_for'] <=> $a['goals_for'],
                fn ($a, $b) => $a['team']->name <=> $b['team']->name,
            ])
            ->values();
    }
}
