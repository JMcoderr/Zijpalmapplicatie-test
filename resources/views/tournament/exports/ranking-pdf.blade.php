{{-- Plain layout used for the PDF export of the ranking. --}}
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Ranglijst {{ $tournament->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        p.meta { color: #555; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border-bottom: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f2f2f2; }
        td.points { text-align: right; }
    </style>
</head>
<body>
    <h1>Ranglijst {{ $tournament->name }}</h1>
    <p class="meta">
        {{ $includeEmail ? 'Bestuursversie' : 'Openbare versie' }}
        &middot; gegenereerd op {{ now()->format('d-m-Y H:i') }}
    </p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Zelf gekozen naam</th>
                @if($includeEmail)
                    <th>E-mailadres</th>
                @endif
                <th class="points">Punten</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ranking as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row->display_name }}</td>
                    @if($includeEmail)
                        <td>{{ $row->email }}</td>
                    @endif
                    <td class="points">{{ $row->points }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
