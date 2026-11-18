<?php
// Keeps track of the available sports so the rest of the app can look one up by key.

namespace App\Sports;

use InvalidArgumentException;

class SportRegistry
{
    /** @var array<string, SportRules> */
    private array $sports = [];

    public function register(SportRules $sport): void
    {
        $this->sports[$sport->key()] = $sport;
    }

    public function get(string $key): SportRules
    {
        if (! isset($this->sports[$key])) {
            throw new InvalidArgumentException("Onbekend sporttype: {$key}.");
        }

        return $this->sports[$key];
    }

    public function has(string $key): bool
    {
        return isset($this->sports[$key]);
    }

    /** @return array<string, SportRules> */
    public function all(): array
    {
        return $this->sports;
    }

    // Key => label, handy for a dropdown in the tournament form.
    public function options(): array
    {
        return array_map(fn (SportRules $sport) => $sport->label(), $this->sports);
    }
}
