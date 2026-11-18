{{-- Group stage: the table and the matches of every group. --}}
<x-page-wrapper page="{{ $tournament->name }}">

    <x-zijpalm-div name="tournament-show-banner" color="transparent" :title="$tournament->name" :editable="false"
                   :text="$tournament->description"/>

    <div class="flex flex-wrap justify-center gap-2">
        <x-zijpalm-button label="Knockoutfase" :href="route('tournament.bracket', $tournament)"/>
        <x-zijpalm-button label="Stand" :href="route('tournament.ranking', $tournament)"/>
        @auth
            <x-zijpalm-button label="Mijn voorspellingen" :href="route('tournament.predictions')"/>
        @endauth
    </div>

    @error('deadline')
        <x-zijpalm-div color="light" :editable="false" :text="$message" width="w-max" error/>
    @enderror

    @php($locked = $tournament->starts_at->isPast())

    @if($locked)
        <p class="text-sm opacity-75">
            Het toernooi is gestart op {{ $tournament->starts_at->format('d-m-Y H:i') }},
            voorspellingen zijn definitief.
        </p>
    @else
        <p class="text-sm opacity-75">
            Je kunt voorspellen tot {{ $tournament->starts_at->format('d-m-Y H:i') }}.
        </p>
    @endif

    {{-- Registration for this tournament --}}
    @auth
        @if(! $locked)
            <form method="POST" action="{{ route('tournament.join', $tournament) }}" id="join-tournament">
                @csrf
                <x-zijpalm-button type="submit" form="join-tournament" label="Inschrijven" center="horizontal"/>
            </form>
        @endif
    @endauth

    <div class="flex flex-col items-center gap-6 p-5">
        @foreach($tournament->groups as $group)
            <x-zijpalm-div :editable="false" color="light" :title="'Poule '.$group->name"
                           class="w-full max-w-[44rem]">

                {{-- Standings of this group --}}
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="p-1">Team</th>
                            <th class="p-1">G</th>
                            <th class="p-1">W</th>
                            <th class="p-1">GL</th>
                            <th class="p-1">V</th>
                            <th class="p-1">DS</th>
                            <th class="p-1 text-right">Ptn</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tables[$group->id] as $index => $row)
                            {{-- Highlight the teams that qualify for the knockout stage. --}}
                            <tr @class(['border-b', 'font-bold' => $index < $tournament->qualifiers_per_group])>
                                <td class="p-1">{{ $row['team']->name }}</td>
                                <td class="p-1">{{ $row['played'] }}</td>
                                <td class="p-1">{{ $row['won'] }}</td>
                                <td class="p-1">{{ $row['drawn'] }}</td>
                                <td class="p-1">{{ $row['lost'] }}</td>
                                <td class="p-1">{{ $row['goal_difference'] }}</td>
                                <td class="p-1 text-right">{{ $row['points'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Matches of this group --}}
                <div class="mt-4 flex flex-col gap-4">
                    @foreach($groupMatches[$group->id] ?? [] as $match)
                        <div class="border-t pt-3">
                            <p class="text-xs opacity-75">{{ $match->kickoff->format('d-m-Y H:i') }}</p>

                            <x-prediction-form :match="$match"
                                               :prediction="$myPredictions[$match->id] ?? null"
                                               :locked="$locked"
                                               lockReason="De deadline van het toernooi is verstreken."/>
                        </div>
                    @endforeach
                </div>
            </x-zijpalm-div>
        @endforeach
    </div>
</x-page-wrapper>
