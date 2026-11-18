<?php
// Public ranking plus the Excel and PDF exports.

namespace App\Http\Controllers\Tournament;

use App\Exports\Tournament\RankingExport;
use App\Http\Controllers\Controller;
use App\Models\Tournament\Tournament;
use App\Tournaments\Ranking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class RankingController extends Controller
{
    public function __construct(private readonly Ranking $ranking)
    {
    }

    public function index(Tournament $tournament): View
    {
        return view('tournament.ranking', [
            'tournament' => $tournament,
            'ranking' => $this->ranking->for($tournament),
        ]);
    }

    public function excel(Request $request, Tournament $tournament): Response
    {
        $includeEmail = $this->wantsBoardVersion($request);

        return Excel::download(
            new RankingExport($tournament, $includeEmail),
            $this->filename($tournament, $includeEmail, 'xlsx')
        );
    }

    public function pdf(Request $request, Tournament $tournament): Response
    {
        $includeEmail = $this->wantsBoardVersion($request);

        $pdf = Pdf::loadView('tournament.exports.ranking-pdf', [
            'tournament' => $tournament,
            'ranking' => $this->ranking->for($tournament, $includeEmail),
            'includeEmail' => $includeEmail,
        ]);

        return $pdf->download($this->filename($tournament, $includeEmail, 'pdf'));
    }

    // Only admins may download the version that contains email addresses.
    private function wantsBoardVersion(Request $request): bool
    {
        if (! $request->boolean('bestuur')) {
            return false;
        }

        abort_unless($request->user()?->is_admin, 403, 'Alleen beheerders mogen de bestuurslijst downloaden.');

        return true;
    }

    private function filename(Tournament $tournament, bool $includeEmail, string $extension): string
    {
        $slug = str($tournament->name)->slug();
        $type = $includeEmail ? 'bestuur' : 'openbaar';

        return "ranglijst-{$slug}-{$type}.{$extension}";
    }
}
