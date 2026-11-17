<?php

use App\Actions\GenerateEventZReport;
use App\BetSide;
use App\FightResult;
use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\Station;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('bets are numbered sequentially per station', function () {
    $firstStation = Station::factory()->create();
    $secondStation = Station::factory()->create();
    $fight = Fight::factory()->create();

    $firstStationBets = Bet::factory()->count(3)->for($fight)
        ->for(User::factory()->cashier($firstStation))->create();
    $secondStationBet = Bet::factory()->for($fight)
        ->for(User::factory()->cashier($secondStation))->create();
    $unassignedBet = Bet::factory()->for($fight)
        ->for(User::factory()->cashier())->create();

    expect($firstStationBets->pluck('station_sequence')->all())->toBe([1, 2, 3])
        ->and($secondStationBet->station_sequence)->toBe(1)
        ->and($unassignedBet->station_sequence)->toBeNull();
});

test('station numbering continues across events', function () {
    $station = Station::factory()->create();
    $cashier = User::factory()->cashier($station)->create();

    Bet::factory()->count(2)->for(Fight::factory())->for($cashier)->create();
    $laterBet = Bet::factory()->for(Fight::factory())->for($cashier)->create();

    expect($laterBet->station_sequence)->toBe(3);
});

test('the z-report lists each station with its bet totals and commission sales', function () {
    $stationA = Station::factory()->create(['name' => 'Station A']);
    $stationB = Station::factory()->create(['name' => 'Station B']);
    $cashierA = User::factory()->cashier($stationA)->create();
    $cashierB = User::factory()->cashier($stationB)->create();
    $unassignedCashier = User::factory()->cashier()->create();

    $previousEvent = Event::factory()->create(['date' => '2026-01-01 10:00:00', 'commission_rate' => 10]);
    $event = Event::factory()->create(['date' => '2026-02-01 10:00:00', 'commission_rate' => 7]);
    $laterEvent = Event::factory()->create(['date' => '2026-03-01 10:00:00', 'commission_rate' => 7]);

    $placeBet = fn (Event $event, FightResult $result, User $cashier, BetSide $side, int $amount): Bet => Bet::factory()
        ->for(Fight::factory()->for($event)->create(['result' => $result]))
        ->for($cashier)
        ->create(['bet_side' => $side, 'amount' => $amount]);

    $placeBet($previousEvent, FightResult::Meron, $cashierA, BetSide::Meron, 2000);
    $placeBet($previousEvent, FightResult::Cancelled, $cashierA, BetSide::Wala, 5000);

    $placeBet($event, FightResult::Meron, $cashierA, BetSide::Meron, 1000);
    $placeBet($event, FightResult::Meron, $cashierA, BetSide::Wala, 500);
    $placeBet($event, FightResult::Draw, $cashierA, BetSide::Draw, 100);
    $placeBet($event, FightResult::Cancelled, $cashierA, BetSide::Meron, 300);
    $placeBet($event, FightResult::Wala, $cashierB, BetSide::Wala, 200);
    $placeBet($event, FightResult::Wala, $unassignedCashier, BetSide::Meron, 400);

    $placeBet($laterEvent, FightResult::Meron, $cashierA, BetSide::Meron, 9000);

    $report = app(GenerateEventZReport::class)->handle($event);

    expect($report->all())->toEqual([
        [
            'station_id' => $stationA->id,
            'station' => 'Station A',
            'transaction_count' => 4,
            'gross_bets' => 1500.0,
            'draw_bets' => 100.0,
            'cancelled_bets' => 300.0,
            'sales' => 105.0,
        ],
        [
            'station_id' => $stationB->id,
            'station' => 'Station B',
            'transaction_count' => 1,
            'gross_bets' => 200.0,
            'draw_bets' => 0.0,
            'cancelled_bets' => 0.0,
            'sales' => 14.0,
        ],
        [
            'station_id' => null,
            'station' => 'Unassigned',
            'transaction_count' => 1,
            'gross_bets' => 400.0,
            'draw_bets' => 0.0,
            'cancelled_bets' => 0.0,
            'sales' => 28.0,
        ],
    ]);
});

test('an event without bets has an empty z-report', function () {
    expect(app(GenerateEventZReport::class)->handle(Event::factory()->create()))->toBeEmpty();
});

test('the event page includes the z-report', function () {
    $station = Station::factory()->create();
    $fight = Fight::factory()->create();
    Bet::factory()->for($fight)->for(User::factory()->cashier($station))->create();

    $this->actingAs(User::factory()->create())
        ->get(route('events.show', $fight->event))
        ->assertInertia(fn (Assert $page) => $page
            ->component('events/show')
            ->has('zReport', 1)
            ->where('zReport.0.station_id', $station->id)
        );
});

test('the printable z-report shows the station readings', function () {
    $this->freezeSecond();
    $station = Station::factory()->create(['name' => 'Station A']);
    $fight = Fight::factory()->create();
    Bet::factory()->for($fight)->for(User::factory()->cashier($station))->create();

    $this->actingAs(User::factory()->create(['name' => 'Report Admin']))
        ->get(route('events.z-report', $fight->event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('print/event-z-report')
            ->where('event.id', $fight->event->id)
            ->where('zReport.0.station', 'Station A')
            ->where('businessName', config('app.name'))
            ->missing('generatedBy')
            ->where('generatedAt', now()->toIso8601String())
        );
});

test('guests cannot view the printable z-report', function () {
    $event = Event::factory()->create();

    $this->get(route('events.z-report', $event))->assertRedirect(route('login'));
});
