<?php

namespace App\Exports\Charity;

use App\Models\Charity\CharityAdmin;
use App\Models\Charity\Member;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Style;

class MemberExport extends StringValueBinder implements FromQuery, WithColumnWidths, WithCustomValueBinder, WithDefaultStyles, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private Builder $members) {}

    public function query(): Builder
    {
        return $this->members->addSelect('members.*')->selectSub(
            CharityAdmin::query()
                ->selectRaw('1')
                ->whereColumn('admins.employee', 'members.email')
                ->limit(1),
            'export_is_admin',
        );
    }

    public function headings(): array
    {
        return [
            'First name',
            'Last name',
            'Email',
            'Phone',
            'Type',
            'Membership start date',
            'Employee number',
            'Contribution',
            'Is admin',
            'In dienst sinds',
            'Geboortedatum',
        ];
    }

    public function map($row): array
    {
        return [
            $row->name,
            $this->lastName($row),
            $row->email,
            null,
            null,
            null,
            $row->id,
            null,
            $row->export_is_admin ? 'yes' : 'no',
            $row->years_of_service ? Carbon::parse($row->years_of_service)->format('d-m-Y') : null,
            $row->dob ? Carbon::parse($row->dob)->format('d-m-Y') : null,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15.282,
            'B' => 34.135,
            'C' => 38.848,
            'D' => 12.854,
            'E' => 17.567,
            'F' => 25.851,
            'G' => 18.710,
            'H' => 15.282,
            'I' => 10.569,
            'J' => 18.710,
            'K' => 18.710,
        ];
    }

    public function title(): string
    {
        return 'Leden';
    }

    public function defaultStyles(Style $defaultStyle): array
    {
        return ['font' => ['name' => 'Calibri', 'size' => 11, 'bold' => false]];
    }

    private function lastName(Member $member): ?string
    {
        if ($member->full_name === null) {
            return null;
        }

        $fullName = trim($member->full_name);
        if (str_contains($fullName, ',')) {
            return trim(explode(',', $fullName, 2)[0]);
        }

        $firstName = trim($member->name ?? '');
        if ($firstName !== '' && str_starts_with($fullName, $firstName.' ')) {
            return trim(substr($fullName, strlen($firstName) + 1));
        }

        return $fullName;
    }
}