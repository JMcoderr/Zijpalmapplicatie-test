{{-- Public ranking. Only the chosen screen name and the points are shown. --}}
<x-page-wrapper page="Stand {{ $tournament->name }}">

    <x-zijpalm-div name="tournament-ranking-banner" color="transparent" title="Ranglijst" :editable="false"
                   :text="$tournament->name"/>

    <div class="flex flex-wrap justify-center gap-2">
        <x-zijpalm-button label="Poulefase" :href="route('tournament.show', $tournament)"/>
        <x-zijpalm-button label="Knockout" :href="route('tournament.bracket', $tournament)"/>
        <x-zijpalm-button label="Excel" :href="route('tournament.ranking.excel', $tournament)"/>
        <x-zijpalm-button label="PDF" :href="route('tournament.ranking.pdf', $tournament)"/>
    </div>

    @auth
        @if(auth()->user()->is_admin)
            <div class="flex flex-wrap justify-center gap-2">
                <x-zijpalm-button label="Excel (bestuur)"
                                  :href="route('tournament.ranking.excel', [$tournament, 'bestuur' => 1])"/>
                <x-zijpalm-button label="PDF (bestuur)"
                                  :href="route('tournament.ranking.pdf', [$tournament, 'bestuur' => 1])"/>
            </div>
        @endif
    @endauth

    <div class="flex justify-center p-5">
        <x-zijpalm-div :editable="false" color="light" title="" class="w-full max-w-[40rem]">
            <p class="mb-3 text-sm opacity-75">
                {{ $ranking->count() }} {{ Str::plural('deelnemer', $ranking->count()) }}
                &middot; 1 punt voor de juiste uitslag, 4 punten voor de exacte score
            </p>

            {{-- Scrollable on small screens so the table stays readable on mobile. --}}
            <div class="overflow-x-auto">
                <table class="w-full min-w-[20rem] text-left">
                    <thead>
                        <tr class="border-b">
                            <th class="p-2">#</th>
                            <th class="p-2">Zelf gekozen naam</th>
                            <th class="p-2 text-right">Punten</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ranking as $index => $row)
                            <tr @class(['border-b', 'font-bold' => auth()->id() === $row->user_id])>
                                <td class="p-2">{{ $index + 1 }}</td>
                                <td class="p-2">{{ $row->display_name }}</td>
                                <td class="p-2 text-right">{{ $row->points }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="p-2">Er zijn nog geen deelnemers.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-zijpalm-div>
    </div>
</x-page-wrapper>
