<?php

use App\Actions\EncodeFight;
use App\BetSide;
use App\EventStatus;
use App\FightResult;
use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\Station;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array<string, int|string>
 */
function encodeEntry(array $overrides = []): array
{
    return [
        'fight_number' => 1,
        'meron_amount' => 2000,
        'meron_bettors' => 5,
        'wala_amount' => 2000,
        'wala_bettors' => 3,
        'date' => '2026-11-17',
        'time_open' => '11:30',
        'time_close' => '11:35',
        'result' => FightResult::Meron->value,
        ...$overrides,
    ];
}

test('odds follow the payout formula rounded down to one decimal', function (int $amount, int $againstAmount, int $rate, float $expectedOdds) {
    expect(Fight::calculateOdds($amount, $againstAmount, $rate))->toBe($expectedOdds);
})->with([
    'even sides at 10%' => [2000, 2000, 10, 180.0],
    'favourite is rounded down' => [7000, 3000, 10, 128.5],
    'underdog' => [3000, 7000, 10, 300.0],
    'at 7%' => [3000, 2000, 7, 155.0],
    'no bets on the side' => [0, 2000, 10, 0.0],
]);

test('a side total is split into whole bets of at least the minimum', function (int $total, int $bettors) {
    $amounts = app(EncodeFight::class)->distributeAmount($total, $bettors);

    expect($amounts)->toHaveCount($bettors)
        ->and(array_sum($amounts))->toBe($total)
        ->and($amounts)->each->toBeInt()->toBeGreaterThanOrEqual(EncodeFight::MINIMUM_BET);
})->with([
    'example meron' => [2000, 5],
    'example wala' => [2000, 3],
    'exactly the minimum' => [500, 5],
    'odd total' => [12345, 7],
    'many bettors' => [250000, 300],
    'no bettors' => [0, 0],
]);

test('encoding creates the fight with the example totals and odds', function () {
    $event = Event::factory()->ongoing()->create(['commission_rate' => 10]);
    User::factory()->count(3)->cashier(Station::factory()->create())->create();

    $this->actingAs(User::factory()->encoder()->create())
        ->post(route('encode.store', $event), encodeEntry())
        ->assertRedirect(route('encode.show', $event))
        ->assertSessionHasNoErrors();

    $fight = $event->fights()->sole();

    expect($fight->fight_number)->toBe(1)
        ->and($fight->meron_amount)->toBe('2000.00')
        ->and($fight->wala_amount)->toBe('2000.00')
        ->and($fight->meron_odds)->toBe('180.00')
        ->and($fight->wala_odds)->toBe('180.00')
        ->and($fight->percentage)->toBe('10.00')
        ->and($fight->commission)->toBe('400.00')
        ->and($fight->result)->toBe(FightResult::Meron)
        ->and($fight->bet_open->format('Y-m-d H:i'))->toBe('2026-11-17 11:30')
        ->and($fight->bet_closed->format('Y-m-d H:i'))->toBe('2026-11-17 11:35')
        ->and($fight->canceled_at)->toBeNull();
});

test('encoding generates one bet per bettor assigned to cashiers', function () {
    $event = Event::factory()->ongoing()->create(['commission_rate' => 10]);
    $station = Station::factory()->create();
    $cashiers = User::factory()->count(3)->cashier($station)->create();

    $this->actingAs(User::factory()->encoder()->create())
        ->post(route('encode.store', $event), encodeEntry());

    $bets = Bet::all();
    $meronBets = $bets->where('bet_side', BetSide::Meron);
    $walaBets = $bets->where('bet_side', BetSide::Wala);
    $betOpen = CarbonImmutable::parse('2026-11-17 11:30');
    $betClosed = CarbonImmutable::parse('2026-11-17 11:35');

    expect($meronBets)->toHaveCount(5)
        ->and($walaBets)->toHaveCount(3)
        ->and((int) $meronBets->sum('amount'))->toBe(2000)
        ->and((int) $walaBets->sum('amount'))->toBe(2000);

    $bets->each(function (Bet $bet) use ($event, $cashiers, $station, $betOpen, $betClosed): void {
        expect($bet->event_id)->toBe($event->id)
            ->and($cashiers->pluck('id'))->toContain($bet->user_id)
            ->and($bet->station_id)->toBe($station->id)
            ->and((float) $bet->amount)->toBeGreaterThanOrEqual(100.0)
            ->and(fmod((float) $bet->amount, 1.0))->toBe(0.0)
            ->and($bet->created_at->betweenIncluded($betOpen, $betClosed))->toBeTrue();
    });

    expect($bets->sortBy('created_at')->pluck('station_sequence')->values()->all())->toBe(range(1, 8));
});

test('only winning bets are claimed at the odds within twenty minutes of closing', function () {
    $event = Event::factory()->ongoing()->create(['commission_rate' => 10]);
    $cashiers = User::factory()->count(3)->cashier()->create();

    $this->actingAs(User::factory()->encoder()->create())
        ->post(route('encode.store', $event), encodeEntry());

    $betClosed = CarbonImmutable::parse('2026-11-17 11:35');

    Bet::where('bet_side', BetSide::Meron)->get()->each(function (Bet $bet) use ($cashiers, $betClosed): void {
        $minutesAfterClosing = $betClosed->diffInSeconds($bet->claimed_at) / 60;

        expect((float) $bet->claim_amount)->toBe(round((float) $bet->amount * 1.8, 2))
            ->and($cashiers->pluck('id'))->toContain($bet->claim_from_user_id)
            ->and($minutesAfterClosing)->toBeGreaterThanOrEqual(3)->toBeLessThanOrEqual(20);
    });

    expect(Bet::where('bet_side', BetSide::Wala)->whereNotNull('claimed_at')->count())->toBe(0);
});

test('every bet on a draw or cancelled fight is refunded within five minutes of closing with no commission', function (FightResult $result) {
    $event = Event::factory()->ongoing()->create(['commission_rate' => 10, 'commission_amount' => 0]);
    $cashiers = User::factory()->count(3)->cashier()->create();

    $this->actingAs(User::factory()->encoder()->create())
        ->post(route('encode.store', $event), encodeEntry(['result' => $result->value]));

    $fight = $event->fights()->sole();
    $betClosed = CarbonImmutable::parse('2026-11-17 11:35');
    $bets = Bet::all();

    expect($bets)->toHaveCount(8)
        ->and($fight->canceled_at?->format('H:i'))->toBe($result === FightResult::Cancelled ? '11:35' : null)
        ->and($fight->commission)->toBe('0.00')
        ->and($event->fresh()->commission_amount)->toBe('0.00');

    $bets->each(function (Bet $bet) use ($cashiers, $betClosed): void {
        $secondsAfterClosing = $betClosed->diffInSeconds($bet->claimed_at);

        expect($bet->claim_amount)->toBe($bet->amount)
            ->and($cashiers->pluck('id'))->toContain($bet->claim_from_user_id)
            ->and($secondsAfterClosing)->toBeGreaterThanOrEqual(30)->toBeLessThanOrEqual(300);
    });
})->with([FightResult::Cancelled, FightResult::Draw]);

test('the encoded entry is validated', function (array $overrides, string $invalidField) {
    $event = Event::factory()->ongoing()->create();
    Fight::factory()->for($event)->create(['fight_number' => 9]);
    User::factory()->cashier()->create();

    $this->actingAs(User::factory()->encoder()->create())
        ->post(route('encode.store', $event), encodeEntry($overrides))
        ->assertSessionHasErrors($invalidField);

    expect($event->fights()->count())->toBe(1);
})->with([
    'fight number already used in the event' => [['fight_number' => 9], 'fight_number'],
    'bet below the minimum per bettor' => [['meron_amount' => 499, 'meron_bettors' => 5], 'meron_amount'],
    'bet without bettors' => [['wala_amount' => 500, 'wala_bettors' => 0], 'wala_bettors'],
    'decimal amount' => [['meron_amount' => 2000.5], 'meron_amount'],
    'no bettors at all' => [['meron_amount' => 0, 'meron_bettors' => 0, 'wala_amount' => 0, 'wala_bettors' => 0], 'meron_bettors'],
    'close before open' => [['time_open' => '11:35', 'time_close' => '11:30'], 'time_close'],
    'invalid time' => [['time_open' => '25:00'], 'time_open'],
    'unknown result' => [['result' => 'pending'], 'result'],
    'too many bettors' => [['meron_amount' => 200000, 'meron_bettors' => 1001], 'meron_bettors'],
]);

test('encoding needs at least one cashier', function () {
    $event = Event::factory()->ongoing()->create();

    $this->actingAs(User::factory()->encoder()->create())
        ->post(route('encode.store', $event), encodeEntry())
        ->assertSessionHasErrors('cashiers');

    expect(Fight::count())->toBe(0);
});

test('the same fight number can be used in another event', function () {
    Fight::factory()->for(Event::factory())->create(['fight_number' => 1]);
    $event = Event::factory()->ongoing()->create();
    User::factory()->cashier()->create();

    $this->actingAs(User::factory()->encoder()->create())
        ->post(route('encode.store', $event), encodeEntry())
        ->assertSessionHasNoErrors();

    expect($event->fights()->count())->toBe(1);
});

test('encoders see the events and the encoding page with the next fight number', function () {
    $event = Event::factory()->ongoing()->create();
    $fight = Fight::factory()->for($event)->create(['fight_number' => 4, 'bet_closed' => '2026-11-17 13:42:00']);
    $encoder = User::factory()->encoder()->create();

    $this->actingAs($encoder)
        ->get(route('encode.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('encode/index')
            ->where('events.data.0.fights_count', 1)
        );

    $this->actingAs($encoder)
        ->get(route('encode.show', $event))
        ->assertInertia(fn (Assert $page) => $page
            ->component('encode/show')
            ->where('nextFightNumber', 5)
            ->where('defaultTimeOpen', $fight->bet_closed->format('H:i'))
            ->where('today', now()->toDateString())
            ->has('fights', 1)
        );
});

test('only encoders can encode', function (string $state) {
    $event = Event::factory()->ongoing()->create();
    User::factory()->cashier()->create();
    $user = User::factory()->{$state}()->create();

    $this->actingAs($user)->get(route('encode.index'))->assertForbidden();
    $this->actingAs($user)->get(route('encode.show', $event))->assertForbidden();
    $this->actingAs($user)->post(route('encode.store', $event), encodeEntry())->assertForbidden();

    expect(Fight::count())->toBe(0);
})->with(['admin', 'cashier']);

test('encoders land on the encode page and cannot open admin pages', function () {
    $encoder = User::factory()->encoder()->create();

    $this->actingAs($encoder)->get(route('home'))->assertRedirect(route('encode.index'));
    $this->actingAs($encoder)->get(route('dashboard'))->assertForbidden();
    $this->actingAs($encoder)->get(route('events.index'))->assertOk();
});

test('each encoded fight adds its commission to the event', function () {
    $event = Event::factory()->ongoing()->create(['commission_rate' => 10, 'commission_amount' => 150]);
    User::factory()->cashier()->create();
    $encoder = User::factory()->encoder()->create();

    $this->actingAs($encoder)->post(route('encode.store', $event), encodeEntry());
    expect($event->fresh()->commission_amount)->toBe('550.00');

    $this->actingAs($encoder)->post(route('encode.store', $event), encodeEntry([
        'fight_number' => 2,
        'meron_amount' => 3000,
        'wala_amount' => 1500,
        'result' => FightResult::Draw->value,
    ]));
    expect($event->fresh()->commission_amount)->toBe('550.00');

    $this->actingAs($encoder)->post(route('encode.store', $event), encodeEntry([
        'fight_number' => 3,
        'result' => FightResult::Cancelled->value,
    ]));
    expect($event->fresh()->commission_amount)->toBe('550.00');

    $this->actingAs($encoder)->post(route('encode.store', $event), encodeEntry([
        'fight_number' => 4,
        'meron_amount' => 3000,
        'wala_amount' => 1500,
        'result' => FightResult::Wala->value,
    ]));
    expect($event->fresh()->commission_amount)->toBe('1000.00');
});

test('a rejected entry does not change the event commission', function () {
    $event = Event::factory()->ongoing()->create(['commission_amount' => 150]);
    User::factory()->cashier()->create();

    $this->actingAs(User::factory()->encoder()->create())
        ->post(route('encode.store', $event), encodeEntry(['time_close' => '11:00']))
        ->assertSessionHasErrors('time_close');

    expect($event->fresh()->commission_amount)->toBe('150.00');
});

test('the first fight of an event has no default open time', function () {
    $this->actingAs(User::factory()->encoder()->create())
        ->get(route('encode.show', Event::factory()->ongoing()->create()))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextFightNumber', 1)
            ->where('defaultTimeOpen', null)
        );
});

test('generated bets are inserted in batches', function () {
    $event = Event::factory()->ongoing()->create(['commission_rate' => 10]);
    User::factory()->count(3)->cashier(Station::factory()->create())->create();

    $betQueries = [];
    DB::listen(function ($query) use (&$betQueries): void {
        if (str_contains($query->sql, '"bets"')) {
            $betQueries[] = strtok(strtolower(ltrim($query->sql)), ' ');
        }
    });

    $this->actingAs(User::factory()->encoder()->create())
        ->post(route('encode.store', $event), encodeEntry([
            'meron_amount' => 60000,
            'meron_bettors' => 300,
            'wala_amount' => 60000,
            'wala_bettors' => 300,
        ]))
        ->assertSessionHasNoErrors();

    expect(Bet::count())->toBe(600)
        ->and(array_count_values($betQueries)['insert'] ?? 0)->toBe(2)
        ->and(count($betQueries))->toBeLessThanOrEqual(6);
});

test('batched bets get unique barcodes and continue each station numbering', function () {
    $event = Event::factory()->ongoing()->create(['commission_rate' => 10]);
    $station = Station::factory()->create();
    $cashier = User::factory()->cashier($station)->create();
    $existingBets = Bet::factory()->count(4)->for(Fight::factory())->for($cashier)->create();

    $this->actingAs(User::factory()->encoder()->create())
        ->post(route('encode.store', $event), encodeEntry());

    $encodedBets = Bet::where('event_id', $event->id)->orderBy('created_at')->orderBy('station_sequence')->get();

    expect($encodedBets)->toHaveCount(8)
        ->and($encodedBets->pluck('station_sequence')->all())->toBe(range(5, 12))
        ->and(Bet::pluck('barcode')->unique())->toHaveCount(12)
        ->and($encodedBets->pluck('barcode')->intersect($existingBets->pluck('barcode')))->toBeEmpty();

    $encodedBets->each(fn (Bet $bet) => expect($bet->barcode)->toMatch('/^[1-9]\d{11}$/'));
});

test('batch barcode generation returns the requested number of unused barcodes', function () {
    Bet::factory()->count(3)->create();

    $barcodes = Bet::generateUniqueBarcodes(250);

    expect($barcodes)->toHaveCount(250)
        ->and(array_unique($barcodes))->toHaveCount(250)
        ->and(array_intersect($barcodes, Bet::pluck('barcode')->all()))->toBeEmpty()
        ->and($barcodes)->each->toMatch('/^[1-9]\d{11}$/');
});

test('only ongoing events are listed for encoding', function () {
    $ongoingEvent = Event::factory()->ongoing()->create(['name' => 'Live Derby']);
    Event::factory()->upcoming()->create();
    Event::factory()->create(['status' => EventStatus::Completed]);
    Event::factory()->create(['status' => EventStatus::Cancelled]);

    $this->actingAs(User::factory()->encoder()->create())
        ->get(route('encode.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('events.data', 1)
            ->where('events.data.0.id', $ongoingEvent->id)
            ->where('events.total', 1)
        );
});

test('events that are not ongoing cannot be encoded', function (EventStatus $status) {
    $event = Event::factory()->create(['status' => $status]);
    User::factory()->cashier()->create();
    $encoder = User::factory()->encoder()->create();

    $this->actingAs($encoder)->get(route('encode.show', $event))->assertForbidden();
    $this->actingAs($encoder)->post(route('encode.store', $event), encodeEntry())->assertForbidden();
    $this->actingAs($encoder)->post(route('encode.store', $event), encodeEntry(['time_close' => '00:00']))->assertForbidden();

    expect(Fight::count())->toBe(0);
})->with([EventStatus::Upcoming, EventStatus::Completed, EventStatus::Cancelled]);
