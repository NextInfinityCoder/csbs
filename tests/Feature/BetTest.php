<?php

use App\BetSide;
use App\Models\Bet;
use App\Models\Fight;
use App\Models\Station;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('bets.index'))->assertRedirect(route('login'));
});

test('a bet is given a unique 12 digit barcode when created', function () {
    $bets = Bet::factory()->count(20)->for(Fight::factory())->create();

    $bets->each(function (Bet $bet): void {
        expect($bet->barcode)->toMatch('/^[1-9]\d{11}$/');
    });

    expect($bets->pluck('barcode')->unique())->toHaveCount(20);
});

test('a provided barcode is kept', function () {
    $bet = Bet::factory()->create(['barcode' => '103778500618']);

    expect($bet->barcode)->toBe('103778500618');
});

test('a barcode cannot be used by two bets', function () {
    Bet::factory()->create(['barcode' => '103778500618']);
    Bet::factory()->create(['barcode' => '103778500618']);
})->throws(UniqueConstraintViolationException::class);

test('a bet belongs to the fight event and the cashier station', function () {
    $station = Station::factory()->create();
    $cashier = User::factory()->cashier($station)->create();
    $fight = Fight::factory()->create();

    $bet = Bet::factory()->for($fight)->for($cashier)->create();

    expect($bet->event_id)->toBe($fight->event_id)
        ->and($bet->station_id)->toBe($station->id);
});

test('authenticated users can view a paginated list of bets', function () {
    Bet::factory()->count(15)->for(Fight::factory())->create();

    $this->actingAs(User::factory()->create())
        ->get(route('bets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bets/index')
            ->has('bets.data', 10)
            ->where('bets.total', 15)
            ->where('bets.last_page', 2)
        );
});

test('bets are listed newest first with their related records', function () {
    $station = Station::factory()->create(['name' => 'Station 1']);
    $cashier = User::factory()->cashier($station)->create(['name' => 'Cashier One']);
    $claimer = User::factory()->cashier()->create(['name' => 'Cashier Two']);
    $fight = Fight::factory()->create(['fight_number' => 7]);

    Bet::factory()->for($fight)->for($cashier)->create(['created_at' => now()->subHour()]);
    $newestBet = Bet::factory()->for($fight)->for($cashier)->claimed($claimer)->create([
        'bet_side' => BetSide::Wala,
        'created_at' => now(),
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('bets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('bets.data.0.barcode', $newestBet->barcode)
            ->where('bets.data.0.bet_side', 'wala')
            ->where('bets.data.0.event.name', $fight->event->name)
            ->where('bets.data.0.fight.fight_number', 7)
            ->where('bets.data.0.user.name', 'Cashier One')
            ->where('bets.data.0.station.name', 'Station 1')
            ->where('bets.data.0.claimed_from.name', 'Cashier Two')
            ->where('bets.data.1.claimed_from', null)
        );
});
