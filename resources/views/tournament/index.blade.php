{{-- Overview of all tournaments. --}}
<x-page-wrapper page="Voetbalpoule">

    <x-zijpalm-div name="tournament-index-banner" color="transparent" title="Voetbalpoule" :editable="false"
                   text="Voorspel de uitslagen en strijd om de eerste plaats."/>

    @auth
        @if(auth()->user()->is_admin)
            <x-zijpalm-button label="Toernooien beheren" :href="route('tournament.admin.index')" center="horizontal"/>
        @endif
    @endauth

    <div class="flex flex-wrap justify-center gap-4 p-5">
        @forelse($tournaments as $tournament)
            <x-zijpalm-div :editable="false" color="light" :title="$tournament->name"
                           class="flex flex-col items-center justify-between gap-3 max-w-[24rem] w-full">
                @if($tournament->description)
                    <p class="text-sm">{{ $tournament->description }}</p>
                @endif

                <p class="text-sm opacity-75">
                    {{ $tournament->teams_count }} teams &middot; {{ $tournament->group_count }} poules
                    &middot; start {{ $tournament->starts_at->format('d-m-Y H:i') }}
                </p>

                <p class="text-sm">{{ $tournament->status->label() }}</p>

                <div class="flex flex-wrap justify-center gap-2">
                    <x-zijpalm-button label="Poulefase" :href="route('tournament.show', $tournament)"/>
                    <x-zijpalm-button label="Knockout" :href="route('tournament.bracket', $tournament)"/>
                    <x-zijpalm-button label="Stand" :href="route('tournament.ranking', $tournament)"/>
                </div>
            </x-zijpalm-div>
        @empty
            <x-zijpalm-div :editable="false" color="light" title="Nog geen toernooien"
                           text="Er is op dit moment geen toernooi."/>
        @endforelse
    </div>
</x-page-wrapper>
