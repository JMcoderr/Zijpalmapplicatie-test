{{-- Prediction form for one match. Used by both the group stage and the bracket. --}}
@props([
    'match',
    'prediction' => null,
    'locked' => false,
    'lockReason' => null,
])

<div class="flex flex-col gap-2">
    <div class="flex items-center justify-center gap-3 text-lg font-semibold">
        <span>{{ $match->homeLabel() }}</span>
        <span>
            @if($match->hasResult())
                {{ $match->home_score }} - {{ $match->away_score }}
            @else
                vs
            @endif
        </span>
        <span>{{ $match->awayLabel() }}</span>
    </div>

    @if($match->hasResult() && $match->penalty_winner_team_id)
        <p class="text-xs opacity-75">{{ $match->penaltyWinner?->name }} wint na penalty's</p>
    @endif

    @guest
        <p class="text-sm">Log in om een voorspelling te doen.</p>
    @endguest

    @auth
        @if($locked)
            <p class="text-sm">
                @if($prediction)
                    Jouw voorspelling: {{ $prediction->home_score }} - {{ $prediction->away_score }}
                    @if($prediction->penaltyWinner)
                        (penalty's: {{ $prediction->penaltyWinner->name }})
                    @endif
                    @if($match->hasResult())
                        &middot; <strong>{{ $prediction->points }} punten</strong>
                    @endif
                @else
                    Je hebt deze wedstrijd niet voorspeld.
                @endif
            </p>

            @if($lockReason)
                <p class="text-xs opacity-75">{{ $lockReason }}</p>
            @endif
        @else
            <form method="POST" action="{{ route('tournament.prediction.store', $match) }}"
                  id="prediction-{{ $match->id }}" class="flex flex-wrap items-end justify-center gap-3">
                @csrf

                <flux:input type="number" name="home_score" min="0" max="99" class="w-20"
                            :label="$match->homeLabel()"
                            value="{{ old('home_score', $prediction?->home_score) }}" required/>

                <flux:input type="number" name="away_score" min="0" max="99" class="w-20"
                            :label="$match->awayLabel()"
                            value="{{ old('away_score', $prediction?->away_score) }}" required/>

                {{-- A knockout match always needs a winner, so a draw needs a penalty pick. --}}
                @if($match->round->isKnockout())
                    <flux:select name="penalty_winner_team_id" label="Winnaar penalty's" class="w-40">
                        <option value="">Geen penalty's</option>
                        <option value="{{ $match->home_team_id }}"
                                @selected($prediction?->penalty_winner_team_id === $match->home_team_id)>
                            {{ $match->homeLabel() }}
                        </option>
                        <option value="{{ $match->away_team_id }}"
                                @selected($prediction?->penalty_winner_team_id === $match->away_team_id)>
                            {{ $match->awayLabel() }}
                        </option>
                    </flux:select>
                @endif

                <x-zijpalm-button type="submit" form="prediction-{{ $match->id }}"
                                  label="{{ $prediction ? 'Wijzigen' : 'Voorspellen' }}"/>
            </form>
        @endif
    @endauth
</div>
