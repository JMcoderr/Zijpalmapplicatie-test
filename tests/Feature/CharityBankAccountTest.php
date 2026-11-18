<?php

use App\Models\Charity\CharityAdmin;
use App\Models\Charity\Event;
use App\Models\Charity\Member;
use App\Models\Charity\SupportRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withSession(['magic' => [
        'email' => 'aanvrager@almere.nl',
        'expires_at' => now()->addHour(),
        'admin' => ['isAdmin' => false, 'isSuper' => false],
    ]]);
});

it('checks normalized IBAN format without claiming account or holder verification', function () {
    $this->postJson(route('charity.bank-account.check'), ['iban' => 'nl91 abna 0417 1643 00'])
        ->assertOk()->assertJson([
            'format_valid' => true, 'account_verified' => false, 'holder_verified' => false,
        ])->assertHeader('Cache-Control', 'no-store, private');
});

it('rejects invalid IBAN checksums and malformed values', function ($iban) {
    $this->postJson(route('charity.bank-account.check'), ['iban' => $iban])
        ->assertUnprocessable()->assertJsonValidationErrors('iban');
})->with(['NL92ABNA0417164300', 'NL91ABNA04171643', 'XX91ABNA0417164300', [[]], '']);

it('does not allow a guest to check bank information', function () {
    session()->forget('magic');
    $this->postJson(route('charity.bank-account.check'), ['iban' => 'NL91ABNA0417164300'])
        ->assertRedirect(route('charity.login'));
});

it('stores normalized bank information and rejects invalid checksum submissions', function () {
    Mail::fake();
    $event = Event::forceCreate(['id' => 17, 'title' => 'Geboorte']);
    $cost = $event->eventCosts()->create(['amount' => 25, 'start_date' => now()->subDay()]);
    $member = Member::create(['id' => 456, 'email' => 'collega@almere.nl']);
    $fields = [
        'employee_requester' => 'aanvrager@almere.nl', 'employee_recipient' => '456',
        'event_cost_id' => $cost->id, 'account_name' => 'Collega', 'iban' => 'NL92ABNA0417164300',
    ];
    $this->post(route('charity.requests.store'), $fields)->assertSessionHasErrors('iban');
    expect(SupportRequest::count())->toBe(0);

    $fields['iban'] = 'nl91 abna 0417 1643 00';
    $this->post(route('charity.requests.store'), $fields)->assertSessionHasNoErrors();
    expect(SupportRequest::sole()->iban)->toBe('NL91ABNA0417164300');
});

it('validates a SEPA debtor checksum before saving the payer details', function () {
    CharityAdmin::create(['employee' => 'aanvrager@almere.nl', 'super' => true]);
    $this->post(route('charity.admin.download.validate-sepa'), [
        'debtorName' => 'Vereniging', 'debtorIban' => 'NL92ABNA0417164300',
    ])->assertSessionHasErrors('debtorIban')->assertSessionMissing('debtor_data');
});

it('renders accessible bank controls and preserves application values after an error', function () {
    $event = Event::forceCreate(['id' => 17, 'title' => 'Geboorte']);
    $cost = $event->eventCosts()->create(['amount' => 25, 'start_date' => now()->subDay()]);
    Member::create(['id' => 456, 'email' => 'collega@almere.nl', 'full_name' => 'Sanne de Vries']);
    $url = route('charity.requests');
    $this->from($url)->post(route('charity.requests.store'), [
        'employee_requester' => 'aanvrager@almere.nl', 'employee_recipient' => '456',
        'event_cost_id' => $cost->id, 'account_name' => 'Test Rekeninghouder', 'iban' => 'NL92ABNA0417164300',
    ])->assertSessionHasErrors('iban');

    $this->get($url)->assertOk()->assertSee('Test Rekeninghouder')
        ->assertSee('Controleer IBAN')->assertSee('Nog niet gecontroleerd.')
        ->assertSee('geen bevestiging dat de rekening bestaat')
        ->assertSee('data-member-search', false)->assertSee('aria-live="polite"', false)
        ->assertSee('Uitbetaling aan jou');
});

it('returns a validation error rather than a server error for malformed request filters', function () {
    CharityAdmin::create(['employee' => 'aanvrager@almere.nl', 'super' => true]);
    $this->getJson(route('charity.admin.requests.index', ['statuses' => 'paid']))
        ->assertUnprocessable()->assertJsonValidationErrors('statuses');
});

it('requires a rejection reason on the server and keeps the pending application unchanged', function () {
    Mail::fake();
    CharityAdmin::create(['employee' => 'aanvrager@almere.nl']);
    $event = Event::forceCreate(['id' => 17, 'title' => 'Geboorte']);
    $cost = $event->eventCosts()->create(['amount' => 25, 'start_date' => now()->subDay()]);
    Member::create(['id' => 456, 'email' => 'collega@almere.nl']);
    $application = SupportRequest::create([
        'employee_requester' => 'medewerker@almere.nl', 'employee_recipient' => 456,
        'event_cost_id' => $cost->id,
    ]);

    $this->put(route('charity.admin.requests.update', $application), ['status' => 'rejected'])
        ->assertSessionHasErrors(['reason' => 'Vul een reden van afwijzing in.']);
    expect($application->fresh()->status)->toBe(\App\Enums\Charity\RequestStatus::PENDING);
    Mail::assertNothingSent();

    $this->get(route('charity.admin.requests.index'))->assertOk()->assertSee('Reden van afwijzing');
});

it('rejects reversed report dates and provides the report period form', function () {
    CharityAdmin::create(['employee' => 'aanvrager@almere.nl', 'super' => true]);
    $this->post(route('charity.admin.download.validate-rapport'), [
        'startDate' => '2026-11-06', 'endDate' => '2026-11-01',
    ])->assertSessionHasErrors('endDate')->assertSessionMissing('rapport_data');
    $this->get(route('charity.admin.requests.index'))->assertOk()->assertSee('Rapport met betaalde aanvragen')
        ->assertSee('IBAN betaler')->assertSee('name="startDate"', false);
});