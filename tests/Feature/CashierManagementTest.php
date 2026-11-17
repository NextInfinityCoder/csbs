<?php

use App\Models\Station;
use App\Models\User;
use App\UserType;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access cashier management', function () {
    $cashier = User::factory()->cashier()->create();

    $this->get(route('cashiers.index'))->assertRedirect(route('login'));
    $this->post(route('cashiers.store'), ['name' => 'New'])->assertRedirect(route('login'));
    $this->put(route('cashiers.update', $cashier), ['name' => 'New'])->assertRedirect(route('login'));
});

test('only cashiers are listed', function () {
    $station = Station::factory()->create(['name' => 'Station 1']);
    User::factory()->cashier($station)->create(['name' => 'Cashier One']);
    User::factory()->encoder()->create(['name' => 'Encoder One']);
    $admin = User::factory()->create(['name' => 'Admin One']);

    $this->actingAs($admin)
        ->get(route('cashiers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('cashiers/index')
            ->has('cashiers.data', 1)
            ->where('cashiers.data.0.name', 'Cashier One')
            ->where('cashiers.data.0.station.name', 'Station 1')
            ->has('stations', 1)
        );
});

test('cashiers are paginated', function () {
    User::factory()->count(15)->cashier()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('cashiers.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('cashiers.data', 5)
            ->where('cashiers.total', 15)
        );
});

test('creating a user always creates a verified cashier', function (bool $withStation) {
    $station = Station::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('cashiers.store'), [
            'name' => 'New Cashier',
            'email' => 'cashier@example.com',
            'type' => UserType::Admin->value,
            'station_id' => $withStation ? $station->id : '',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertSessionHasNoErrors();

    $cashier = User::where('email', 'cashier@example.com')->sole();

    expect($cashier->type)->toBe(UserType::Cashier)
        ->and($cashier->station_id)->toBe($withStation ? $station->id : null)
        ->and($cashier->hasVerifiedEmail())->toBeTrue()
        ->and(Hash::check('password', $cashier->password))->toBeTrue();
})->with([
    'with station' => [true],
    'without station' => [false],
]);

test('a cashier can be updated without changing the password', function () {
    $station = Station::factory()->create();
    $cashier = User::factory()->cashier()->create();
    $originalPassword = $cashier->password;

    $this->actingAs(User::factory()->create())
        ->put(route('cashiers.update', $cashier), [
            'name' => 'Renamed Cashier',
            'email' => $cashier->email,
            'type' => UserType::Admin->value,
            'station_id' => $station->id,
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertSessionHasNoErrors();

    $cashier->refresh();

    expect($cashier->name)->toBe('Renamed Cashier')
        ->and($cashier->type)->toBe(UserType::Cashier)
        ->and($cashier->station_id)->toBe($station->id)
        ->and($cashier->password)->toBe($originalPassword);
});

test('a cashier password and station can be changed', function () {
    $cashier = User::factory()->cashier(Station::factory()->create())->create();

    $this->actingAs(User::factory()->create())
        ->put(route('cashiers.update', $cashier), [
            'name' => $cashier->name,
            'email' => $cashier->email,
            'station_id' => '',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasNoErrors();

    $cashier->refresh();

    expect($cashier->station_id)->toBeNull()
        ->and(Hash::check('new-password', $cashier->password))->toBeTrue();
});

test('non-cashier users cannot be updated', function (string $state) {
    $user = User::factory()->{$state}()->create(['name' => 'Original']);

    $this->actingAs(User::factory()->create())
        ->put(route('cashiers.update', $user), [
            'name' => 'Changed',
            'email' => $user->email,
        ])
        ->assertNotFound();

    expect($user->fresh()->name)->toBe('Original');
})->with([
    'admin' => ['admin'],
    'encoder' => ['encoder'],
]);

test('cashier input is validated', function (array $input, string $invalidField) {
    User::factory()->cashier()->create(['email' => 'taken@example.com']);

    $this->actingAs(User::factory()->create())
        ->post(route('cashiers.store'), [
            'name' => 'New Cashier',
            'email' => 'cashier@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            ...$input,
        ])
        ->assertSessionHasErrors($invalidField);

    expect(User::where('email', 'cashier@example.com')->exists())->toBeFalse();
})->with([
    'missing name' => [['name' => ''], 'name'],
    'invalid email' => [['email' => 'not-an-email'], 'email'],
    'duplicate email' => [['email' => 'taken@example.com'], 'email'],
    'missing password' => [['password' => '', 'password_confirmation' => ''], 'password'],
    'unconfirmed password' => [['password_confirmation' => 'different'], 'password'],
    'unknown station' => [['station_id' => 999], 'station_id'],
]);
