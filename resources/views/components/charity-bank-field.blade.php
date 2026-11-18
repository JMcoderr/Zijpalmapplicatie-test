@props(['name', 'id', 'label' => 'IBAN', 'value' => ''])

<div class="charity-field" data-bank-check data-check-url="{{ route('charity.bank-account.check') }}">
    <label for="{{ $id }}">{{ $label }} <span aria-hidden="true">*</span></label>
    <div class="charity-bank-control">
        <input id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" placeholder="NL00 BANK 0000 0000 00" maxlength="50" autocomplete="off" spellcheck="false" autocapitalize="characters" required data-iban-input aria-describedby="{{ $id }}-error {{ $id }}-status {{ $id }}-notice" aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"/>
        <flux:button type="button" icon="shield-check" data-bank-check-button>Controleer IBAN</flux:button>
    </div>
    <p id="{{ $id }}-error" class="charity-field-error" data-field-error="{{ $name }}">@error($name){{ $message }}@enderror</p>
    <p id="{{ $id }}-status" class="charity-bank-status" data-bank-status data-state="idle" role="status" aria-live="polite">Nog niet gecontroleerd.</p>
    <p id="{{ $id }}-notice" class="charity-field-hint">Formaatcontrole is geen bevestiging dat de rekening bestaat of op jouw naam staat.</p>
</div>