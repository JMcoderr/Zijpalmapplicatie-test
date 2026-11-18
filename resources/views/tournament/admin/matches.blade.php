{{-- The organiser checks and corrects the generated schedule here. --}}
<x-page-wrapper page="Schema {{ $tournament->name }}">

    <x-zijpalm-div name="tournament-schedule-banner" color="transparent" title="Gegenereerd schema"
                   :editable="false" :text="$tournament->name"/>

    <p class="text-sm opacity-75">
        {{ $tournament->team_count }} teams &middot; {{ $tournament->group_count }} poules van
        {{ $tournament->group_size }} &middot; top {{ $tournament->qualifiers_per_group }} gaat door
        &middot; knockout met {{ $tournament->knockout_size }} teams
    </p>

    <div class="flex flex-wrap justify-center gap-2">
        <x-zijpalm-button label="Uitslagen invoeren" :href="route('tournament.admin.results', $tournament)"/>
        <x-zijpalm-button label="Deelnemers" :href="route('tournament.admin.participants', $tournament)"/>
        <x-zijpalm-button label="Terug naar beheer" :href="route('tournament.admin.index')"/>
    </div>

    {{-- Team names can still be corrected after generating. --}}
    <div class="flex justify-center p-5">
        <x-zijpalm-div :editable="false" color="light" title="Teams" class="w-full max-w-[40rem]">
            @foreach($tournament->groups as $group)
                <p class="mt-3 text-left font-semibold">Poule {{ $group->name }}</p>

                @foreach($group->teams()->orderBy('position_in_group')->get() as $team)
                    <form method="POST" action="{{ route('tournament.admin.updateTeam', $tournament) }}"
                          id="team-{{ $team->id }}" class="flex items-end gap-2 py-1">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="team_id" value="{{ $team->id }}">
                        <flux:input name="name" value="{{ $team->name }}" class="flex-1" required/>
                        <x-zijpalm-button type="submit" form="team-{{ $team->id }}" label="Opslaan"/>
                    </form>
                @endforeach
            @endforeach
        </x-zijpalm-div>
    </div>

    {{-- Every generated match, grouped per round. --}}
    <div class="flex flex-col items-center gap-4 p-5">
        @foreach($matches as $roundValue => $roundMatches)
            @php($round = \App\Enums\Tournament\MatchRound::from($roundValue))

            <x-zijpalm-div :editable="false" color="light" :title="$round->label()" class="w-full max-w-[44rem]">
                @foreach($roundMatches as $match)
                    <form method="POST" action="{{ route('tournament.admin.updateMatch', $match) }}"
                          id="match-{{ $match->id }}"
                          class="flex flex-wrap items-end justify-between gap-3 border-b py-2 text-left">
                        @csrf
                        @method('PUT')

                        <span class="flex-1">
                            {{ $match->homeLabel() }} - {{ $match->awayLabel() }}
                            @if($match->group)
                                <span class="text-xs opacity-75">(poule {{ $match->group->name }})</span>
                            @endif
                        </span>

                        <flux:input type="datetime-local" name="kickoff"
                                    value="{{ $match->kickoff->format('Y-m-d\TH:i') }}" required/>

                        <x-zijpalm-button type="submit" form="match-{{ $match->id }}" label="Opslaan"/>
                    </form>
                @endforeach
            </x-zijpalm-div>
        @endforeach
    </div>
</x-page-wrapper>
