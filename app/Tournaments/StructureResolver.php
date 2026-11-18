<?php
// Works out a valid group and knockout layout for a given number of teams.

namespace App\Tournaments;

class StructureResolver
{
    public const MIN_TEAMS = 8;
    public const MAX_TEAMS = 64;

    // Groups of four with two qualifiers is the most common setup, so try that first.
    private const GROUP_SIZES = [4, 5, 3, 6];
    private const QUALIFIERS = [2, 1];

    // The knockout stage cannot be bigger than a round of 32.
    private const MAX_KNOCKOUT = 32;

    public function resolve(int $teamCount): ?TournamentStructure
    {
        if ($teamCount < self::MIN_TEAMS || $teamCount > self::MAX_TEAMS) {
            return null;
        }

        foreach (self::GROUP_SIZES as $groupSize) {
            if ($teamCount % $groupSize !== 0) {
                continue;
            }

            $groupCount = intdiv($teamCount, $groupSize);

            if ($groupCount < 2) {
                continue;
            }

            foreach (self::QUALIFIERS as $qualifiers) {
                if ($qualifiers >= $groupSize) {
                    continue;
                }

                $knockoutSize = $groupCount * $qualifiers;

                if ($knockoutSize < 2 || $knockoutSize > self::MAX_KNOCKOUT) {
                    continue;
                }

                if (! $this->isPowerOfTwo($knockoutSize)) {
                    continue;
                }

                // With two qualifiers per group the bracket pairs groups two by two.
                if ($qualifiers === 2 && $groupCount % 2 !== 0) {
                    continue;
                }

                if ($qualifiers === 1 && $groupCount % 2 !== 0) {
                    continue;
                }

                return new TournamentStructure(
                    teamCount: $teamCount,
                    groupCount: $groupCount,
                    groupSize: $groupSize,
                    qualifiersPerGroup: $qualifiers,
                    knockoutSize: $knockoutSize,
                );
            }
        }

        return null;
    }

    public function supports(int $teamCount): bool
    {
        return $this->resolve($teamCount) !== null;
    }

    /** @return array<int> Every team count between 8 and 64 that produces a clean schedule. */
    public function validTeamCounts(): array
    {
        $counts = [];

        for ($teams = self::MIN_TEAMS; $teams <= self::MAX_TEAMS; $teams++) {
            if ($this->supports($teams)) {
                $counts[] = $teams;
            }
        }

        return $counts;
    }

    private function isPowerOfTwo(int $number): bool
    {
        return $number > 0 && ($number & ($number - 1)) === 0;
    }
}
