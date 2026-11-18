<?php
// One group (poule) inside a tournament.

namespace App\Models\Tournament;

use App\Enums\Tournament\MatchRound;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'name',
        'position',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class)->where('round', MatchRound::Group->value);
    }

    public function isComplete(): bool
    {
        return ! $this->matches()->whereNull('result_entered_at')->exists();
    }
}
