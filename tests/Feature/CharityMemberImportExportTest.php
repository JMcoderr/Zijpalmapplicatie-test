<?php

use App\Exports\Charity\MemberExport;
use App\Imports\Charity\MemberImport;
use App\Models\Charity\CharityAdmin;
use App\Models\Charity\Member;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

it('imports different members that share an email address', function () {
    app(MemberImport::class)->collection(collect([
        [123456, 'A. Medewerker', 'A.', null, null, null, null, 'gedeeld@almere.nl'],
        [123457, 'B. Medewerker', 'B.', null, null, null, null, 'gedeeld@almere.nl'],
    ]));

    expect(Member::query()->where('email', 'gedeeld@almere.nl')->count())->toBe(2);
});

it('does not soft delete members when an import contains no usable rows', function () {
    $member = Member::create([
        'id' => 123456,
        'email' => 'bestaand@almere.nl',
        'full_name' => 'Bestaand Lid',
    ]);

    expect(fn () => app(MemberImport::class)->collection(collect()))
        ->toThrow(\Illuminate\Validation\ValidationException::class);

    expect($member->fresh()->trashed())->toBeFalse();
});

it('exports member names, employee dates and birth dates', function () {
    Member::create([
        'id' => 123456,
        'email' => 'anna@almere.nl',
        'full_name' => 'Anna de Vries',
        'name' => 'Anna',
        'dob' => '1980-02-01',
        'years_of_service' => '2010-03-04',
    ]);

    $response = Excel::download(new MemberExport(Member::query()), 'leden.xlsx');
    $path = $response->getFile()->getPathname();

    try {
        $rows = IOFactory::load($path)->getActiveSheet()->toArray();

        expect($rows[0])->toContain('In dienst sinds', 'Geboortedatum')
            ->and($rows[1][0])->toBe('Anna')
            ->and($rows[1][1])->toBe('de Vries')
            ->and($rows[1][6])->toBe('123456')
            ->and($rows[1][9])->toBe('04-03-2010')
            ->and($rows[1][10])->toBe('01-02-1980');
    } finally {
        unlink($path);
    }
});

it('restricts the filtered member export to super admins', function () {
    Member::create([
        'id' => 123456,
        'email' => 'anna@almere.nl',
        'full_name' => 'Anna de Vries',
        'name' => 'Anna',
    ]);
    Member::create([
        'id' => 123457,
        'email' => 'bert@almere.nl',
        'full_name' => 'Bert Jansen',
        'name' => 'Bert',
    ]);

    $url = route('charity.admin.members.export', ['search' => '123456']);
    $this->get($url)->assertRedirect(route('charity.login'));

    CharityAdmin::create(['employee' => 'beheerder@almere.nl']);
    $adminSession = [
        'magic' => [
            'email' => 'beheerder@almere.nl',
            'expires_at' => now()->addHour(),
            'admin' => ['isAdmin' => true, 'isSuper' => false],
        ],
    ];
    $this->withSession($adminSession)->get($url)
        ->assertRedirect(route('charity.admin.requests.index'));

    CharityAdmin::query()->where('employee', 'beheerder@almere.nl')->update(['super' => now()]);
    $adminSession['magic']['admin']['isSuper'] = true;
    $response = $this->withSession($adminSession)->get($url)->assertOk();
    $path = $response->baseResponse->getFile()->getPathname();

    try {
        $rows = IOFactory::load($path)->getActiveSheet()->toArray();
        expect($rows)->toHaveCount(2)
            ->and($rows[1][6])->toBe('123456');
    } finally {
        unlink($path);
    }
});

it('repairs only a unique email index after explicit confirmation', function () {
    Schema::table('members', function (Blueprint $table) {
        $table->unique('email');
    });

    $this->artisan('members:check-import')
        ->expectsOutputToContain('Blokkerende unieke e-mailindex')
        ->assertExitCode(1);

    $this->artisan('members:check-import', ['--repair' => true, '--force' => true])
        ->expectsOutputToContain('Herstel voltooid')
        ->assertExitCode(0);

    expect(collect(Schema::getIndexes('members'))->contains(
        fn (array $index) => $index['unique'] && $index['columns'] === ['email'],
    ))->toBeFalse();
});