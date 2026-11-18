<?php

use App\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('events.index'))->assertRedirect(route('login'));
});

test('authenticated users can view a paginated list of events', function () {
    Event::factory()->count(15)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('events.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('events/index')
            ->has('events.data', 10)
            ->where('events.total', 15)
            ->where('events.last_page', 2)
        );
});

test('events are ordered by date with the newest first', function () {
    Event::factory()->create(['name' => 'Older Event', 'date' => '2026-01-01 09:00:00']);
    Event::factory()->create(['name' => 'Newer Event', 'date' => '2026-06-01 18:30:00']);

    $this->actingAs(User::factory()->create())
        ->get(route('events.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('events.data.0.name', 'Newer Event')
            ->where('events.data.0.date', Carbon::parse('2026-06-01 18:30:00')->toJSON())
            ->where('events.data.1.name', 'Older Event')
        );
});

test('the second page shows the remaining events', function () {
    Event::factory()->count(15)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('events.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('events.data', 5)
            ->where('events.current_page', 2)
        );
});

test('authenticated users can create an event', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('events.store'), [
            'name' => 'Grand Derby',
            'commission_rate' => 7,
            'multiplier' => 2,
            'status' => EventStatus::Upcoming->value,
            'date' => '2026-11-20T06:30:00.000Z',
        ])
        ->assertRedirect(route('events.index'))
        ->assertSessionHasNoErrors();

    $event = Event::sole();

    expect($event->name)->toBe('Grand Derby')
        ->and($event->commission_rate)->toBe('7.00')
        ->and($event->multiplier)->toBe('2.00')
        ->and($event->commission_amount)->toBe('0.00')
        ->and($event->status)->toBe(EventStatus::Upcoming)
        ->and($event->date->format('Y-m-d H:i'))->toBe('2026-11-20 14:30')
        ->and($event->date->tzName)->toBe('Asia/Manila');
});

test('the event date is stored in the app timezone whatever offset the browser sends', function (string $submittedDate) {
    $this->actingAs(User::factory()->create())
        ->post(route('events.store'), [
            'name' => 'Grand Derby',
            'commission_rate' => 7,
            'multiplier' => 2,
            'status' => EventStatus::Upcoming->value,
            'date' => $submittedDate,
        ])
        ->assertSessionHasNoErrors();

    expect(Event::sole()->date->format('Y-m-d H:i'))->toBe('2026-11-20 14:30');
})->with([
    'utc' => ['2026-11-20T06:30:00.000Z'],
    'manila offset' => ['2026-11-20T14:30:00+08:00'],
    'another timezone' => ['2026-11-19T22:30:00-08:00'],
    'without an offset' => ['2026-11-20 14:30:00'],
]);

test('authenticated users can update an event', function () {
    $event = Event::factory()->upcoming()->create(['commission_rate' => 7]);

    $this->actingAs(User::factory()->create())
        ->from(route('events.index', ['page' => 2]))
        ->put(route('events.update', $event), [
            'name' => 'Renamed Derby',
            'commission_rate' => 8.5,
            'multiplier' => 3.5,
            'status' => EventStatus::Ongoing->value,
            'date' => '2026-12-01T10:00:00+08:00',
        ])
        ->assertRedirect(route('events.index', ['page' => 2]))
        ->assertSessionHasNoErrors();

    $event->refresh();

    expect($event->name)->toBe('Renamed Derby')
        ->and($event->commission_rate)->toBe('8.50')
        ->and($event->multiplier)->toBe('3.50')
        ->and($event->status)->toBe(EventStatus::Ongoing)
        ->and($event->date->format('Y-m-d H:i'))->toBe('2026-12-01 10:00');
});

test('event input is validated', function (array $input, array $invalidFields) {
    $this->actingAs(User::factory()->create())
        ->post(route('events.store'), [
            'name' => 'Grand Derby',
            'commission_rate' => 7,
            'multiplier' => 2,
            'status' => EventStatus::Upcoming->value,
            'date' => '2026-11-20T14:30:00+08:00',
            ...$input,
        ])
        ->assertSessionHasErrors($invalidFields);

    expect(Event::count())->toBe(0);
})->with([
    'missing name' => [['name' => ''], ['name']],
    'name too long' => [['name' => str_repeat('a', 256)], ['name']],
    'missing commission rate' => [['commission_rate' => ''], ['commission_rate']],
    'negative commission rate' => [['commission_rate' => -1], ['commission_rate']],
    'commission rate above 100' => [['commission_rate' => 100.01], ['commission_rate']],
    'commission rate with too many decimals' => [['commission_rate' => 7.123], ['commission_rate']],
    'unknown status' => [['status' => 'archived'], ['status']],
    'missing date' => [['date' => ''], ['date']],
    'missing multiplier' => [['multiplier' => ''], ['multiplier']],
    'negative multiplier' => [['multiplier' => -1], ['multiplier']],
    'multiplier with too many decimals' => [['multiplier' => 2.555], ['multiplier']],
    'invalid date' => [['date' => 'not-a-date'], ['date']],
]);

test('guests cannot create or update events', function () {
    $event = Event::factory()->create();

    $this->post(route('events.store'), ['name' => 'Grand Derby'])
        ->assertRedirect(route('login'));

    $this->put(route('events.update', $event), ['name' => 'Renamed'])
        ->assertRedirect(route('login'));

    expect(Event::count())->toBe(1)
        ->and($event->fresh()->name)->toBe($event->name);
});

test('the events list includes each event multiplier', function () {
    Event::factory()->create(['multiplier' => 2.5]);

    $this->actingAs(User::factory()->create())
        ->get(route('events.index'))
        ->assertInertia(fn (Assert $page) => $page->where('events.data.0.multiplier', '2.50'));
});
