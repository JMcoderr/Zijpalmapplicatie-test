<?php
// The stage a tournament is in.

namespace App\Enums\Tournament;

enum TournamentStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Running = 'running';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Concept',
            self::Open => 'Inschrijving open',
            self::Running => 'Bezig',
            self::Finished => 'Afgelopen',
        };
    }
}
