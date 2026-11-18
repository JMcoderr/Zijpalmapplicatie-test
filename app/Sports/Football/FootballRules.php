<?php
// Football rules. Only the parts that are specific to football live here.

namespace App\Sports\Football;

use App\Models\Tournament\Prediction;
use App\Models\Tournament\TournamentMatch;
use App\Sports\ScoreBasedSportRules;

class FootballRules extends ScoreBasedSportRules
{
    public function key(): string
    {
        return 'football';
    }

    public function label(): string
    {
        return 'Voetbal';
    }

    /**
     * When a knockout match is decided by penalties the winner gets one virtual
     * goal extra, so the pool calculation always has a winner.
     */
    protected function effectiveResult(TournamentMatch $match): array
    {
        return $this->applyPenaltyBonus(
            (int) $match->home_score,
            (int) $match->away_score,
            $match->penalty_winner_team_id,
            $match
        );
    }

    protected function effectivePrediction(Prediction $prediction, TournamentMatch $match): array
    {
        return $this->applyPenaltyBonus(
            (int) $prediction->home_score,
            (int) $prediction->away_score,
            $prediction->penalty_winner_team_id,
            $match
        );
    }

    private function applyPenaltyBonus(int $home, int $away, ?int $penaltyWinnerId, TournamentMatch $match): array
    {
        if ($home !== $away || $penaltyWinnerId === null) {
            return [$home, $away];
        }

        if ($penaltyWinnerId === $match->home_team_id) {
            return [$home + 1, $away];
        }

        if ($penaltyWinnerId === $match->away_team_id) {
            return [$home, $away + 1];
        }

        return [$home, $away];
    }
}
