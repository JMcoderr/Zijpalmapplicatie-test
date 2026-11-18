<?php
// An employee who is eligible for a Lief en Leed contribution.

namespace App\Models\Charity;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;

    protected $fillable = [
        'id',
        'email',
        'full_name',
        'name',
        'dob',
        'years_of_service',
    ];

    public function requests(): HasMany
    {
        return $this->hasMany(SupportRequest::class, 'employee_recipient', 'id');
    }

    public function getIdAttribute(): string
    {
        return str_pad($this->attributes['id'], 6, '0', STR_PAD_LEFT);
    }

    // Former employees can still be nominated for a few months after they leave.
    public function scopeAllowedMembers(Builder $query, int $months = 3): Builder
    {
        $cutoff = Carbon::now()->subMonths($months);

        return $query->withTrashed()
            ->where(function (Builder $q) use ($cutoff) {
                $q->whereNull('deleted_at')
                    ->orWhere('deleted_at', '>=', $cutoff);
            });
    }
}
