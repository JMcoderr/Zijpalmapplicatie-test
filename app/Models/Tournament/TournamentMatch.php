<?php
// A single match. Knockout matches start empty and get their teams once the
// previous stage has been processed.

namespace App\Models\Tournament;

use App\Enums\Tournament\MatchRound;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'tournament_group_id',
        'round',
        'bracket_position',
        'home_team_id',
        'away_team_id',
        'home_source',
        'away_source',
        'kickoff',
        'home_score',
        'away_score',
        'penalty_winner_team_id',
        'result_entered_at',
        'next_match_id',
        'next_slot',
    ];

    protected function casts(): array
    {
        return [
            'round' => MatchRound::class,
            'kickoff' => 'datetime',
            'result_entered_at' => 'datetime',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TournamentGroup::class, 'tournament_group_id');
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function penaltyWinner(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'penalty_winner_team_id');
    }

    public function nextMatch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'next_match_id');
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    public function hasResult(): bool
    {
        return $this->home_score !== null && $this->away_score !== null;
    }

    // Both teams need to be known before anyone can predict.
    public function isReadyForPredictions(): bool
    {
        return $this->home_team_id !== null && $this->away_team_id !== null;
    }

    public function homeLabel(): string
    {
        return $this->homeTeam?->name ?? $this->home_source ?? 'Nog onbekend';
    }

    public function awayLabel(): string
    {
        return $this->awayTeam?->name ?? $this->away_source ?? 'Nog onbekend';
    }
}
