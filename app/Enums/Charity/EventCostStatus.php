<?php
// Tells whether an amount still has to start, is active now, or has expired.

namespace App\Enums\Charity;

enum EventCostStatus: string
{
    case Upcoming = 'upcoming';
    case Active = 'active';
    case Passed = 'passed';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Gepland',
            self::Active => 'Actief',
            self::Passed => 'Afgelopen',
            self::Unknown => 'Onbekend',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
