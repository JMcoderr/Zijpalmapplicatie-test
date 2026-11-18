<?php
// The status a Lief en Leed request can have.

namespace App\Enums\Charity;

enum RequestStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case PAID = 'paid';
    case EXPORTED = 'exported';

    public function label(): string
    {
        return match ($this) {
            RequestStatus::PENDING => 'In behandeling',
            RequestStatus::ACCEPTED => 'Goedgekeurd',
            RequestStatus::REJECTED => 'Afgewezen',
            RequestStatus::PAID => 'Betaald',
            RequestStatus::EXPORTED => 'Geexporteerd',
        };
    }
}
