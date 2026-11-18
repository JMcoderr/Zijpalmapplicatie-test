{{-- Member list, imported from an Excel export. --}}
<x-charity-wrapper page="Leden">
    <header class="charity-page-heading">
        <h2>Leden</h2>
        <p>{{ $members->total() }} {{ $members->total() === 1 ? 'lid' : 'leden' }} gevonden die nu of kort geleden in aanmerking komen voor een bijdrage.</p>
    </header>

    <div class="charity-admin-layout">
        <section class="charity-admin-section" aria-labelledby="import-members-heading">
            <div class="charity-admin-section-heading">
                <h3 id="import-members-heading">Ledenbestand importeren</h3>
                                <p class="charity-section-intro">Selecteer het actuele Excelbestand met de ledengegevens.</p>
            </div>
            <form method="POST" action="{{ route('charity.admin.members.store') }}" id="import-members"
                                    enctype="multipart/form-data" class="charity-admin-form charity-admin-form-inline" data-charity-form data-submit-label="Leden importeren">
                @csrf
                <flux:input type="file" name="file" label="Excel-bestand (.xlsx)" accept=".xlsx,.xls" required/>
                                <flux:button type="submit" variant="primary" icon="arrow-up-tray" data-submit-button><span data-submit-label>Leden importeren</span></flux:button>
            </form>
        </section>

        <section class="charity-admin-section" aria-labelledby="members-list-heading">
            <div class="charity-admin-section-heading">
                <h3 id="members-list-heading">Ledenlijst</h3>
                <flux:button href="{{ route('charity.admin.members.export', request()->only(['search', 'deleted'])) }}"
                             icon="arrow-down-tray" variant="primary">
                    Ledenlijst exporteren
                </flux:button>
            </div>

            <form method="GET" action="{{ route('charity.admin.members.index') }}" id="member-filters"
                  class="charity-admin-form charity-admin-filter-form">
                <flux:input name="search" label="Zoeken" value="{{ $filters['search'] ?? '' }}"
                            placeholder="Nummer of e-mail"/>
                <flux:select name="deleted" label="Welke leden tonen?">
                    <option value="">In aanmerking voor een bijdrage</option>
                    <option value="1" @selected(($filters['deleted'] ?? '') === '1')>Alle actieve leden</option>
                    <option value="2" @selected(($filters['deleted'] ?? '') === '2')>Alleen verwijderde leden</option>
                    <option value="3" @selected(($filters['deleted'] ?? '') === '3')>Alle leden, ook oude verwijderingen</option>
                </flux:select>
                <flux:button type="submit" icon="magnifying-glass">Filter</flux:button>
            </form>

            <div class="charity-admin-table-scroll" role="region" aria-label="Ledenlijst" tabindex="0">
                <table class="charity-admin-table">
                    <thead>
                        <tr>
                            <th scope="col">Nummer</th>
                            <th scope="col">Naam</th>
                            <th scope="col">E-mail</th>
                            <th scope="col">Aanvragen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($members as $member)
                            <tr @class(['charity-admin-member-deleted' => $member->trashed()])>
                                <td>{{ $member->id }}</td>
                                <td>{{ $member->full_name ?? $member->name }}</td>
                                <td>{{ $member->email }}</td>
                                <td>{{ $member->requests->count() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">Geen leden gevonden.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="charity-pagination">{{ $members->links() }}</div>
        </section>
    </div>
</x-charity-wrapper>
