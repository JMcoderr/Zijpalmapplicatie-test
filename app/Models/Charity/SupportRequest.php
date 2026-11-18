<?php
// A request for a Lief en Leed contribution. Named SupportRequest to avoid
// confusion with Illuminate\Http\Request.

namespace App\Models\Charity;

use App\Casts\EncryptedCast;
use App\Enums\Charity\RequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Carbon;

class SupportRequest extends Model
{
    use HasFactory;

    protected $table = 'requests';

    protected $fillable = [
        'employee_requester',
        'employee_recipient',
        'event_cost_id',
        'iban',
        'account_name',
        'status',
        'reason',
        'paid_at',
    ];

    protected $casts = [
        'iban' => EncryptedCast::class,
        'payment_download' => 'datetime',
        'status' => RequestStatus::class,
        'created_at' => 'datetime',
    ];

    // The IBAN should never end up in a response by accident.
    protected $hidden = [
        'iban',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'employee_recipient');
    }

    public function eventCost(): BelongsTo
    {
        return $this->belongsTo(EventCost::class);
    }

    public function event(): HasOneThrough
    {
        return $this->hasOneThrough(Event::class, EventCost::class, 'id', 'id', 'event_cost_id', 'event_id');
    }

    public function getEmployeeRequesterAttribute($value)
    {
        return str_pad($value, 6, '0', STR_PAD_LEFT);
    }

    public function getEmployeeRecipientAttribute($value)
    {
        return str_pad($value, 6, '0', STR_PAD_LEFT);
    }

    public function updateIfPending(array $attributes): bool
    {
        if ($this->status !== RequestStatus::PENDING) {
            return false;
        }

        if ($attributes['status'] === RequestStatus::REJECTED->value) {
            $attributes['iban'] = null;
            $attributes['account_name'] = null;
        } else {
            $attributes['reason'] = null;
        }

        return $this->update($attributes);
    }

    public function updateIfExported(array $attributes): bool
    {
        if ($this->status !== RequestStatus::EXPORTED) {
            return false;
        }

        // Wipe the bank details once paid, because of privacy rules.
        $attributes['paid_at'] = Carbon::now();
        $attributes['iban'] = null;
        $attributes['account_name'] = null;

        return $this->update($attributes);
    }

    /**
     * Dont include any rows with given status in query.
     *
     * @author Brighton van Rouendal
     */
    public function scopeWithoutStatus(Builder $query, RequestStatus $status): Builder
    {
        return match ($status) {
            RequestStatus::PENDING => $query->whereNot('status', RequestStatus::PENDING),
            RequestStatus::REJECTED => $query->whereNot('status', RequestStatus::REJECTED),
            RequestStatus::ACCEPTED => $query->whereNot('status', RequestStatus::ACCEPTED),
            RequestStatus::EXPORTED => $query->whereNot('status', RequestStatus::EXPORTED),
            RequestStatus::PAID => $query->whereNot('status', RequestStatus::PAID),

            default => $query->whereRaw('1=0'), // no match for Unknown status
        };
    }
}
