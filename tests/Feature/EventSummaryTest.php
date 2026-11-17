<?php

use App\Actions\GenerateEventSummary;
use App\BetSide;
use App\FightResult;
use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\Station;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Create an event with bets on a meron win, a draw and a cancelled fight.
 *
 * @return array{event: Event, firstCashier: User, secondCashier: User, payoutCashier: User}
 */
function createEventWithSettledBets(): array
{
    $event = Event::factory()->create(['commission_rate' => 7]);
    $firstCashier = User::factory()->cashier(Station::factory()->create(['name' => 'Station 1']))->create(['name' => 'Alice']);
    $secondCashier = User::factory()->cashier()->create(['name' => 'Bob']);
    $payoutCashier = User::factory()->cashier()->create(['name' => 'Carol']);

    $meronWin = Fight::factory()->for($event)->create(['fight_number' => 1, 'result' => FightResult::Meron]);
    $drawFight = Fight::factory()->for($event)->create(['fight_number' => 2, 'result' => FightResult::Draw]);
    $cancelledFight = Fight::factory()->for($event)->cancelled()->create(['fight_number' => 3]);

    $placeBet = function (Fight $fight, User $cashier, BetSide $side, int $amount, ?float $claimAmount = null, ?User $claimedFrom = null): Bet {
        return Bet::factory()->for($fight)->for($cashier)->create([
            'bet_side' => $side,
            'amount' => $amount,
            'claim_amount' => $claimAmount,
            'claim_from_user_id' => $claimedFrom?->id,
            'claimed_at' => $claimAmount === null ? null : now(),
        ]);
    };

    $placeBet($meronWin, $firstCashier, BetSide::Meron, 1000, 1800, $secondCashier);
    $placeBet($meronWin, $secondCashier, BetSide::Wala, 500);
    $placeBet($meronWin, $firstCashier, BetSide::Draw, 100);

    $placeBet($drawFight, $firstCashier, BetSide::Meron, 300, 300, $firstCashier);
    $placeBet($drawFight, $secondCashier, BetSide::Draw, 200, 1600, $secondCashier);

    $placeBet($cancelledFight, $firstCashier, BetSide::Wala, 400, 400, $payoutCashier);
    $placeBet($cancelledFight, $secondCashier, BetSide::Draw, 50);

    Bet::factory()->for(Fight::factory()->create(['result' => FightResult::Wala]))->create([
        'bet_side' => BetSide::Wala,
        'amount' => 9999,
        'claim_amount' => 9999,
        'claim_from_user_id' => $firstCashier->id,
        'claimed_at' => now(),
    ]);

    return compact('event', 'firstCashier', 'secondCashier', 'payoutCashier');
}

test('the over-all summary is calculated from the event bets only', function () {
    ['event' => $event] = createEventWithSettledBets();

    $summary = app(GenerateEventSummary::class)->handle($event)['summary'];

    expect($summary)->toEqual([
        'total_commission' => 126.0,
        'total_bets' => 1800.0,
        'total_draw_bets' => 300.0,
        'total_cancelled_bets' => 450.0,
        'total_payout_paid' => 1800.0,
        'total_cancelled_draw_paid' => 700.0,
        'total_draw_paid' => 1600.0,
        'net_win_lose' => -1550.0,
    ]);
});

test('the cashier totals only list cashiers who took bets', function () {
    ['event' => $event] = createEventWithSettledBets();

    $cashiers = app(GenerateEventSummary::class)->handle($event)['cashiers'];

    expect($cashiers->all())->toEqual([
        [
            'id' => $cashiers[0]['id'],
            'name' => 'Alice',
            'station' => 'Station 1',
            'total_meron_bets' => 1300.0,
            'total_wala_bets' => 400.0,
            'total_draw_bets' => 100.0,
            'number_of_bets' => 4,
        ],
        [
            'id' => $cashiers[1]['id'],
            'name' => 'Bob',
            'station' => null,
            'total_meron_bets' => 0.0,
            'total_wala_bets' => 500.0,
            'total_draw_bets' => 250.0,
            'number_of_bets' => 3,
        ],
    ]);
});

test('an event without bets has an empty summary', function () {
    $event = Event::factory()->create();

    $report = app(GenerateEventSummary::class)->handle($event);

    expect($report['summary'])->each->toEqual(0.0)
        ->and($report['cashiers'])->toBeEmpty();
});

test('authenticated users can view the event summary page', function () {
    ['event' => $event] = createEventWithSettledBets();

    $this->actingAs(User::factory()->create())
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('events/show')
            ->where('event.id', $event->id)
            ->has('summary', 8)
            ->has('cashiers', 2)
        );
});

test('guests cannot view the event summary page', function () {
    $event = Event::factory()->create();

    $this->get(route('events.show', $event))->assertRedirect(route('login'));
});

test('the printable summary report shows the summary, the cashier totals', function () {
    $this->freezeSecond();
    ['event' => $event] = createEventWithSettledBets();

    $this->actingAs(User::factory()->create(['name' => 'Report Admin']))
        ->get(route('events.summary-report', $event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('print/event-summary')
            ->where('event.id', $event->id)
            ->has('summary', 8)
            ->has('cashiers', 2)
            ->missing('cashiers.0.total_cashout_paid')
            ->where('businessName', config('app.name'))
            ->missing('generatedBy')
            ->where('generatedAt', now()->toIso8601String())
        );
});

test('guests cannot view the printable summary report', function () {
    $this->get(route('events.summary-report', Event::factory()->create()))
        ->assertRedirect(route('login'));
});
