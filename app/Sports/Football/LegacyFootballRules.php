<?php

namespace App\Sports\Football;

use App\Models\Tournament\Prediction;
use App\Models\Tournament\TournamentMatch;

class LegacyFootballRules extends FootballRules
{
    public function key(): string
    {
        return 'legacy_football';
    }

    public function label(): string
    {
        return 'Voetbal (oude 3/1/0-puntentelling)';
    }

    public function maximumPointsPerMatch(): int
    {
        return 3;
    }

    public function pointsFor(Prediction $prediction, TournamentMatch $match): int
    {
        if (! $match->hasResult()) {
            return 0;
        }

        if ($prediction->home_score === $match->home_score && $prediction->away_score === $match->away_score) {
            return 3;
        }

        return $prediction->home_score - $prediction->away_score === $match->home_score - $match->away_score ? 1 : 0;
    }
}