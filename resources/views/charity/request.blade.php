{{-- Form where an employee requests a Lief en Leed contribution for a colleague. --}}
<x-charity-wrapper page="Aanvraag indienen">

    <header class="charity-page-heading">
        <h2>Aanvraag indienen</h2>
        <p>Vraag een bijdrage aan voor een bijzonder moment van je collega. De aanvraag wordt aan jou uitbetaald nadat deze is goedgekeurd.</p>
    </header>

    <form method="POST" action="{{ route('charity.requests.store') }}" id="charity-request" class="charity-request-grid" data-charity-form>
        @csrf
        <div class="charity-form-main">
            <fieldset class="charity-section">
                <legend><span class="charity-step">1</span> Collega en gebeurtenis</legend>
                <div class="charity-field">
                    <label for="member-search">Zoek een collega</label>
                    <input id="member-search" type="search" placeholder="Naam, personeelsnummer of e-mail" autocomplete="off" data-member-search/>
                    <p class="charity-field-hint" role="status" data-member-search-feedback></p>
                </div>
                <div class="charity-field">
                    <label for="employee-recipient">Voor welke collega is de aanvraag? <span aria-hidden="true">*</span></label>
                    <select id="employee-recipient" name="employee_recipient" required data-member-select aria-describedby="recipient-hint recipient-error" aria-invalid="{{ $errors->has('employee_recipient') ? 'true' : 'false' }}">
                        <option value="">Kies een lid</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}" @selected(old('employee_recipient') == $member->id)>{{ $member->full_name ?? $member->name ?? 'Naam onbekend' }} (ID {{ $member->id }}) - {{ $member->email }}</option>
                        @endforeach
                    </select>
                    <p id="recipient-hint" class="charity-field-hint">Controleer naam en personeelsnummer. Collega's kunnen hetzelfde e-mailadres delen.</p>
                    <p id="recipient-error" class="charity-field-error" data-field-error="employee_recipient">@error('employee_recipient'){{ $message }}@enderror</p>
                </div>
                <div class="charity-field">
                    <label for="event-cost">Gebeurtenis <span aria-hidden="true">*</span></label>
                    <select id="event-cost" name="event_cost_id" required data-event-select aria-describedby="event-error" aria-invalid="{{ $errors->has('event_cost_id') ? 'true' : 'false' }}">
                        <option value="">Kies een gebeurtenis</option>
                        @foreach($events as $eventCost)
                            <option value="{{ $eventCost->id }}" data-title="{{ $eventCost->event?->title }}" data-amount="{{ number_format($eventCost->amount, 2, ',', '.') }}" @selected(old('event_cost_id') == $eventCost->id)>{{ $eventCost->event?->title }} (&euro; {{ number_format($eventCost->amount, 2, ',', '.') }})</option>
                        @endforeach
                    </select>
                    <p class="charity-field-hint">De bijbehorende bijdrage verschijnt in de samenvatting.</p>
                    <p id="event-error" class="charity-field-error" data-field-error="event_cost_id">@error('event_cost_id'){{ $message }}@enderror</p>
                </div>
            </fieldset>

            <fieldset class="charity-section">
                <legend><span class="charity-step">2</span> Uitbetaling aan jou</legend>
                <p class="charity-section-intro">Vul het rekeningnummer in waarop je de bijdrage wilt ontvangen.</p>
                <div class="charity-field">
                    <label for="account-name">Naam rekeninghouder <span aria-hidden="true">*</span></label>
                    <input id="account-name" name="account_name" value="{{ old('account_name') }}" maxlength="100" autocomplete="name" required aria-describedby="account-error" aria-invalid="{{ $errors->has('account_name') ? 'true' : 'false' }}"/>
                    <p id="account-error" class="charity-field-error" data-field-error="account_name">@error('account_name'){{ $message }}@enderror</p>
                </div>
                <x-charity-bank-field name="iban" id="request-iban" label="IBAN" :value="old('iban', '')"/>
            </fieldset>

            <div class="charity-form-footer">
                <p class="charity-field-hint">Velden met * zijn verplicht.</p>
                <flux:button type="submit" variant="primary" icon="paper-airplane" data-submit-button :disabled="$members->isEmpty() || $events->isEmpty()"><span data-submit-label>Aanvraag versturen</span></flux:button>
            </div>
            @if($members->isEmpty() || $events->isEmpty())
                <p class="charity-notice charity-notice-error" role="status">Er zijn nog geen beschikbare collega's of gebeurtenissen. Neem contact op met een admin.</p>
            @endif
        </div>

        <aside class="charity-request-summary" aria-label="Samenvatting van je aanvraag" aria-live="polite">
            <div class="charity-summary-heading"><flux:icon.clipboard-document-list class="size-5"/><h3>Samenvatting</h3></div>
            <p class="charity-summary-intro">Controleer de gebeurtenis en het bedrag voordat je verdergaat.</p>
            <dl>
                <div><dt>Gebeurtenis</dt><dd data-event-title>Kies een gebeurtenis</dd></div>
                <div><dt>Bijdrage</dt><dd class="charity-summary-amount" data-event-amount>&mdash;</dd></div>
            </dl>
            <div class="charity-field">
                <label for="employee-requester">Aanvrager</label>
                <input id="employee-requester" name="employee_requester" value="{{ session('magic.email') }}" readonly aria-describedby="requester-error"/>
                <p id="requester-error" class="charity-field-error">@error('employee_requester'){{ $message }}@enderror</p>
            </div>
            <p class="charity-privacy"><flux:icon.lock-closed class="size-4"/> Bankgegevens worden versleuteld opgeslagen.</p>
        </aside>
    </form>
</x-charity-wrapper>
