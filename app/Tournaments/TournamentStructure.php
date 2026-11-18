<?php
// Describes how a tournament is put together: how many groups, how big they are,
// how many teams qualify and how large the knockout bracket becomes.

namespace App\Tournaments;

final class TournamentStructure
{
    public function __construct(
        public readonly int $teamCount,
        public readonly int $groupCount,
        public readonly int $groupSize,
        public readonly int $qualifiersPerGroup,
        public readonly int $knockoutSize,
    ) {
    }

    // Short description shown to the organiser before the tournament is created.
    public function summary(): string
    {
        return sprintf(
            '%d teams, %d poules van %d, top %d gaat door, knockout met %d teams',
            $this->teamCount,
            $this->groupCount,
            $this->groupSize,
            $this->qualifiersPerGroup,
            $this->knockoutSize
        );
    }

    public function toArray(): array
    {
        return [
            'team_count' => $this->teamCount,
            'group_count' => $this->groupCount,
            'group_size' => $this->groupSize,
            'qualifiers_per_group' => $this->qualifiersPerGroup,
            'knockout_size' => $this->knockoutSize,
        ];
    }
}
