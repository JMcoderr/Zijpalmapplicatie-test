{{-- Layout for the Lief en Leed module. Uses the Zijpalm layout with its own submenu. --}}
@props([
    'page' => 'Lief & Leed',
])

@push('scripts')
    @vite('resources/js/charity/forms.js')
@endpush

<x-layouts.app.header :title="$page">
    <main @class(['charity-app', 'charity-app-login' => request()->routeIs('charity.login')])>
        <div class="charity-container">
            <header class="charity-brand">
                <div class="charity-brand-name">
                    <img src="{{ asset('images/lief-en-leed.png') }}" alt="Logo Lief &amp; Leed" width="132" height="49" class="charity-logo"/>
                    <div>
                        <p class="charity-eyebrow">Personeelsvereniging</p>
                        <h1>Lief &amp; Leed</h1>
                    </div>
                </div>
                @if($magic = session('magic'))
                    <div class="charity-session-user">
                        <span class="charity-session-avatar" aria-hidden="true"><flux:icon.user class="size-5"/></span>
                        <div class="charity-session-copy">
                            <span>Ingelogd als</span>
                            <strong>{{ $magic['email'] }}</strong>
                            <span class="charity-session-role">
                                {{ ($magic['admin']['isSuper'] ?? false) ? 'Superadmin' : (($magic['admin']['isAdmin'] ?? false) ? 'Admin' : 'Medewerker') }}
                            </span>
                        </div>
                        <flux:button :href="route('charity.logout')" icon="arrow-right-start-on-rectangle" variant="outline">Uitloggen</flux:button>
                    </div>
                @endif
            </header>

            @if($magic)
                <nav class="charity-navigation" aria-label="Lief en Leed">
                    <a href="{{ route('charity.requests') }}" @if(request()->routeIs('charity.requests')) aria-current="page" @endif>
                        <flux:icon.heart class="size-4"/> Aanvraag indienen
                    </a>
                    @if($magic['admin']['isAdmin'] ?? false)
                        <a href="{{ route('charity.admin.requests.index') }}" @if(request()->routeIs('charity.admin.requests.*')) aria-current="page" @endif>
                            <flux:icon.clipboard-document-list class="size-4"/> Aanvragen
                        </a>
                    @endif
                    @if($magic['admin']['isSuper'] ?? false)
                        <a href="{{ route('charity.admin.events.index') }}" @if(request()->routeIs('charity.admin.events.*')) aria-current="page" @endif><flux:icon.calendar-days class="size-4"/> Gebeurtenissen</a>
                        <a href="{{ route('charity.admin.members.index') }}" @if(request()->routeIs('charity.admin.members.*')) aria-current="page" @endif><flux:icon.users class="size-4"/> Leden</a>
                        <a href="{{ route('charity.admin.admins.index') }}" @if(request()->routeIs('charity.admin.admins.*')) aria-current="page" @endif><flux:icon.shield-check class="size-4"/> Admins</a>
                    @endif
                </nav>
            @endif

            @if(session('toast'))
                <div class="charity-notice {{ session('toast.type') === 'success' ? 'charity-notice-success' : 'charity-notice-error' }}" role="{{ session('toast.type') === 'success' ? 'status' : 'alert' }}">
                    {{ session('toast.message') }}
                </div>
            @endif
            @if(session('status') && !request()->routeIs('charity.login'))
                <div class="charity-notice charity-notice-success" role="status">{{ session('status') }}</div>
            @endif
            @if($errors->toast->any())
                <div class="charity-notice charity-notice-error" role="alert">{{ $errors->toast->first('message') }}</div>
            @endif
            @if($errors->getBag('default')->any() && !request()->routeIs('charity.login'))
                <div class="charity-notice charity-notice-error" role="alert" tabindex="-1" data-error-summary>
                    <h2>Controleer je gegevens</h2>
                    <ul>
                        @foreach($errors->getBag('default')->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="charity-content">{{ $slot }}</div>
        </div>
    </main>
</x-layouts.app.header>
