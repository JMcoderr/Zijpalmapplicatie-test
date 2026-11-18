{{-- Magic link login for the Lief en Leed module. No password needed. --}}
<x-charity-wrapper page="Lief & Leed inloggen">
    <section class="charity-login">
        <header class="charity-page-heading">
            <h2>Inloggen met je werkmail</h2>
            <p>We sturen je een persoonlijke inloglink naar je e-mail.</p>
        </header>
        @if(session('status'))
            <aside class="charity-login-success" role="status" aria-live="polite">
                <span class="charity-login-success-icon"><flux:icon.check-circle class="size-6"/></span>
                <div>
                    <h3>Inloglink verstuurd</h3>
                    @if(app()->environment('local'))
                        <p>Je testmail staat klaar in de lokale inbox.</p>
                        <a href="http://127.0.0.1:8025/" target="_blank" rel="noopener noreferrer">
                            <flux:icon.inbox class="size-4"/> Open de testinbox
                        </a>
                    @else
                        <p>{{ session('status') }}</p>
                    @endif
                </div>
            </aside>
        @endif
        <form method="POST" action="{{ route('charity.magic-login.send-link') }}" id="magic-login" data-charity-form data-submit-label="Stuur inloglink">
            @csrf
            <div class="charity-field">
                <label for="magic-email">Werk-e-mailadres <span aria-hidden="true">*</span></label>
                <input id="magic-email" type="email" name="email" value="{{ old('email') }}" placeholder="naam@almere.nl" autocomplete="email" required autofocus aria-describedby="magic-email-error" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"/>
                <p id="magic-email-error" class="charity-field-error" data-field-error="email">@error('email'){{ $message }}@enderror</p>
            </div>
            <flux:button type="submit" variant="primary" icon="envelope" data-submit-button><span data-submit-label>Stuur inloglink</span></flux:button>
        </form>
        <p class="charity-login-expiry"><flux:icon.clock class="size-4"/> De link is {{ config('services.magic.expires', 5) }} minuten geldig. Na het openen kun je hem niet opnieuw gebruiken.</p>
    </section>
</x-charity-wrapper>
