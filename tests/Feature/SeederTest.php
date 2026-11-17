<?php

use App\Models\Bet;
use App\Models\Event;
use App\Models\Station;
use App\Models\User;
use App\UserType;
use Database\Seeders\BetSeeder;
use Database\Seeders\CashierSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\EventSeeder;
use Database\Seeders\FightSeeder;
use Database\Seeders\SetupSeeder;
use Illuminate\Support\Facades\Hash;

test('the setup seeder creates one admin, one encoder, ten stations and a cashier for each station', function () {
    $this->seed(SetupSeeder::class);

    $admin = User::where('type', UserType::Admin)->sole();
    $encoder = User::where('type', UserType::Encoder)->sole();

    expect($admin->email)->toBe('admin@csbs.com')
        ->and($encoder->email)->toBe('encoder@csbs.com')
        ->and($admin->hasVerifiedEmail())->toBeTrue()
        ->and($encoder->hasVerifiedEmail())->toBeTrue()
        ->and(Station::orderBy('id')->pluck('name')->all())->toBe(
            array_map(fn (int $number): string => "Station {$number}", range(1, 10)),
        );

    $cashiers = User::where('type', UserType::Cashier)->with('station')->orderBy('id')->get();

    expect($cashiers)->toHaveCount(10);

    $cashiers->each(function (User $cashier, int $index): void {
        $number = $index + 1;

        expect($cashier->name)->toBe("Cashier {$number}")
            ->and($cashier->email)->toBe("cashier{$number}@example.com")
            ->and($cashier->station->name)->toBe("Station {$number}")
            ->and($cashier->hasVerifiedEmail())->toBeTrue();
    });
});

test('the seeded accounts can log in and land on their start page', function (string $email, string $expectedRoute) {
    $this->seed(SetupSeeder::class);

    $this->post(route('login.store'), ['email' => $email, 'password' => SetupSeeder::DEFAULT_PASSWORD])
        ->assertRedirect(route('home.redirect'));

    $this->get(route('home.redirect'))->assertRedirect(route($expectedRoute));
})->with([
    'admin' => ['admin@csbs.com', 'dashboard'],
    'encoder' => ['encoder@csbs.com', 'encode.index'],
    'cashier' => ['cashier1@example.com', 'events.index'],
]);

test('the setup seeder can run again without duplicating or overwriting anything', function () {
    Station::factory()->create(['name' => 'Station 3', 'description' => 'Kept as is']);
    $this->seed(SetupSeeder::class);

    User::where('email', 'admin@csbs.com')->update(['password' => Hash::make('changed-password')]);
    $this->seed(SetupSeeder::class);

    expect(User::count())->toBe(12)
        ->and(Station::count())->toBe(10)
        ->and(Station::where('name', 'Station 3')->value('description'))->toBe('Kept as is')
        ->and(Hash::check('changed-password', User::where('email', 'admin@csbs.com')->value('password')))->toBeTrue();
});

test('the database seeder only runs the setup', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::where('type', UserType::Admin)->count())->toBe(1)
        ->and(User::where('type', UserType::Encoder)->count())->toBe(1)
        ->and(User::where('type', UserType::Cashier)->count())->toBe(10)
        ->and(Station::count())->toBe(10)
        ->and(Event::count())->toBe(0)
        ->and(Bet::count())->toBe(0);
});

test('the sample data seeders give every bet a barcode and station number', function () {
    $this->seed([
        SetupSeeder::class,
        EventSeeder::class,
        FightSeeder::class,
        CashierSeeder::class,
        BetSeeder::class,
    ]);

    expect(User::where('type', UserType::Cashier)->count())->toBe(33)
        ->and(Bet::count())->toBeGreaterThan(0)
        ->and(Bet::whereNull('barcode')->count())->toBe(0)
        ->and(Bet::whereNotNull('station_id')->whereNull('station_sequence')->count())->toBe(0);
});
