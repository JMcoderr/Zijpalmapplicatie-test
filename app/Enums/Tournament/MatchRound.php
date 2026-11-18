<?php
// The rounds a match can belong to. The order of the cases is the order they are played.

namespace App\Enums\Tournament;

enum MatchRound: string
{
    case Group = 'group';
    case RoundOf32 = 'round_of_32';
    case RoundOf16 = 'round_of_16';
    case QuarterFinal = 'quarter_final';
    case SemiFinal = 'semi_final';
    case Final = 'final';

    public function label(): string
    {
        return match ($this) {
            self::Group => 'Poulefase',
            self::RoundOf32 => 'Zestiende finales',
            self::RoundOf16 => 'Achtste finales',
            self::QuarterFinal => 'Kwartfinales',
            self::SemiFinal => 'Halve finales',
            self::Final => 'Finale',
        };
    }

    public function isKnockout(): bool
    {
        return $this !== self::Group;
    }

    // How many matches this round has, based on the number of teams left.
    public static function forTeamCount(int $teams): self
    {
        return match ($teams) {
            32 => self::RoundOf32,
            16 => self::RoundOf16,
            8 => self::QuarterFinal,
            4 => self::SemiFinal,
            2 => self::Final,
            default => throw new \InvalidArgumentException("Geen knockoutronde voor {$teams} teams."),
        };
    }

    // Knockout rounds in the order they are played.
    public static function knockoutOrder(): array
    {
        return [self::RoundOf32, self::RoundOf16, self::QuarterFinal, self::SemiFinal, self::Final];
    }
}
