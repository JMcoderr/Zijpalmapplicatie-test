{{-- Admin page where the organiser creates a tournament. --}}
<x-page-wrapper page="Toernooien beheren">

    <x-zijpalm-div name="tournament-admin-banner" color="transparent" title="Toernooien beheren" :editable="false"/>

    @error('team_count')
        <x-zijpalm-div color="light" :editable="false" :text="$message" width="w-max" error/>
    @enderror

    <div class="flex justify-center p-5">
        <x-zijpalm-div :editable="false" color="light" title="Nieuw toernooi" class="w-full max-w-[36rem]">
            <form method="POST" action="{{ route('tournament.admin.store') }}" id="new-tournament"
                  class="flex flex-col gap-3 text-left">
                @csrf

                <flux:input name="name" label="Naam" value="{{ old('name') }}" maxlength="150" required/>

                <flux:textarea name="description" label="Omschrijving">{{ old('description') }}</flux:textarea>

                <flux:select name="sport" label="Sport" required>
                    @foreach($sports as $key => $label)
                        <option value="{{ $key }}" @selected(old('sport') === $key)>{{ $label }}</option>
                    @endforeach
                </flux:select>

                <flux:select name="team_count" label="Aantal teams" required
                             description="Alleen aantallen die een sluitend schema opleveren.">
                    @foreach($validTeamCounts as $count)
                        <option value="{{ $count }}" @selected(old('team_count', 16) == $count)>
                            {{ $count }} teams
                        </option>
                    @endforeach
                </flux:select>

                <flux:input type="datetime-local" name="starts_at" label="Start toernooi"
                            value="{{ old('starts_at') }}" required
                            description="Inschrijving en voorspellingen sluiten op dit moment."/>

                <flux:textarea name="team_names" label="Teamnamen (optioneel, een per regel)"
                               rows="5">{{ old('team_names') }}</flux:textarea>

                <x-zijpalm-button type="submit" form="new-tournament" label="Toernooi aanmaken"/>
            </form>
        </x-zijpalm-div>
    </div>

    <div class="flex flex-wrap justify-center gap-4 p-5">
        @forelse($tournaments as $tournament)
            <x-zijpalm-div :editable="false" color="light" :title="$tournament->name" class="max-w-[24rem] w-full">
                <p class="text-sm opacity-75">
                    {{ $tournament->teams_count }} teams &middot; {{ $tournament->matches_count }} wedstrijden<br>
                    Start {{ $tournament->starts_at->format('d-m-Y H:i') }}
                </p>

                <div class="mt-3 flex flex-wrap justify-center gap-2">
                    <x-zijpalm-button label="Schema" :href="route('tournament.admin.matches', $tournament)"/>
                    <x-zijpalm-button label="Uitslagen" :href="route('tournament.admin.results', $tournament)"/>
                    <x-zijpalm-button label="Deelnemers" :href="route('tournament.admin.participants', $tournament)"/>
                </div>

                <form method="POST" action="{{ route('tournament.admin.destroy', $tournament) }}"
                      id="delete-tournament-{{ $tournament->id }}" class="mt-2"
                      onsubmit="return confirm('Weet je zeker dat je dit toernooi wilt verwijderen?');">
                    @csrf
                    @method('DELETE')
                    <x-zijpalm-button type="submit" form="delete-tournament-{{ $tournament->id }}"
                                      variant="remove" label="Verwijderen"/>
                </form>
            </x-zijpalm-div>
        @empty
            <x-zijpalm-div :editable="false" color="light" title="Nog geen toernooien"
                           text="Maak hierboven het eerste toernooi aan."/>
        @endforelse
    </div>
</x-page-wrapper>
