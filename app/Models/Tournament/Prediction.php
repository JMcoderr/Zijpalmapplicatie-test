<?php
// A participant's prediction for one match.

namespace App\Models\Tournament;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tournament_match_id',
        'home_score',
        'away_score',
        'penalty_winner_team_id',
        'points',
        'scored_at',
    ];

    protected function casts(): array
    {
        return [
            'scored_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'tournament_match_id');
    }

    public function penaltyWinner(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'penalty_winner_team_id');
    }
}
