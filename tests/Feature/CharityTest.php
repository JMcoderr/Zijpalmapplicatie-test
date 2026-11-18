<?php
// Tests for the Lief en Leed module: magic link access, roles and the request form.

use App\Enums\Charity\RequestStatus;
use App\Models\Charity\CharityAdmin;
use App\Models\Charity\Event;
use App\Models\Charity\EventCost;
use App\Models\Charity\Member;
use App\Models\Charity\SupportRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// Builds the session that the magic link middleware expects.
function magicSession(string $email, bool $isAdmin = false, bool $isSuper = false): array
{
    return [
        'magic' => [
            'email' => $email,
            'expires_at' => now()->addHours(24),
            'admin' => ['isAdmin' => $isAdmin, 'isSuper' => $isSuper],
        ],
    ];
}

it('shows the login page to guests', function () {
    $this->get(route('charity.login'))->assertOk()->assertSee('Lief &amp; Leed', false);
});

it('shows login confirmation inside the login card', function () {
    $message = 'Controleer je werkmail en eventueel de map met ongewenste e-mail.';

    $this->withSession(['status' => $message])
        ->get(route('charity.login'))
        ->assertOk()
        ->assertSee('class="charity-login-success"', false)
        ->assertSee('Inloglink verstuurd')
        ->assertSee($message)
        ->assertDontSee('charity-notice-success');
});

it('sends guests to the login page when they open the request form', function () {
    $this->get(route('charity.requests'))->assertRedirect(route('charity.login'));
});

it('only allows a magic link for an allowed domain', function () {
    config()->set('auth.allowed_domains', ['almere.nl']);
    Mail::fake();

    $this->post(route('charity.magic-login.send-link'), ['email' => 'someone@gmail.com'])
        ->assertSessionHasErrors('email');

    Mail::assertNothingSent();
});

it('rejects an external email in production', function () {
    config()->set('auth.allowed_domains', ['almere.nl']);
    config()->set('app.env', 'production');
    Mail::fake();

    $this->post(route('charity.magic-login.send-link'), ['email' => 'someone@gmail.com'])
        ->assertSessionHasErrors('email');

    Mail::assertNothingSent();
});

it('confirms that a magic login link was sent', function () {
    config()->set('auth.allowed_domains', ['almere.nl']);
    Mail::fake();

    $this->from(route('charity.login'))
        ->post(route('charity.magic-login.send-link'), ['email' => 'collega@almere.nl'])
        ->assertRedirect(route('charity.login'))
        ->assertSessionHas('status');

    Mail::assertSent(\App\Mail\Charity\MagicLoginMail::class, 1);
});

it('rejects expired magic login links', function () {
    config()->set('auth.allowed_domains', ['almere.nl']);
    $service = app(\App\Services\MagicLinkService::class);
    $token = $service->generateToken('collega@almere.nl');
    $url = $service->createSignedUrl('collega@almere.nl', $token);

    $this->travel(6)->minutes();
    $this->get($url)->assertForbidden()->assertSessionMissing('magic');
});

it('rejects reused or modified magic login links', function () {
    config()->set('auth.allowed_domains', ['almere.nl']);
    $service = app(\App\Services\MagicLinkService::class);
    $token = $service->generateToken('collega@almere.nl');
    $url = $service->createSignedUrl('collega@almere.nl', $token);

    $this->get($url.'&extra=changed')->assertForbidden();
    $this->get($url)->assertRedirect(route('charity.requests'));
    session()->forget('magic');
    $this->get($url)->assertForbidden();
});

it('logs out of charity without signing out of the main website', function () {
    $user = \App\Models\User::factory()->create();
    $session = magicSession('collega@almere.nl');
    $session['debtor_data'] = ['debtorName' => 'Prive', 'debtorIban' => 'NL91ABNA0417164300'];

    $this->actingAs($user)->withSession($session)
        ->get(route('charity.logout'))
        ->assertRedirect(route('charity.login'))
        ->assertSessionMissing('magic')
        ->assertSessionMissing('debtor_data');

    $this->assertAuthenticatedAs($user);
});

it('revokes cached charity administrator rights on the next request', function () {
    $admin = CharityAdmin::create(['employee' => 'beheerder@almere.nl']);
    $session = magicSession('beheerder@almere.nl', isAdmin: true);
    $admin->delete();

    $this->withSession($session)->get(route('charity.admin.requests.index'))
        ->assertRedirect(route('charity.requests'));
});

it('shows the request form to a logged in employee', function () {
    $event = Event::create(['title' => 'Geboorte']);
    $event->eventCosts()->create([
        'amount' => 25.00,
        'start_date' => now()->subDay(),
    ]);

    $this->withSession(magicSession('medewerker@almere.nl'))
        ->get(route('charity.requests'))
        ->assertOk()
        ->assertSee('Geboorte');
});

it('keeps a normal employee out of the admin pages', function () {
    $this->withSession(magicSession('medewerker@almere.nl'))
        ->get(route('charity.admin.requests.index'))
        ->assertRedirect(route('charity.requests'));
});

it('shows the request overview to an admin', function () {
    CharityAdmin::create(['employee' => 'beheerder@almere.nl']);

    $this->withSession(magicSession('beheerder@almere.nl', isAdmin: true))
        ->get(route('charity.admin.requests.index'))
        ->assertOk();
});

it('keeps a normal admin out of the member pages', function () {
    CharityAdmin::create(['employee' => 'beheerder@almere.nl']);

    $this->withSession(magicSession('beheerder@almere.nl', isAdmin: true))
        ->get(route('charity.admin.members.index'))
        ->assertRedirect(route('charity.admin.requests.index'));
});

it('lets a super admin manage events and members', function () {
    CharityAdmin::create(['employee' => 'hoofd@almere.nl', 'super' => true]);
    $session = magicSession('hoofd@almere.nl', isAdmin: true, isSuper: true);

    $this->withSession($session)->get(route('charity.admin.events.index'))->assertOk();
    $this->withSession($session)->get(route('charity.admin.members.index'))->assertOk();
    $this->withSession($session)->get(route('charity.admin.admins.index'))
        ->assertOk()
        ->assertSee('Admins')
        ->assertSee('Superadmin')
        ->assertSee('Niet verwijderbaar')
        ->assertDontSee('Superadmins kunnen admins, leden en gebeurtenissen beheren.')
        ->assertDontSee('Maak admin')
        ->assertDontSee('Hoofdbeheerder')
        ->assertDontSee('Beheerders')
        ->assertDontSee('Verwijderen');
});

it('adds new accounts as regular admins even for zijpalm addresses', function () {
    config()->set('auth.allowed_domains', ['almere.nl', 'zijpalm.nl']);
    CharityAdmin::create(['employee' => 'super@almere.nl', 'super' => true]);

    $this->from(route('charity.admin.admins.index'))
        ->withSession(magicSession('super@almere.nl', isAdmin: true, isSuper: true))
        ->post(route('charity.admin.admins.store'), ['employee' => 'collega@zijpalm.nl'])
        ->assertRedirect(route('charity.admin.admins.index'))
        ->assertSessionHas('toast.message', 'Admin succesvol toegevoegd.');

    expect((bool) CharityAdmin::where('employee', 'collega@zijpalm.nl')->value('super'))->toBeFalse();
});

it('only shows superadmin promotion for regular admins', function () {
    CharityAdmin::create(['employee' => 'super@almere.nl', 'super' => true]);
    CharityAdmin::create(['employee' => 'admin@almere.nl']);

    $this->withSession(magicSession('super@almere.nl', isAdmin: true, isSuper: true))
        ->get(route('charity.admin.admins.index'))
        ->assertOk()
        ->assertSee('Maak superadmin')
        ->assertDontSee('Maak admin');
});

it('does not allow a superadmin to be demoted to admin', function () {
    CharityAdmin::create(['employee' => 'actor@almere.nl', 'super' => true]);
    $otherSuperadmin = CharityAdmin::create(['employee' => 'other@almere.nl', 'super' => true]);

    $this->from(route('charity.admin.admins.index'))
        ->withSession(magicSession('actor@almere.nl', isAdmin: true, isSuper: true))
        ->put(route('charity.admin.admins.update', $otherSuperadmin), ['super' => 0])
        ->assertSessionHasErrors('super_delete');

    expect($otherSuperadmin->fresh()->super)->toBeTrue();
});

it('shows clear event cost statuses and the matching actions', function () {
    CharityAdmin::create(['employee' => 'super@almere.nl', 'super' => true]);
    $event = Event::create(['title' => 'Geboorte']);
    $event->eventCosts()->create(['amount' => 25, 'start_date' => now()->subDay()]);
    $event->eventCosts()->create(['amount' => 30, 'start_date' => now()->addDay()]);
    $event->eventCosts()->create([
        'amount' => 20,
        'start_date' => now()->subDays(3),
        'end_date' => now()->subDay(),
    ]);

    $response = $this->withSession(magicSession('super@almere.nl', isAdmin: true, isSuper: true))
        ->get(route('charity.admin.events.index'))
        ->assertOk()
        ->assertSee('Actief')
        ->assertSee('Gepland')
        ->assertSee('Afgelopen')
        ->assertSee('Bedrag stoppen')
        ->assertSee('Verwijderen')
        ->assertSee('Alleen historie');

    expect(substr_count($response->getContent(), 'class="charity-event-cost-actions"'))->toBe(2);
});

it('prevents a superadmin from deleting their own account', function () {
    $superadmin = CharityAdmin::create(['employee' => 'super@almere.nl', 'super' => true]);

    $this->from(route('charity.admin.admins.index'))
        ->withSession(magicSession('super@almere.nl', isAdmin: true, isSuper: true))
        ->delete(route('charity.admin.admins.destroy', $superadmin))
        ->assertSessionHasErrors('super_delete');

    expect($superadmin->fresh())->not->toBeNull();
});

it('prevents a superadmin from deleting another superadmin', function () {
    CharityAdmin::create(['employee' => 'actor@almere.nl', 'super' => true]);
    $otherSuperadmin = CharityAdmin::create(['employee' => 'other@almere.nl', 'super' => true]);

    $this->from(route('charity.admin.admins.index'))
        ->withSession(magicSession('actor@almere.nl', isAdmin: true, isSuper: true))
        ->delete(route('charity.admin.admins.destroy', $otherSuperadmin))
        ->assertSessionHasErrors('super_delete');

    expect($otherSuperadmin->fresh())->not->toBeNull();
});

it('allows a superadmin to delete a regular admin', function () {
    CharityAdmin::create(['employee' => 'actor@almere.nl', 'super' => true]);
    $admin = CharityAdmin::create(['employee' => 'admin@almere.nl']);

    $this->from(route('charity.admin.admins.index'))
        ->withSession(magicSession('actor@almere.nl', isAdmin: true, isSuper: true))
        ->delete(route('charity.admin.admins.destroy', $admin))
        ->assertRedirect(route('charity.admin.admins.index'))
        ->assertSessionHas('toast.message', 'Admin succesvol verwijderd.');

    expect(CharityAdmin::query()->whereKey($admin->id)->exists())->toBeFalse();
});

it('refuses a request when the recipient is unknown', function () {
    Mail::fake();

    $event = Event::create(['title' => 'Huwelijk']);
    $cost = $event->eventCosts()->create([
        'amount' => 50.00,
        'start_date' => now()->subDay(),
    ]);

    $this->withSession(magicSession('medewerker@almere.nl'))
        ->post(route('charity.requests.store'), [
            'employee_requester' => 'medewerker@almere.nl',
            'employee_recipient' => 'onbekend@almere.nl',
            'event_cost_id' => $cost->id,
            'account_name' => 'J. Jansen',
            'iban' => 'NL91ABNA0417164300',
        ])
        ->assertSessionHasErrors('employee_recipient');
});

it('refuses a request for yourself', function () {
    $event = Event::create(['title' => 'Jubileum']);
    $cost = $event->eventCosts()->create([
        'amount' => 50.00,
        'start_date' => now()->subDay(),
    ]);

    $this->withSession(magicSession('medewerker@almere.nl'))
        ->post(route('charity.requests.store'), [
            'employee_requester' => 'medewerker@almere.nl',
            'employee_recipient' => 'medewerker@almere.nl',
            'event_cost_id' => $cost->id,
            'account_name' => 'J. Jansen',
            'iban' => 'NL91ABNA0417164300',
        ])
        ->assertSessionHasErrors('employee_requester');
});

it('lets an employee select the intended member when emails are shared', function () {
    Mail::fake();

    foreach (range(1, 16) as $i) {
        Event::create(['title' => "Filler {$i}"]);
    }

    $event = Event::create(['title' => 'Collegiale attentie']);
    $cost = $event->eventCosts()->create([
        'amount' => 25.00,
        'start_date' => now()->subDay(),
    ]);

    Member::create(['id' => 123456, 'email' => 'gedeeld@almere.nl', 'full_name' => 'A. Medewerker']);
    Member::create(['id' => 123457, 'email' => 'gedeeld@almere.nl', 'full_name' => 'B. Medewerker']);

    $session = magicSession('aanvrager@almere.nl');
    $this->withSession($session)
        ->get(route('charity.requests'))
        ->assertOk()
        ->assertSee('Controleer naam en personeelsnummer')
        ->assertSee('B. Medewerker')
        ->assertSee('123457');

    $this->withSession($session)
        ->post(route('charity.requests.store'), [
            'employee_requester' => 'aanvrager@almere.nl',
            'employee_recipient' => '123457',
            'event_cost_id' => $cost->id,
            'account_name' => 'B. Medewerker',
            'iban' => 'NL91ABNA0417164300',
        ])
        ->assertSessionHasNoErrors();

    expect(SupportRequest::sole()->employee_recipient)->toBe('123457');
});

it('stores a valid request and keeps the iban encrypted', function () {
    Mail::fake();

    // Event ids 1-8 and 15-16 trigger the hardcoded milestone rules,
    // so use a later id for a plain request.
    foreach (range(1, 16) as $i) {
        Event::create(['title' => "Filler {$i}"]);
    }

    $event = Event::create(['title' => 'Geboorte']);
    $cost = $event->eventCosts()->create([
        'amount' => 25.00,
        'start_date' => now()->subDay(),
    ]);

    Member::create([
        'id' => 123456,
        'email' => 'collega@almere.nl',
        'full_name' => 'C. Collega',
    ]);

    $this->withSession(magicSession('medewerker@almere.nl'))
        ->post(route('charity.requests.store'), [
            'employee_requester' => 'medewerker@almere.nl',
            'employee_recipient' => 'collega@almere.nl',
            'event_cost_id' => $cost->id,
            'account_name' => 'C. Collega',
            'iban' => 'NL91ABNA0417164300',
        ])
        ->assertSessionHasNoErrors();

    $stored = SupportRequest::first();

    expect($stored)->not->toBeNull()
        ->and($stored->status)->toBe(RequestStatus::PENDING)
        ->and($stored->iban)->toBe('NL91ABNA0417164300');

    // The raw column must not contain the plain IBAN.
    $raw = DB::table('requests')->value('iban');
    expect($raw)->not->toBe('NL91ABNA0417164300');
});

it('keeps paid requests outside a regular admin search', function () {
    CharityAdmin::create(['employee' => 'beheerder@almere.nl']);
    $member = Member::create(['id' => 456, 'email' => 'collega@almere.nl', 'full_name' => 'Afgeschermde collega']);
    $event = Event::create(['title' => 'Geboorte']);
    $cost = $event->eventCosts()->create(['amount' => 25, 'start_date' => now()->subDay()]);
    $paid = SupportRequest::create([
        'employee_requester' => 'aanvrager@almere.nl', 'employee_recipient' => $member->id,
        'event_cost_id' => $cost->id, 'status' => RequestStatus::PAID,
    ]);

    $this->withSession(magicSession('beheerder@almere.nl', isAdmin: true))
        ->get(route('charity.admin.requests.index', ['search' => 'aanvrager']))
        ->assertOk()
        ->assertViewHas('requests', fn ($requests) => ! $requests->contains('id', $paid->id));
});

it('resolves a request event through its cost rather than an unrelated event id', function () {
    $event = Event::create(['title' => 'Juiste gebeurtenis']);
    $cost = $event->eventCosts()->create(['id' => 23, 'amount' => 25, 'start_date' => now()->subDay()]);
    $member = Member::create(['id' => 456, 'email' => 'collega@almere.nl']);
    $supportRequest = SupportRequest::create([
        'employee_requester' => 'aanvrager@almere.nl', 'employee_recipient' => $member->id,
        'event_cost_id' => $cost->id,
    ]);

    expect($supportRequest->event?->id)->toBe($event->id);
});

it('exports an accepted request and clears bank details after confirmed payment', function () {
    Mail::fake();
    CharityAdmin::create(['employee' => 'hoofd@almere.nl', 'super' => true]);
    $member = Member::create(['id' => 456, 'email' => 'collega@almere.nl', 'full_name' => 'A & B']);
    $event = Event::create(['title' => 'Huwelijk & partnerschap']);
    $cost = $event->eventCosts()->create(['id' => 23, 'amount' => 25, 'start_date' => now()->subDay()]);
    $supportRequest = SupportRequest::create([
        'employee_requester' => 'aanvrager@almere.nl', 'employee_recipient' => $member->id,
        'event_cost_id' => $cost->id, 'status' => RequestStatus::ACCEPTED,
        'account_name' => 'A & B', 'iban' => 'NL91ABNA0417164300',
    ]);
    $session = magicSession('hoofd@almere.nl', isAdmin: true, isSuper: true);
    $session['debtor_data'] = ['debtorName' => 'Vereniging & medewerkers', 'debtorIban' => 'NL91ABNA0417164300'];

    $response = $this->withSession($session)->get(route('charity.admin.download.sepa'))->assertOk();
    $xml = simplexml_load_string($response->streamedContent());
    $xml->registerXPathNamespace('sepa', 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.09');

    expect((string) $xml->xpath('//sepa:Cdtr/sepa:Nm')[0])->toBe('A & B')
        ->and((string) $xml->xpath('//sepa:Ustrd')[0])->toContain('Huwelijk & partnerschap')
        ->and($supportRequest->fresh()->status)->toBe(RequestStatus::EXPORTED);

    $this->patch(route('charity.admin.requests.update', $supportRequest), ['status' => RequestStatus::PAID->value])
        ->assertSessionHasNoErrors();

    expect($supportRequest->fresh()->status)->toBe(RequestStatus::PAID)
        ->and($supportRequest->fresh()->iban)->toBeNull()
        ->and($supportRequest->fresh()->account_name)->toBeNull();
    Mail::assertSent(\App\Mail\Charity\RequestPaidMail::class, 1);
});
