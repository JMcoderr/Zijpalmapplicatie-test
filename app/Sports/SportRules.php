<?php
// Contract every sport has to follow. The general tournament logic only talks to this
// interface, so a new sport can be added without touching the rest of the application.

namespace App\Sports;

use App\Enums\Tournament\MatchRound;
use App\Models\Tournament\Prediction;
use App\Models\Tournament\TournamentMatch;

interface SportRules
{
    // Short key stored on the tournament, for example "football".
    public function key(): string;

    // Name shown to the organiser.
    public function label(): string;

    // May a match in this round end in a draw?
    public function allowsDraw(MatchRound $round): bool;

    // Validation rules for the admin form where the official result is entered.
    public function resultValidationRules(TournamentMatch $match): array;

    // Extra checks that need the match itself, throws a ValidationException when invalid.
    public function validateResult(TournamentMatch $match, array $result): void;

    // Points a single prediction is worth once the match has a result.
    public function pointsFor(Prediction $prediction, TournamentMatch $match): int;

    // Which team goes through to the next round, or null when there is no result yet.
    public function winnerTeamId(TournamentMatch $match): ?int;

    // Points a team earns in the group table for a win, draw or loss.
    public function standingPoints(string $outcome): int;

    // Highest number of points one prediction can score, used in explanations.
    public function maximumPointsPerMatch(): int;
}
