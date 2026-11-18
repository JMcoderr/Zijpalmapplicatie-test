<?php

namespace Tests\Support;

use App\Enums\Tournament\MatchRound;
use App\Sports\ScoreBasedSportRules;

class TestBasketballRules extends ScoreBasedSportRules
{
    public function key(): string
    {
        return 'basketball';
    }

    public function label(): string
    {
        return 'Basketbal';
    }

    public function allowsDraw(MatchRound $round): bool
    {
        return false;
    }

    public function standingPoints(string $outcome): int
    {
        return $outcome === 'win' ? 2 : 0;
    }

    protected function pointsForExactScore(): int
    {
        return 5;
    }
}