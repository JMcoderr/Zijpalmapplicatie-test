<?php
// Recalculates the points of predictions. Points are stored as an absolute value
// per prediction, so running this twice can never double someone's score.

namespace App\Tournaments;

use App\Models\Tournament\Prediction;
use App\Models\Tournament\Tournament;
use App\Models\Tournament\TournamentMatch;

class ScoreCalculator
{
    public function recalculateMatch(TournamentMatch $match): void
    {
        $rules = $match->tournament->rules();

        $match->predictions()->with([])->chunkById(200, function ($predictions) use ($rules, $match) {
            foreach ($predictions as $prediction) {
                $prediction->update([
                    'points' => $rules->pointsFor($prediction, $match),
                    'scored_at' => $match->hasResult() ? now() : null,
                ]);
            }
        });
    }

    public function recalculateTournament(Tournament $tournament): void
    {
        foreach ($tournament->matches()->whereNotNull('result_entered_at')->cursor() as $match) {
            $this->recalculateMatch($match);
        }
    }

    // Total score of one participant in a tournament.
    public function totalFor(Tournament $tournament, int $userId): int
    {
        return (int) Prediction::query()
            ->where('user_id', $userId)
            ->whereIn('tournament_match_id', $tournament->matches()->select('id'))
            ->sum('points');
    }
}
