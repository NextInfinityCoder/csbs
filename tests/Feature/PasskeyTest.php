<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\Features;

test('users can have passkeys', function () {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(PasskeyUser::class)
        ->and($user->passkeys()->count())->toBe(0);
});

test('passkeys are enabled and two-factor authentication is turned off', function () {
    expect(Features::canManagePasskeys())->toBeTrue()
        ->and(Features::canManageTwoFactorAuthentication())->toBeFalse();
});

test('the security page lets users manage passkeys without two-factor settings', function () {
    $this->actingAs(User::factory()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/security')
            ->where('canManagePasskeys', true)
            ->where('passkeys', [])
            ->where('canManageTwoFactor', false)
            ->missing('twoFactorEnabled')
        );
});

test('logged in users can request passkey registration options', function () {
    $this->actingAs(User::factory()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->getJson(route('passkey.registration-options'))
        ->assertOk()
        ->assertJsonStructure(['options' => ['challenge', 'rp', 'user']]);
});

test('guests can request passkey login options', function () {
    $this->getJson(route('passkey.login-options'))
        ->assertOk()
        ->assertJsonStructure(['options' => ['challenge']]);
});
