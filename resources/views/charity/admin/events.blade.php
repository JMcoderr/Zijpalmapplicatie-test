{{-- Managing events and the amounts that belong to them. --}}
<x-charity-wrapper page="Gebeurtenissen">
    <header class="charity-page-heading">
        <h2>Gebeurtenissen</h2>
        <p>Stel per gebeurtenis het bedrag en de geldigheidsperiode in.</p>
    </header>

    <div class="charity-admin-layout">
        <section class="charity-admin-section" aria-labelledby="new-event-heading">
            <div class="charity-admin-section-heading">
                <div>
                    <h3 id="new-event-heading">Gebeurtenis toevoegen</h3>
                    <p class="charity-section-intro">Bijvoorbeeld een geboorte, huwelijk of jubileum.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('charity.admin.events.store') }}" id="new-event"
                  class="charity-admin-form charity-admin-form-inline" data-charity-form data-submit-label="Gebeurtenis toevoegen">
                @csrf
                <flux:input id="new-event-title" name="title" label="Naam van de gebeurtenis" maxlength="50" value="{{ old('title') }}" placeholder="Bijvoorbeeld: geboorte" required/>
                <flux:button type="submit" variant="primary" icon="plus" data-submit-button><span data-submit-label>Toevoegen</span></flux:button>
            </form>
        </section>

        <section class="charity-admin-section charity-events-list-section" aria-labelledby="events-list-heading">
            <div class="charity-admin-section-heading">
                <h3 id="events-list-heading">Gebeurtenissen en bedragen</h3>
                <span class="charity-admin-count">{{ $events->count() }} {{ $events->count() === 1 ? 'gebeurtenis' : 'gebeurtenissen' }}</span>
            </div>

            <div class="charity-admin-list charity-events-list">
                @forelse($events as $event)
                    <article class="charity-admin-record charity-admin-event">
                        <header class="charity-admin-record-heading">
                            <h4>{{ $event->title }}</h4>
                            <p class="charity-event-cost-count">{{ $event->eventCosts->count() }} {{ $event->eventCosts->count() === 1 ? 'bedrag' : 'bedragen' }}</p>
                        </header>

                        @forelse($event->eventCosts as $cost)
                            @php
                                $costStatus = $cost->status;
                                $startDate = \Illuminate\Support\Carbon::parse($cost->start_date);
                                $endDate = $cost->end_date ? \Illuminate\Support\Carbon::parse($cost->end_date) : null;
                            @endphp
                            <div class="charity-event-cost-row">
                                <div class="charity-event-cost-info">
                                    <strong class="charity-event-cost-amount">&euro; {{ number_format($cost->amount, 2, ',', '.') }}</strong>
                                    <dl class="charity-event-cost-period">
                                        <div><dt>Vanaf</dt><dd><time datetime="{{ $startDate->toIso8601String() }}">{{ $startDate->format('d-m-Y H:i') }}</time></dd></div>
                                        <div><dt>Tot en met</dt><dd>@if($endDate)<time datetime="{{ $endDate->toIso8601String() }}">{{ $endDate->format('d-m-Y H:i') }}</time>@else Geen einddatum @endif</dd></div>
                                    </dl>
                                </div>
                                <span class="charity-admin-status charity-event-status charity-event-status-{{ $costStatus->value }}">{{ $costStatus->label() }}</span>
                                @if($costStatus === \App\Enums\Charity\EventCostStatus::Active)
                                    <form method="POST" action="{{ route('charity.admin.events.costs.destroy', [$event, $cost]) }}"
                                          id="stop-cost-{{ $cost->id }}" class="charity-event-cost-actions"
                                          data-charity-form data-submit-label="Bedrag stoppen"
                                          data-confirm-message="Weet je zeker dat je dit bedrag wilt stoppen? De historie blijft bewaard.">
                                        @csrf
                                        @method('DELETE')
                                        <flux:button type="submit" variant="danger" data-submit-button><span data-submit-label>Bedrag stoppen</span></flux:button>
                                    </form>
                                @elseif($costStatus === \App\Enums\Charity\EventCostStatus::Upcoming)
                                    <form method="POST" action="{{ route('charity.admin.events.costs.destroy', [$event, $cost]) }}"
                                          id="delete-cost-{{ $cost->id }}" class="charity-event-cost-actions"
                                          data-charity-form data-submit-label="Verwijderen"
                                          data-confirm-message="Weet je zeker dat je deze toekomstige periode wilt verwijderen?">
                                        @csrf
                                        @method('DELETE')
                                        <flux:button type="submit" variant="danger" icon="trash" data-submit-button><span data-submit-label>Verwijderen</span></flux:button>
                                    </form>
                                @else
                                    <span class="charity-event-history-note"><flux:icon.lock-closed class="size-4"/> Alleen historie</span>
                                @endif
                            </div>
                        @empty
                            <p class="charity-admin-empty-note">Nog geen bedrag ingesteld.</p>
                        @endforelse

                        <div class="charity-event-add">
                            <h5>Nieuwe periode toevoegen</h5>
                            <p class="charity-field-hint">Vul het bedrag en de startdatum in. Een einddatum is optioneel.</p>
                        <form method="POST" action="{{ route('charity.admin.events.costs.store', $event) }}"
                              id="new-cost-{{ $event->id }}" class="charity-admin-form charity-admin-cost-form"
                              data-charity-form data-submit-label="Periode toevoegen">
                            @csrf
                            <input type="hidden" name="event_id" value="{{ $event->id }}"/>
                            <div class="charity-event-field">
                                <flux:input id="amount-{{ $event->id }}" type="number" step="0.01" min="0.01" name="amount" label="Bedrag per aanvraag (&euro;)" :value="old('event_id') == $event->id ? old('amount') : ''" aria-describedby="amount-error-{{ $event->id }}" :aria-invalid="old('event_id') == $event->id && $errors->has('amount') ? 'true' : 'false'" required/>
                                <p id="amount-error-{{ $event->id }}" class="charity-field-error" data-field-error="amount">@if(old('event_id') == $event->id)@error('amount'){{ $message }}@enderror @endif</p>
                            </div>
                            <div class="charity-event-field">
                                <flux:input id="start-date-{{ $event->id }}" type="datetime-local" name="start_date" label="Startdatum en tijd" :value="old('event_id') == $event->id ? old('start_date') : ''" aria-describedby="start-date-error-{{ $event->id }}" :aria-invalid="old('event_id') == $event->id && $errors->has('start_date') ? 'true' : 'false'" required/>
                                <p id="start-date-error-{{ $event->id }}" class="charity-field-error" data-field-error="start_date">@if(old('event_id') == $event->id)@error('start_date'){{ $message }}@enderror @endif</p>
                            </div>
                            <div class="charity-event-field">
                                <flux:input id="end-date-{{ $event->id }}" type="datetime-local" name="end_date" label="Einddatum en tijd (optioneel)" :value="old('event_id') == $event->id ? old('end_date') : ''" aria-describedby="end-date-error-{{ $event->id }}" :aria-invalid="old('event_id') == $event->id && $errors->has('end_date') ? 'true' : 'false'"/>
                                <p id="end-date-error-{{ $event->id }}" class="charity-field-error" data-field-error="end_date">@if(old('event_id') == $event->id)@error('end_date'){{ $message }}@enderror @endif</p>
                            </div>
                            <flux:button type="submit" icon="plus" data-submit-button><span data-submit-label>Periode toevoegen</span></flux:button>
                        </form>
                        </div>
                    </article>
                @empty
                    <div class="charity-empty"><flux:icon.calendar-days class="size-8"/><h3>Nog geen gebeurtenissen</h3><p>Voeg hierboven de eerste gebeurtenis toe.</p></div>
                @endforelse
            </div>
        </section>
    </div>
</x-charity-wrapper>
