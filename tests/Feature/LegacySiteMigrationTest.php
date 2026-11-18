<?php

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('database.connections.legacy_test', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);
    $source = DB::connection('legacy_test');
    $schema = $source->getSchemaBuilder();
    $schema->create('users', function (Blueprint $table) {
        $table->id();
        $table->string('first_name');
        $table->string('last_name');
        $table->string('email');
        $table->string('password_hash');
        $table->string('role')->default('user');
    });
    $schema->create('poules', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->text('description')->nullable();
        $table->dateTime('starts_at')->nullable();
    });
    $schema->create('matches', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('poule_id');
        $table->string('home_team');
        $table->string('away_team');
        $table->dateTime('kickoff');
        $table->unsignedTinyInteger('home_score')->nullable();
        $table->unsignedTinyInteger('away_score')->nullable();
        $table->dateTime('result_entered_at')->nullable();
    });
    $schema->create('predictions', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('match_id');
        $table->unsignedTinyInteger('home_score');
        $table->unsignedTinyInteger('away_score');
    });

    $source->table('users')->insert([
        'id' => 42, 'first_name' => 'Oud', 'last_name' => 'Account',
        'email' => 'speler@almere.nl', 'password_hash' => Hash::make('Legacy-password-42'),
        'role' => 'admin',
    ]);
    $source->table('poules')->insert(['id' => 7, 'name' => 'Oude poule', 'starts_at' => '2026-06-11 00:00:00']);
    $source->table('matches')->insert([
        'id' => 8, 'poule_id' => 7, 'home_team' => 'Nederland', 'away_team' => 'Belgie',
        'kickoff' => '2026-06-11 18:00:00', 'home_score' => 2, 'away_score' => 1,
        'result_entered_at' => '2026-06-11 20:00:00',
    ]);
    $source->table('predictions')->insert([
        'id' => 9, 'user_id' => 42, 'match_id' => 8, 'home_score' => 2, 'away_score' => 1,
    ]);
});

afterEach(function () {
    DB::purge('legacy_test');
});

it('previews the complete legacy football import without saving any rows', function () {
    $this->artisan('legacy:import', ['profile' => 'football', '--source' => 'legacy_test'])
        ->assertExitCode(0);

    expect(DB::table('tournaments')->count())->toBe(0)
        ->and(DB::table('users')->count())->toBe(0)
        ->and(DB::table('predictions')->count())->toBe(0);
});

it('maps legacy football relationships to existing accounts without granting admin rights', function () {
    $user = User::factory()->create(['email' => 'speler@almere.nl', 'is_admin' => false]);
    $password = $user->password;

    $this->artisan('legacy:import', ['profile' => 'football', '--source' => 'legacy_test', '--apply' => true])
        ->expectsConfirmation('Is er een gecontroleerde back-up van de doeldatabase?', 'yes')
        ->assertExitCode(0);

    $prediction = DB::table('predictions')->sole();
    expect($prediction->user_id)->toBe($user->id)
        ->and($prediction->points)->toBe(3)
        ->and($user->fresh()->password)->toBe($password)
        ->and($user->fresh()->is_admin)->toBeFalse()
        ->and(DB::table('tournaments')->count())->toBe(1)
        ->and(DB::table('teams')->count())->toBe(2)
        ->and(DB::table('tournament_participants')->count())->toBe(1);

    $tournament = \App\Models\Tournament\Tournament::sole();
    $this->get(route('tournament.show', $tournament))->assertOk()->assertSee('Nederland');

    $this->artisan('legacy:import', ['profile' => 'football', '--source' => 'legacy_test', '--apply' => true, '--force' => true])
        ->assertExitCode(0);
    expect(DB::table('predictions')->count())->toBe(1)
        ->and(DB::table('teams')->count())->toBe(2);
});

it('rolls back the entire legacy import when a prediction has no source account', function () {
    DB::connection('legacy_test')->table('predictions')->update(['user_id' => 999]);

    $this->artisan('legacy:import', ['profile' => 'football', '--source' => 'legacy_test', '--apply' => true])
        ->expectsConfirmation('Is er een gecontroleerde back-up van de doeldatabase?', 'yes')
        ->assertExitCode(1);

    expect(DB::table('users')->count())->toBe(0)
        ->and(DB::table('tournaments')->count())->toBe(0);
});

function prepareLegacyTables(array $tables): \Illuminate\Database\Connection
{
    $source = DB::connection('legacy_test');
    foreach (['predictions', 'matches', 'poules', 'users'] as $table) {
        $source->getSchemaBuilder()->drop($table);
    }
    foreach ($tables as $table) {
        $definition = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$table]);
        $source->statement($definition->sql);
    }

    return $source;
}

it('preserves charity relationships and reencrypts bank details with the destination key', function () {
    $source = prepareLegacyTables(['events', 'event_costs', 'members', 'admins', 'requests']);
    $oldKey = random_bytes(32);
    config()->set('legacy.charity_key', 'base64:'.base64_encode($oldKey));
    $encrypted = (new Encrypter($oldKey, 'AES-256-CBC'))->encryptString('NL91ABNA0417164300');

    $source->table('events')->insert(['id' => 17, 'title' => 'Geboorte']);
    $source->table('event_costs')->insert(['id' => 23, 'event_id' => 17, 'amount' => 25, 'start_date' => '2025-01-01']);
    $source->table('members')->insert(['id' => 456, 'email' => 'collega@almere.nl', 'full_name' => 'Collega']);
    $source->table('admins')->insert(['id' => 3, 'employee' => 'beheerder@almere.nl']);
    $source->table('requests')->insert([
        'id' => 12, 'employee_requester' => 'aanvrager@almere.nl', 'employee_recipient' => 456,
        'event_cost_id' => 23, 'iban' => $encrypted, 'account_name' => 'Collega', 'status' => 'accepted',
    ]);

    $options = ['profile' => 'charity', '--source' => 'legacy_test', '--apply' => true, '--force' => true];
    $this->artisan('legacy:import', $options)->assertExitCode(0);

    $stored = DB::table('requests')->sole();
    expect($stored->employee_recipient)->toBe(456)
        ->and($stored->event_cost_id)->toBe(23)
        ->and($stored->iban)->not->toBe($encrypted)
        ->and(Crypt::decryptString($stored->iban))->toBe('NL91ABNA0417164300');

    $this->artisan('legacy:import', $options)->assertExitCode(0);
    expect(DB::table('requests')->count())->toBe(1);
});

it('preserves Zijpalm account ids and password hashes', function () {
    $source = prepareLegacyTables(['users']);
    $password = Hash::make('Existing-zijpalm-password');
    $source->table('users')->insert([
        'id' => 82, 'firstName' => 'Bestaand', 'lastName' => 'Lid',
        'email' => 'lid@almere.nl', 'password' => $password,
    ]);

    $this->artisan('legacy:import', [
        'profile' => 'zijpalm', '--source' => 'legacy_test', '--apply' => true, '--force' => true,
    ])->assertExitCode(0);

    expect(User::findOrFail(82)->password)->toBe($password);
});

it('refuses to merge a Zijpalm database into populated target tables', function () {
    $source = prepareLegacyTables(['users']);
    User::factory()->create(['email' => 'bestaand@almere.nl']);
    $source->table('users')->insert([
        'id' => 82, 'firstName' => 'Bron', 'lastName' => 'Lid', 'email' => 'bron@almere.nl',
    ]);

    $this->artisan('legacy:import', [
        'profile' => 'zijpalm', '--source' => 'legacy_test', '--apply' => true, '--force' => true,
    ])->assertExitCode(1);

    expect(User::count())->toBe(1)
        ->and(User::where('email', 'bron@almere.nl')->exists())->toBeFalse();
});

it('preserves linked shop relationships without resetting later changes on a rerun', function () {
    $tables = ['users', 'activities', 'products', 'payments', 'orders', 'applications'];
    $source = prepareLegacyTables($tables);
    $user = User::factory()->create();
    $activity = \App\Models\Activity::factory()->create();
    $product = \App\Models\Product::factory()->create(['activity_id' => $activity->id]);
    $orderId = DB::table('orders')->insertGetId(['user_id' => $user->id]);
    $application = \App\Models\Application::factory()->create([
        'user_id' => $user->id, 'activity_id' => $activity->id, 'shop_order_id' => $orderId,
    ]);

    foreach ($tables as $table) {
        foreach (DB::table($table)->get() as $row) {
            $source->table($table)->insert((array) $row);
        }
    }
    foreach (array_reverse($tables) as $table) {
        DB::table($table)->delete();
    }

    $options = ['profile' => 'zijpalm', '--source' => 'legacy_test', '--apply' => true, '--force' => true];
    $this->artisan('legacy:import', $options)->assertExitCode(0);

    expect(DB::table('applications')->where('id', $application->id)->value('shop_order_id'))->toBe($orderId)
        ->and(DB::table('products')->where('id', $product->id)->value('activity_id'))->toBe($activity->id);

    DB::table('products')->where('id', $product->id)->update(['activity_id' => null]);
    $this->artisan('legacy:import', $options)->assertExitCode(0);
    expect(DB::table('products')->where('id', $product->id)->value('activity_id'))->toBeNull();
});