<?php
// Admin page for the participant list, payment status and removing non payers.

namespace App\Http\Controllers\Tournament;

use App\Http\Controllers\Controller;
use App\Models\Tournament\Tournament;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParticipantController extends Controller
{
    public function index(Tournament $tournament): View
    {
        return view('tournament.admin.participants', [
            'tournament' => $tournament,
            'participants' => $tournament->participants()->orderBy('display_name')->get(),
        ]);
    }

    // A participant signs themselves up, as long as registration is still open.
    public function join(Request $request, Tournament $tournament): RedirectResponse
    {
        if (! $tournament->isOpenForRegistration()) {
            return back()->withErrors([
                'registration' => 'De inschrijving is gesloten, het toernooi is al begonnen.',
            ]);
        }

        $tournament->participants()->syncWithoutDetaching([$request->user()->id]);

        return back()->with('success', 'Je bent ingeschreven voor dit toernooi.');
    }

    public function togglePaid(Tournament $tournament, User $user): RedirectResponse
    {
        $participant = $tournament->participants()->where('users.id', $user->id)->firstOrFail();

        $tournament->participants()->updateExistingPivot($user->id, [
            'paid_at' => $participant->pivot->paid_at ? null : now(),
        ]);

        return back()->with('success', 'Betalingsstatus bijgewerkt.');
    }

    public function destroy(Tournament $tournament, User $user): RedirectResponse
    {
        // Once the first result is in, the standings are running and nobody may be removed.
        if ($tournament->hasScoringStarted()) {
            return back()->withErrors([
                'participants' => 'De puntentelling is gestart, deelnemers kunnen niet meer worden verwijderd.',
            ]);
        }

        $tournament->participants()->detach($user->id);

        return back()->with('success', 'Deelnemer verwijderd.');
    }
}
