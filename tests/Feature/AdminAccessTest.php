<?php

use App\Models\Station;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;

dataset('non-admin users', [
    'cashier' => fn () => User::factory()->cashier()->create(),
    'encoder' => fn () => User::factory()->encoder()->create(),
]);

dataset('admin-only requests', [
    'dashboard' => fn () => ['get', route('dashboard')],
    'station list' => fn () => ['get', route('stations.index')],
    'create station' => fn () => ['post', route('stations.store')],
    'update station' => fn () => ['put', route('stations.update', Station::factory()->create())],
    'cashier list' => fn () => ['get', route('cashiers.index')],
    'create cashier' => fn () => ['post', route('cashiers.store')],
    'update cashier' => fn () => ['put', route('cashiers.update', User::factory()->cashier()->create())],
    'change cashier status' => fn () => ['patch', route('cashiers.status', User::factory()->cashier()->create())],
]);

test('non-admin users are forbidden from admin-only pages', function (User $user, array $request) {
    [$method, $url] = $request;

    $this->actingAs($user)->{$method}($url)->assertForbidden();
})->with('non-admin users')->with('admin-only requests');

test('non-admin users cannot create stations or cashiers', function (User $user) {
    $this->actingAs($user)->post(route('stations.store'), ['name' => 'Station X']);
    $this->actingAs($user)->post(route('cashiers.store'), [
        'name' => 'New Cashier',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(Station::count())->toBe(0)
        ->and(User::where('email', 'new@example.com')->exists())->toBeFalse();
})->with('non-admin users');

test('admins can open the admin-only pages', function (string $routeName) {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route($routeName))
        ->assertOk();
})->with(['dashboard', 'stations.index', 'cashiers.index']);

test('non-admin users can still open the shared pages', function (User $user, string $routeName) {
    $this->actingAs($user)->get(route($routeName))->assertOk();
})->with('non-admin users')->with(['events.index', 'fights.index', 'bets.index']);

test('the home redirect sends each user type to their start page', function (string $state, string $expectedRoute) {
    $this->actingAs(User::factory()->{$state}()->create())
        ->get(route('home'))
        ->assertRedirect(route($expectedRoute));
})->with([
    'admin' => ['admin', 'dashboard'],
    'cashier' => ['cashier', 'events.index'],
    'encoder' => ['encoder', 'encode.index'],
]);

test('users land on their start page after logging in', function (string $state, string $expectedRoute) {
    $user = User::factory()->{$state}()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('home'));

    $this->get(route('home'))->assertRedirect(route($expectedRoute));
})->with([
    'admin' => ['admin', 'dashboard'],
    'cashier' => ['cashier', 'events.index'],
]);

test('unverified users visiting the home page are asked to verify their email', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('home'))
        ->assertRedirect(route('verification.notice'));
})->skip(
    fn (): bool => ! is_subclass_of(User::class, MustVerifyEmail::class),
    'The User model does not require email verification.',
);
