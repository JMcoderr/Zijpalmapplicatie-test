{{-- Admin page for entering the official results. --}}
<x-page-wrapper page="Uitslagen {{ $tournament->name }}">

    <x-zijpalm-div name="tournament-results-banner" color="transparent" title="Uitslagen invoeren"
                   :editable="false" :text="$tournament->name"/>

    <div class="flex flex-wrap justify-center gap-2">
        <x-zijpalm-button label="Schema" :href="route('tournament.admin.matches', $tournament)"/>
        <x-zijpalm-button label="Stand" :href="route('tournament.ranking', $tournament)"/>
    </div>

    @error('result')
        <x-zijpalm-div color="light" :editable="false" :text="$message" width="w-max" error/>
    @enderror

    @error('penalty_winner_team_id')
        <x-zijpalm-div color="light" :editable="false" :text="$message" width="w-max" error/>
    @enderror

    @unless($groupStageComplete)
        <p class="text-sm opacity-75">
            De knockoutfase krijgt pas teams zodra alle poulewedstrijden een uitslag hebben.
        </p>
    @endunless

    <div class="flex flex-col items-center gap-4 p-5">
        @foreach($matches as $roundValue => $roundMatches)
            @php($round = \App\Enums\Tournament\MatchRound::from($roundValue))

            <x-zijpalm-div :editable="false" color="light" :title="$round->label()" class="w-full max-w-[44rem]">
                @foreach($roundMatches as $match)
                    <div class="border-b py-3 text-left">
                        <p class="text-xs opacity-75">{{ $match->kickoff->format('d-m-Y H:i') }}</p>

                        <p class="font-semibold">{{ $match->homeLabel() }} - {{ $match->awayLabel() }}</p>

                        @if($match->isReadyForPredictions())
                            <form method="POST" action="{{ route('tournament.admin.storeResult', $match) }}"
                                  id="result-{{ $match->id }}"
                                  class="mt-2 flex flex-wrap items-end gap-3">
                                @csrf

                                <flux:input type="number" name="home_score" min="0" max="99" class="w-20"
                                            label="Thuis" value="{{ $match->home_score }}" required/>

                                <flux:input type="number" name="away_score" min="0" max="99" class="w-20"
                                            label="Uit" value="{{ $match->away_score }}" required/>

                                {{-- A knockout match cannot end in a draw, so penalties decide. --}}
                                @if($round->isKnockout())
                                    <flux:select name="penalty_winner_team_id" label="Winnaar penalty's" class="w-40">
                                        <option value="">Geen penalty's</option>
                                        <option value="{{ $match->home_team_id }}"
                                                @selected($match->penalty_winner_team_id === $match->home_team_id)>
                                            {{ $match->homeLabel() }}
                                        </option>
                                        <option value="{{ $match->away_team_id }}"
                                                @selected($match->penalty_winner_team_id === $match->away_team_id)>
                                            {{ $match->awayLabel() }}
                                        </option>
                                    </flux:select>
                                @endif

                                <x-zijpalm-button type="submit" form="result-{{ $match->id }}"
                                                  label="{{ $match->hasResult() ? 'Wijzigen' : 'Opslaan' }}"/>
                            </form>

                            @if($match->result_entered_at)
                                <p class="mt-1 text-xs opacity-75">
                                    Ingevoerd op {{ $match->result_entered_at->format('d-m-Y H:i') }}
                                </p>
                            @endif
                        @else
                            <p class="text-sm opacity-75">Wacht op de teams uit de vorige ronde.</p>
                        @endif
                    </div>
                @endforeach
            </x-zijpalm-div>
        @endforeach
    </div>
</x-page-wrapper>
