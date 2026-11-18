<?php
// Shared logic for every sport that is scored with two numbers (home and away).
// A new sport can extend this class and only override what is different.

namespace App\Sports;

use App\Enums\Tournament\MatchRound;
use App\Models\Tournament\Prediction;
use App\Models\Tournament\TournamentMatch;
use Illuminate\Validation\ValidationException;

abstract class ScoreBasedSportRules implements SportRules
{
    abstract public function key(): string;

    abstract public function label(): string;

    // Points for guessing win, loss or draw correctly.
    protected function pointsForOutcome(): int
    {
        return 1;
    }

    // Extra points on top of that when the exact score is right.
    protected function pointsForExactScore(): int
    {
        return 3;
    }

    public function maximumPointsPerMatch(): int
    {
        return $this->pointsForOutcome() + $this->pointsForExactScore();
    }

    // Classic three points for a win, one for a draw.
    public function standingPoints(string $outcome): int
    {
        return match ($outcome) {
            'win' => 3,
            'draw' => 1,
            default => 0,
        };
    }

    public function allowsDraw(MatchRound $round): bool
    {
        return ! $round->isKnockout();
    }

    public function resultValidationRules(TournamentMatch $match): array
    {
        $rules = [
            'home_score' => ['required', 'integer', 'min:0', 'max:99'],
            'away_score' => ['required', 'integer', 'min:0', 'max:99'],
        ];

        // A knockout match always needs a winner, so a penalty pick may be required.
        if (! $this->allowsDraw($match->round)) {
            $rules['penalty_winner_team_id'] = ['nullable', 'integer'];
        }

        return $rules;
    }

    public function validateResult(TournamentMatch $match, array $result): void
    {
        $home = (int) ($result['home_score'] ?? 0);
        $away = (int) ($result['away_score'] ?? 0);
        $penaltyWinner = $result['penalty_winner_team_id'] ?? null;

        if ($this->allowsDraw($match->round)) {
            if ($penaltyWinner) {
                throw ValidationException::withMessages([
                    'penalty_winner_team_id' => 'Een poulewedstrijd kent geen penaltyserie.',
                ]);
            }

            return;
        }

        // Knockout: a draw is only allowed when penalties decide the winner.
        if ($home === $away && ! $penaltyWinner) {
            throw ValidationException::withMessages([
                'penalty_winner_team_id' => 'Een knockoutwedstrijd kan niet gelijk eindigen, kies de winnaar van de penaltyserie.',
            ]);
        }

        if ($home !== $away && $penaltyWinner) {
            throw ValidationException::withMessages([
                'penalty_winner_team_id' => 'Er is al een winnaar, een penaltyserie is dan niet mogelijk.',
            ]);
        }

        if ($penaltyWinner && ! in_array((int) $penaltyWinner, [$match->home_team_id, $match->away_team_id], true)) {
            throw ValidationException::withMessages([
                'penalty_winner_team_id' => 'De penaltywinnaar moet een van beide teams zijn.',
            ]);
        }
    }

    public function pointsFor(Prediction $prediction, TournamentMatch $match): int
    {
        if (! $match->hasResult()) {
            return 0;
        }

        [$actualHome, $actualAway] = $this->effectiveResult($match);
        [$guessHome, $guessAway] = $this->effectivePrediction($prediction, $match);

        if ($this->outcome($guessHome, $guessAway) !== $this->outcome($actualHome, $actualAway)) {
            return 0;
        }

        $points = $this->pointsForOutcome();

        if ($guessHome === $actualHome && $guessAway === $actualAway) {
            $points += $this->pointsForExactScore();
        }

        return $points;
    }

    public function winnerTeamId(TournamentMatch $match): ?int
    {
        if (! $match->hasResult()) {
            return null;
        }

        if ($match->home_score > $match->away_score) {
            return $match->home_team_id;
        }

        if ($match->away_score > $match->home_score) {
            return $match->away_team_id;
        }

        return $match->penalty_winner_team_id;
    }

    // The score used for scoring predictions. Sports can add bonuses here.
    protected function effectiveResult(TournamentMatch $match): array
    {
        return [(int) $match->home_score, (int) $match->away_score];
    }

    protected function effectivePrediction(Prediction $prediction, TournamentMatch $match): array
    {
        return [(int) $prediction->home_score, (int) $prediction->away_score];
    }

    private function outcome(int $home, int $away): string
    {
        return match (true) {
            $home > $away => 'home',
            $away > $home => 'away',
            default => 'draw',
        };
    }
}
