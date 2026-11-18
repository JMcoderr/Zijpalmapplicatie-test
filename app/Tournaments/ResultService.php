<?php
// Handles everything that happens when an official result is entered.

namespace App\Tournaments;

use App\Models\Tournament\TournamentMatch;
use Illuminate\Support\Facades\DB;

class ResultService
{
    public function __construct(
        private readonly ScoreCalculator $calculator,
        private readonly KnockoutProgression $progression,
    ) {
    }

    /**
     * @param  array{home_score:int, away_score:int, penalty_winner_team_id?:int|null}  $result
     */
    public function store(TournamentMatch $match, array $result): void
    {
        $tournament = $match->tournament;
        $rules = $tournament->rules();

        $rules->validateResult($match, $result);

        DB::transaction(function () use ($match, $result, $tournament) {
            $match->update([
                'home_score' => $result['home_score'],
                'away_score' => $result['away_score'],
                'penalty_winner_team_id' => $result['penalty_winner_team_id'] ?? null,
                'result_entered_at' => now(),
            ]);

            // The first entered result locks the participant list.
            if (! $tournament->hasScoringStarted()) {
                $tournament->update(['scoring_started_at' => now()]);
            }

            $this->calculator->recalculateMatch($match->fresh());
            $this->progression->sync($tournament->fresh());
        });
    }
}
