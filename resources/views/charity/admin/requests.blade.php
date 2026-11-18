<x-charity-wrapper page="Aanvragen">
    @php
        $isSuper = session('magic.admin.isSuper', false);
        $statuses = $isSuper ? \App\Enums\Charity\RequestStatus::cases() : [
            \App\Enums\Charity\RequestStatus::PENDING,
            \App\Enums\Charity\RequestStatus::ACCEPTED,
            \App\Enums\Charity\RequestStatus::REJECTED,
        ];
    @endphp

    <header class="charity-page-heading charity-heading-row">
        <div><h2>Aanvragen</h2><p>{{ $requests->total() }} {{ $requests->total() === 1 ? 'aanvraag' : 'aanvragen' }} gevonden. Open een aanvraag om deze te beoordelen.</p></div>
        @if($isSuper)<a href="#charity-exports" class="charity-text-link"><flux:icon.arrow-down-tray class="size-4"/> Uitbetalingen en rapporten</a>@endif
    </header>

    <section class="charity-filter-panel" aria-labelledby="request-filter-heading">
        <div class="charity-admin-section-heading">
            <h3 id="request-filter-heading">Zoek en filter aanvragen</h3>
        </div>
        <form method="GET" action="{{ route('charity.admin.requests.index') }}" class="charity-filters">
            <div class="charity-field"><label for="request-search">Zoeken op nummer, naam of e-mail</label><input id="request-search" type="search" name="search" value="{{ $search }}" placeholder="Bijvoorbeeld: 1024 of naam@almere.nl" maxlength="255"/></div>
            <div class="charity-field"><label for="request-event">Gebeurtenis</label><select id="request-event" name="event_id"><option value="">Alle gebeurtenissen</option>@foreach($events as $event)<option value="{{ $event->id }}" @selected($selectedEvent == $event->id)>{{ $event->title }}</option>@endforeach</select></div>
            @if(count($selectedStatuses) === 1)<input type="hidden" name="statuses[]" value="{{ $selectedStatuses[0] }}"/>@endif
            <flux:button type="submit" icon="magnifying-glass">Toon resultaten</flux:button>
            <flux:button :href="route('charity.admin.requests.index')" variant="ghost" icon="x-mark">Wis filters</flux:button>
        </form>
    </section>

    <nav class="charity-status-filters" aria-label="Filter aanvragen op status" data-status-count="{{ count($statuses) + 1 }}">
        <a href="{{ route('charity.admin.requests.index', request()->only(['search', 'event_id'])) }}" @if(count($selectedStatuses) !== 1) aria-current="page" @endif>Alle</a>
        @foreach($statuses as $status)
            <a href="{{ route('charity.admin.requests.index', [...request()->only(['search', 'event_id']), 'statuses' => [$status->value]]) }}" @if(count($selectedStatuses) === 1 && $selectedStatuses[0] === $status->value) aria-current="page" @endif>{{ $status->label() }}</a>
        @endforeach
    </nav>

    <p class="charity-request-list-help"><flux:icon.information-circle class="size-4"/> Selecteer een aanvraag om details en beschikbare acties te bekijken.</p>
    <div class="charity-request-list">
        @forelse($requests as $supportRequest)
            <details class="charity-request-item" @if(old('review_request_id') == $supportRequest->id) open @endif>
                <summary>
                    <div class="charity-request-identity"><span class="charity-request-number">#{{ $supportRequest->id }}</span><strong>{{ $supportRequest->member?->full_name ?? $supportRequest->member?->name ?? $supportRequest->employee_recipient }}</strong><span>{{ $supportRequest->eventCost?->event?->title ?? 'Onbekende gebeurtenis' }}</span></div>
                    <span class="charity-request-date">{{ $supportRequest->created_at?->format('d-m-Y') }}</span>
                    <strong class="charity-request-amount">&euro; {{ number_format($supportRequest->eventCost?->amount ?? 0, 2, ',', '.') }}</strong>
                    <span class="charity-status charity-status-{{ $supportRequest->status->value }}">{{ $supportRequest->status->label() }}</span>
                    <flux:icon.chevron-down class="size-4 charity-details-arrow"/>
                </summary>
                <div class="charity-request-body">
                    <dl class="charity-request-meta"><div><dt>Aanvrager</dt><dd>{{ $supportRequest->employee_requester }}</dd></div><div><dt>Collega</dt><dd>{{ $supportRequest->member?->full_name ?? $supportRequest->employee_recipient }}</dd></div><div><dt>Ingediend</dt><dd>{{ $supportRequest->created_at?->format('d-m-Y H:i') }}</dd></div></dl>
                    @if($supportRequest->reason)<p class="charity-rejection-reason"><strong>Reden van afwijzing</strong><br>{{ $supportRequest->reason }}</p>@endif
                    @if($supportRequest->status === \App\Enums\Charity\RequestStatus::PENDING)
                        <div class="charity-review-actions">
                            <form method="POST" action="{{ route('charity.admin.requests.update', $supportRequest) }}" data-charity-form data-submit-label="Goedkeuren">
                                @csrf @method('PUT')<input type="hidden" name="status" value="accepted"/><input type="hidden" name="review_request_id" value="{{ $supportRequest->id }}"/>
                                <flux:button type="submit" variant="primary" icon="check" data-submit-button><span data-submit-label>Goedkeuren</span></flux:button>
                            </form>
                            <form method="POST" action="{{ route('charity.admin.requests.update', $supportRequest) }}" class="charity-reject-form" data-charity-form data-submit-label="Afwijzen">
                                @csrf @method('PUT')<input type="hidden" name="status" value="rejected"/><input type="hidden" name="review_request_id" value="{{ $supportRequest->id }}"/>
                                <div class="charity-field"><label for="reject-reason-{{ $supportRequest->id }}">Reden van afwijzing <span aria-hidden="true">*</span></label><textarea id="reject-reason-{{ $supportRequest->id }}" name="reason" rows="2" required maxlength="1000" aria-describedby="reject-error-{{ $supportRequest->id }}">{{ old('review_request_id') == $supportRequest->id ? old('reason') : '' }}</textarea><p id="reject-error-{{ $supportRequest->id }}" class="charity-field-error" data-field-error="reason">@if(old('review_request_id') == $supportRequest->id)@error('reason'){{ $message }}@enderror @endif</p></div>
                                <flux:button type="submit" icon="x-mark" variant="danger" data-submit-button><span data-submit-label>Afwijzen</span></flux:button>
                            </form>
                        </div>
                    @endif
                    @if($isSuper && $supportRequest->status === \App\Enums\Charity\RequestStatus::EXPORTED)
                        <form method="POST" action="{{ route('charity.admin.requests.update', $supportRequest) }}" data-charity-form data-submit-label="Markeren als betaald" data-confirm-message="Weet je zeker dat deze bijdrage is betaald? De bankgegevens worden daarna verwijderd.">
                            @csrf @method('PUT')<input type="hidden" name="status" value="paid"/>
                            <p class="charity-field-hint">Markeer alleen een daadwerkelijk uitgevoerde betaling. De bankgegevens worden daarna verwijderd.</p>
                            <flux:button type="submit" icon="check-circle" variant="primary" data-submit-button><span data-submit-label>Markeren als betaald</span></flux:button>
                        </form>
                    @endif
                </div>
            </details>
        @empty
            <div class="charity-empty"><flux:icon.inbox class="size-8"/><h3>Geen aanvragen gevonden</h3><p>Er zijn geen aanvragen die aan deze filters voldoen.</p><a class="charity-text-link" href="{{ route('charity.admin.requests.index') }}">Filters wissen</a></div>
        @endforelse
    </div>
    <div class="charity-pagination">{{ $requests->links() }}</div>

    @if($isSuper)
        <section id="charity-exports" class="charity-exports">
            <h3>Uitbetalingen en rapporten</h3>
            <details class="charity-export-section" @if($errors->has('debtorIban') || $errors->has('debtorName') || session('debtor_data')) open @endif>
                <summary><flux:icon.banknotes class="size-5"/> SEPA-betaalbestand</summary>
                <div class="charity-export-body">
                    <form method="POST" action="{{ route('charity.admin.download.validate-sepa') }}" data-charity-form data-submit-label="Betalergegevens vastleggen">
                        @csrf
                        <div class="charity-field"><label for="debtor-name">Naam betaler <span aria-hidden="true">*</span></label><input id="debtor-name" name="debtorName" maxlength="250" value="{{ old('debtorName', session('debtor_data.debtorName', $defaultSepa['account_name'] ?? '')) }}" required/><p class="charity-field-error" data-field-error="debtorName">@error('debtorName'){{ $message }}@enderror</p></div>
                        <x-charity-bank-field name="debtorIban" id="debtor-iban" label="IBAN betaler" :value="old('debtorIban', session('debtor_data.debtorIban', $defaultSepa['iban'] ?? ''))"/>
                        <flux:button type="submit" variant="primary" icon="check" data-submit-button><span data-submit-label>Betalergegevens vastleggen</span></flux:button>
                    </form>
                    <div class="charity-download-row">
                        @if(session('debtor_data'))<flux:button :href="route('charity.admin.download.sepa')" icon="arrow-down-tray">SEPA-bestand downloaden</flux:button>@else<flux:button disabled icon="arrow-down-tray">SEPA-bestand downloaden</flux:button>@endif
                        <p class="charity-field-hint">Alleen goedgekeurde aanvragen worden in het betaalbestand opgenomen.</p>
                    </div>
                </div>
            </details>
            <details class="charity-export-section" @if($errors->has('startDate') || $errors->has('endDate') || session('rapport_data')) open @endif>
                <summary><flux:icon.document-chart-bar class="size-5"/> Rapport met betaalde aanvragen</summary>
                <div class="charity-export-body">
                    <form method="POST" action="{{ route('charity.admin.download.validate-rapport') }}" data-charity-form data-submit-label="Periode vastleggen">
                        @csrf
                        <div class="charity-date-range"><div class="charity-field"><label for="report-start">Vanaf</label><input id="report-start" type="date" name="startDate" value="{{ old('startDate', session('rapport_data.startDate', now()->startOfMonth()->toDateString())) }}" required/><p class="charity-field-error" data-field-error="startDate">@error('startDate'){{ $message }}@enderror</p></div><div class="charity-field"><label for="report-end">Tot en met</label><input id="report-end" type="date" name="endDate" value="{{ old('endDate', session('rapport_data.endDate', now()->toDateString())) }}" required/><p class="charity-field-error" data-field-error="endDate">@error('endDate'){{ $message }}@enderror</p></div></div>
                        <flux:button type="submit" icon="calendar-days" data-submit-button><span data-submit-label>Periode vastleggen</span></flux:button>
                    </form>
                    @if(session('rapport_data'))<div class="charity-download-row"><flux:button :href="route('charity.admin.download.rapport')" icon="arrow-down-tray">Rapport downloaden</flux:button></div>@endif
                </div>
            </details>
        </section>
    @endif
</x-charity-wrapper>
