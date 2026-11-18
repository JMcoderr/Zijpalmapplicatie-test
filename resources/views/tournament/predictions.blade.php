{{-- Overview of the predictions of the logged in participant. --}}
<x-page-wrapper page="Mijn voorspellingen">

    <x-zijpalm-div name="tournament-predictions-banner" color="transparent" title="Mijn voorspellingen"
                   :editable="false" text="Alle voorspellingen die je hebt ingevoerd."/>

    <x-zijpalm-button label="Naar de toernooien" :href="route('tournament.index')" center="horizontal"/>

    <div class="flex flex-col items-center gap-4 p-5">
        @forelse($predictions as $prediction)
            @php($match = $prediction->match)

            <x-zijpalm-div :editable="false" color="light" title="" class="w-full max-w-[36rem]">
                <div class="flex flex-col gap-2">
                    <p class="text-sm opacity-75">
                        {{ $match->tournament->name }} &middot; {{ $match->round->label() }}
                        &middot; {{ $match->kickoff->format('d-m-Y H:i') }}
                    </p>

                    <div class="flex items-center justify-center gap-3 font-semibold">
                        <span>{{ $match->homeLabel() }}</span>
                        <span>{{ $prediction->home_score }} - {{ $prediction->away_score }}</span>
                        <span>{{ $match->awayLabel() }}</span>
                    </div>

                    @if($prediction->penaltyWinner)
                        <p class="text-xs opacity-75">Penalty's: {{ $prediction->penaltyWinner->name }}</p>
                    @endif

                    @if($match->hasResult())
                        <p class="text-sm">
                            Uitslag: {{ $match->home_score }} - {{ $match->away_score }}
                            &middot; <strong>{{ $prediction->points }} punten</strong>
                        </p>
                    @else
                        <p class="text-sm opacity-75">Wachten op de uitslag.</p>
                    @endif
                </div>
            </x-zijpalm-div>
        @empty
            <x-zijpalm-div :editable="false" color="light" title="Nog niets voorspeld"
                           text="Je hebt nog geen voorspellingen ingevoerd."/>
        @endforelse
    </div>
</x-page-wrapper>
