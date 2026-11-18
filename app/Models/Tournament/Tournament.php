<?php
// A tournament with its groups, teams and matches.

namespace App\Models\Tournament;

use App\Enums\Tournament\MatchRound;
use App\Enums\Tournament\TournamentStatus;
use App\Models\User;
use App\Sports\SportRegistry;
use App\Sports\SportRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'sport',
        'status',
        'team_count',
        'group_count',
        'group_size',
        'qualifiers_per_group',
        'knockout_size',
        'starts_at',
        'scoring_started_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TournamentStatus::class,
            'starts_at' => 'datetime',
            'scoring_started_at' => 'datetime',
        ];
    }

    public function groups(): HasMany
    {
        return $this->hasMany(TournamentGroup::class)->orderBy('position');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tournament_participants')
            ->withPivot('paid_at')
            ->withTimestamps();
    }

    public function groupMatches(): HasMany
    {
        return $this->matches()->where('round', MatchRound::Group->value);
    }

    // The rules of the sport this tournament is played with.
    public function rules(): SportRules
    {
        return app(SportRegistry::class)->get($this->sport);
    }

    // Registration and predictions both close when the tournament starts.
    public function isOpenForRegistration(): bool
    {
        return $this->starts_at->isFuture();
    }

    public function hasScoringStarted(): bool
    {
        return $this->scoring_started_at !== null;
    }
}
