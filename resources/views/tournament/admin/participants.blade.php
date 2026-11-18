{{-- Admin overview of the participants, with payment status and removal. --}}
<x-page-wrapper page="Deelnemers {{ $tournament->name }}">

    <x-zijpalm-div name="tournament-participants-banner" color="transparent" title="Deelnemers"
                   :editable="false" :text="$tournament->name"/>

    <div class="flex flex-wrap justify-center gap-2">
        <x-zijpalm-button label="Schema" :href="route('tournament.admin.matches', $tournament)"/>
        <x-zijpalm-button label="Uitslagen" :href="route('tournament.admin.results', $tournament)"/>
    </div>

    @error('participants')
        <x-zijpalm-div color="light" :editable="false" :text="$message" width="w-max" error/>
    @enderror

    @if($tournament->hasScoringStarted())
        <p class="text-sm opacity-75">
            De puntentelling is gestart op {{ $tournament->scoring_started_at->format('d-m-Y H:i') }},
            deelnemers kunnen niet meer worden verwijderd.
        </p>
    @elseif(! $tournament->isOpenForRegistration())
        <p class="text-sm opacity-75">De inschrijving is gesloten.</p>
    @endif

    <div class="flex justify-center p-5">
        <x-zijpalm-div :editable="false" color="light" title="" class="w-full max-w-[48rem]">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[32rem] text-left">
                    <thead>
                        <tr class="border-b">
                            <th class="p-2">Schermnaam</th>
                            <th class="p-2">Echte naam</th>
                            <th class="p-2">E-mailadres</th>
                            <th class="p-2">Betaald</th>
                            <th class="p-2">Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($participants as $participant)
                            <tr class="border-b">
                                <td class="p-2">{{ $participant->publicName() }}</td>
                                <td class="p-2">{{ $participant->name }}</td>
                                <td class="p-2">{{ $participant->email }}</td>
                                <td class="p-2">
                                    {{ $participant->pivot->paid_at ? 'Ja' : 'Nee' }}
                                </td>
                                <td class="p-2">
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST"
                                              action="{{ route('tournament.admin.togglePaid', [$tournament, $participant]) }}"
                                              id="paid-{{ $participant->id }}">
                                            @csrf
                                            @method('PUT')
                                            <x-zijpalm-button type="submit" form="paid-{{ $participant->id }}"
                                                              label="{{ $participant->pivot->paid_at ? 'Markeer onbetaald' : 'Markeer betaald' }}"/>
                                        </form>

                                        @unless($tournament->hasScoringStarted())
                                            <form method="POST"
                                                  action="{{ route('tournament.admin.removeParticipant', [$tournament, $participant]) }}"
                                                  id="remove-{{ $participant->id }}"
                                                  onsubmit="return confirm('Weet je zeker dat je {{ $participant->publicName() }} wilt verwijderen?');">
                                                @csrf
                                                @method('DELETE')
                                                <x-zijpalm-button type="submit" form="remove-{{ $participant->id }}"
                                                                  variant="remove" label="Verwijderen"/>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-2">Nog geen deelnemers ingeschreven.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-zijpalm-div>
    </div>
</x-page-wrapper>
