<?php

use App\Actions\GenerateDailyAnalysis;
use App\BetSide;
use App\FightResult;
use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

test('daily totals group bets by placement day and payouts by claim day', function () {
    $today = CarbonImmutable::parse('2026-11-17');
    $event = Event::factory()->create(['commission_rate' => 10]);
    $meronWin = Fight::factory()->for($event)->create(['result' => FightResult::Meron]);
    $cancelledFight = Fight::factory()->for($event)->cancelled()->create();

    $placeBet = fn (Fight $fight, BetSide $side, int $amount, string $placedAt, array $claim = []): Bet => Bet::factory()
        ->for($fight)
        ->create(['bet_side' => $side, 'amount' => $amount, 'created_at' => $placedAt, ...$claim]);

    $placeBet($meronWin, BetSide::Meron, 1000, '2026-11-15 09:00:00', [
        'claim_amount' => 1800,
        'claim_from_user_id' => User::factory()->cashier()->create()->id,
        'claimed_at' => '2026-11-16 10:00:00',
    ]);
    $placeBet($meronWin, BetSide::Wala, 500, '2026-11-15 23:59:00');
    $placeBet($meronWin, BetSide::Draw, 200, '2026-11-15 12:00:00');
    $placeBet($cancelledFight, BetSide::Meron, 700, '2026-11-17 08:00:00');
    $placeBet($meronWin, BetSide::Meron, 9999, '2026-11-14 23:59:59');

    $days = app(GenerateDailyAnalysis::class)->handle(3, $today);

    expect($days->all())->toEqual([
        ['date' => '2026-11-15', 'total_bets' => 1500.0, 'total_commission' => 150.0, 'total_payout' => 0.0],
        ['date' => '2026-11-16', 'total_bets' => 0.0, 'total_commission' => 0.0, 'total_payout' => 1800.0],
        ['date' => '2026-11-17', 'total_bets' => 0.0, 'total_commission' => 0.0, 'total_payout' => 0.0],
    ]);
});

test('commission uses each event commission rate', function () {
    $today = CarbonImmutable::parse('2026-11-17');

    foreach ([5 => 1000, 10 => 2000] as $rate => $amount) {
        Bet::factory()
            ->for(Fight::factory()->for(Event::factory()->create(['commission_rate' => $rate]))->create(['result' => FightResult::Wala]))
            ->create(['bet_side' => BetSide::Wala, 'amount' => $amount, 'created_at' => '2026-11-17 10:00:00']);
    }

    $days = app(GenerateDailyAnalysis::class)->handle(1, $today);

    expect($days->sole()['total_commission'])->toEqual(250.0);
});

test('the dashboard shows the daily analysis for the selected range', function (?int $requestedRange, int $expectedRange) {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard', ['range' => $requestedRange]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('range', $expectedRange)
            ->has('days', $expectedRange)
            ->where('days.'.($expectedRange - 1).'.date', now()->toDateString())
        );
})->with([
    'default range' => [null, 30],
    'today only' => [1, 1],
    'seven days' => [7, 7],
    'ninety days' => [90, 90],
    'unsupported range falls back to default' => [365, 30],
]);
