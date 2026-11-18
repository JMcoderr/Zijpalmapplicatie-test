<?php
// Excel export of the ranking. The board version adds the email address.

namespace App\Exports\Tournament;

use App\Models\Tournament\Tournament;
use App\Tournaments\Ranking;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class RankingExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        private readonly Tournament $tournament,
        private readonly bool $includeEmail = false,
    ) {
    }

    public function collection(): Collection
    {
        return app(Ranking::class)
            ->for($this->tournament, $this->includeEmail)
            ->values()
            ->map(function ($row, $index) {
                $line = [
                    'position' => $index + 1,
                    'display_name' => $row->display_name,
                    'points' => (int) $row->points,
                ];

                if ($this->includeEmail) {
                    $line['email'] = $row->email;
                }

                return $line;
            });
    }

    public function headings(): array
    {
        $headings = ['#', 'Zelf gekozen naam', 'Punten'];

        if ($this->includeEmail) {
            $headings[] = 'E-mailadres';
        }

        return $headings;
    }
}
