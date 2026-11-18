<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prevents a member from viewing or changing another member profile', function () {
    $member = User::factory()->create();
    $other = User::factory()->create(['phone' => '12345678']);

    $this->actingAs($member)->get(route('user.edit', $other))->assertRedirect();
    $this->put(route('user.update', $other), ['phone' => '87654321'])->assertRedirect();

    expect($other->fresh()->phone)->toBe('12345678');
});

it('shows the required field explanation on activity and shop administration forms', function () {
    $admin = User::factory()->admin()->create();
    $activity = \App\Models\Activity::factory()->create();
    $product = \App\Models\Product::factory()->create();

    foreach ([route('activity.create'), route('activity.edit', $activity), route('product.create'), route('product.edit', $product)] as $url) {
        $this->actingAs($admin)->get($url)->assertOk()->assertSee('Velden met * zijn verplicht.');
    }
});

it('prevents regular users from updating their own name and email', function () {
    $user = User::factory()->create([
        'firstName' => 'Jordy',
        'lastName' => 'Meijer',
        'email' => 'jordy@example.com',
        'phone' => '12345678',
    ]);

    $this->actingAs($user)
        ->put(route('user.update', $user), [
            'firstName' => 'Changed',
            'lastName' => 'Name',
            'email' => 'changed@example.com',
            'phone' => '87654321',
            'membership_start_date' => '2026-10-01',
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->firstName)->toBe('Jordy')
        ->and($user->lastName)->toBe('Meijer')
        ->and($user->email)->toBe('jordy@example.com')
        ->and($user->phone)->toBe('87654321')
        ->and($user->membership_start_date)->toBeNull();
});

it('allows admins to update user name and email', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'firstName' => 'Jordy',
        'lastName' => 'Meijer',
        'email' => 'jordy@example.com',
    ]);

    $this->actingAs($admin)
        ->put(route('user.update', $user), [
            'firstName' => 'Changed',
            'lastName' => 'Name',
            'email' => 'changed@example.com',
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->firstName)->toBe('Changed')
        ->and($user->lastName)->toBe('Name')
        ->and($user->email)->toBe('changed@example.com');
});

it('allows admins to update a user membership start date', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'type' => 'gepensioneerde',
    ]);

    $this->actingAs($admin)
        ->put(route('user.update', $user), [
            'membership_start_date' => '2026-10-01',
        ])
        ->assertRedirect();

    expect($user->fresh()->membership_start_date->format('Y-m-d'))->toBe('2026-10-01');
});
