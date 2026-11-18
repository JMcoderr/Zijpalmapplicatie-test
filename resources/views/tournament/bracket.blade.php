{{-- Knockout bracket. A round only opens once the previous one is fully processed. --}}
<x-page-wrapper page="Knockoutfase {{ $tournament->name }}">

    <x-zijpalm-div name="tournament-bracket-banner" color="transparent" title="Knockoutfase" :editable="false"
                   :text="$tournament->name"/>

    <div class="flex flex-wrap justify-center gap-2">
        <x-zijpalm-button label="Poulefase" :href="route('tournament.show', $tournament)"/>
        <x-zijpalm-button label="Stand" :href="route('tournament.ranking', $tournament)"/>
    </div>

    @error('deadline')
        <x-zijpalm-div color="light" :editable="false" :text="$message" width="w-max" error/>
    @enderror

    @php($tournamentLocked = $tournament->starts_at->isPast())

    <div class="flex flex-col items-center gap-6 p-5">
        @foreach(\App\Enums\Tournament\MatchRound::knockoutOrder() as $round)
            @continue(! isset($rounds[$round->value]))

            @php($isOpen = $openRounds[$round->value] ?? false)

            <x-zijpalm-div :editable="false" color="light" :title="$round->label()" class="w-full max-w-[44rem]">

                @unless($isOpen)
                    <p class="text-sm opacity-75">
                        Deze ronde opent zodra de vorige ronde volledig is verwerkt.
                    </p>
                @endunless

                <div class="mt-3 flex flex-col gap-4">
                    @foreach($rounds[$round->value] as $match)
                        <div class="border-t pt-3">
                            <p class="text-xs opacity-75">{{ $match->kickoff->format('d-m-Y H:i') }}</p>

                            <x-prediction-form
                                :match="$match"
                                :prediction="$myPredictions[$match->id] ?? null"
                                :locked="$tournamentLocked || ! $isOpen || ! $match->isReadyForPredictions()"
                                :lockReason="$isOpen ? 'De deadline van het toernooi is verstreken.' : 'De vorige ronde is nog niet afgerond.'"/>
                        </div>
                    @endforeach
                </div>
            </x-zijpalm-div>
        @endforeach
    </div>
</x-page-wrapper>
