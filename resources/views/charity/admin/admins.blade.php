{{-- Managing access to the Lief en Leed module. --}}
<x-charity-wrapper page="Admins">
    <header class="charity-page-heading">
        <h2>Admins</h2>
        <p>Gebruikers met toegang tot Lief &amp; Leed.</p>
    </header>

    <div class="charity-admin-layout charity-admin-management">
        <section class="charity-admin-section charity-admin-section-new" aria-labelledby="new-admin-heading">
            <div class="charity-admin-section-heading">
                <h3 id="new-admin-heading">Admin toevoegen</h3>
            </div>
            <form method="POST" action="{{ route('charity.admin.admins.store') }}" id="new-admin"
                  class="charity-admin-form charity-admin-form-inline charity-admin-admin-form">
                @csrf
                <flux:input type="email" name="employee" label="E-mailadres" value="{{ old('employee') }}"
                            placeholder="naam@almere.nl" required/>
                <flux:button type="submit" variant="primary" icon="plus">Toevoegen</flux:button>
            </form>
        </section>

        <section class="charity-admin-section charity-admin-section-list" aria-labelledby="admins-list-heading">
            <div class="charity-admin-section-heading">
                <h3 id="admins-list-heading">Adminlijst</h3>
                <span class="charity-admin-count">{{ $admins->count() }} {{ $admins->count() === 1 ? 'admin' : 'admins' }}</span>
            </div>

            <div class="charity-admin-list">
                @forelse($admins as $admin)
                    @php($isCurrentAdmin = strtolower($admin->employee) === strtolower((string) session('magic.email')))
                    <article @class([
                        'charity-admin-record',
                        'charity-admin-admin',
                        'charity-admin-superadmin' => $admin->super,
                        'charity-admin-regular' => !$admin->super,
                    ])>
                        <div class="charity-admin-record-copy">
                            <strong>{{ $admin->employee }}</strong>
                            <span @class([
                                'charity-admin-status',
                                'charity-admin-status-super' => $admin->super,
                                'charity-admin-status-regular' => !$admin->super,
                            ])>{{ $admin->super ? 'Superadmin' : 'Admin' }}</span>
                            @if($isCurrentAdmin)<span class="charity-admin-current">Jij</span>@endif
                        </div>

                        <div class="charity-admin-actions">
                            @unless($admin->super)
                                <form method="POST" action="{{ route('charity.admin.admins.update', $admin) }}"
                                      id="promote-{{ $admin->id }}" class="charity-admin-promote-form">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="super" value="1">
                                    <flux:button type="submit" icon="shield-check">Maak superadmin</flux:button>
                                </form>
                            @endunless

                            @if($isCurrentAdmin || $admin->super)
                                <span class="charity-admin-protected" title="{{ $isCurrentAdmin ? 'Je account kan niet verwijderd worden.' : 'Superadmins kunnen niet verwijderd worden.' }}" aria-label="{{ $isCurrentAdmin ? 'Je account kan niet verwijderd worden.' : 'Superadmins kunnen niet verwijderd worden.' }}">
                                    <flux:icon.lock-closed class="size-4"/>
                                    Niet verwijderbaar
                                </span>
                            @else
                                <form method="POST" action="{{ route('charity.admin.admins.destroy', $admin) }}"
                                      id="delete-admin-{{ $admin->id }}"
                                      onsubmit="return confirm('Weet je zeker dat je deze admin wilt verwijderen?');">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button type="submit" variant="danger" icon="trash">Verwijderen</flux:button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="charity-admin-empty-note">Nog geen admins. Voeg hierboven de eerste toe.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-charity-wrapper>
