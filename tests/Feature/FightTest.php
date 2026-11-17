<?php

use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\UniqueConstraintViolationException;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('fights.index'))->assertRedirect(route('login'));
});

test('authenticated users can view a paginated list of fights', function () {
    Fight::factory()->count(15)->for(Event::factory())->create();

    $this->actingAs(User::factory()->create())
        ->get(route('fights.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('fights/index')
            ->has('fights.data', 10)
            ->where('fights.total', 15)
            ->where('fights.last_page', 2)
        );
});

test('the second page shows the remaining fights', function () {
    Fight::factory()->count(15)->for(Event::factory())->create();

    $this->actingAs(User::factory()->create())
        ->get(route('fights.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('fights.data', 5)
            ->where('fights.current_page', 2)
        );
});

test('fights are listed by newest event first then by highest fight number', function () {
    $olderEvent = Event::factory()->create(['name' => 'Older Event', 'date' => '2026-01-01 10:00:00']);
    $newerEvent = Event::factory()->create(['name' => 'Newer Event', 'date' => '2026-06-01 10:00:00']);

    Fight::factory()->count(2)->for($olderEvent)
        ->state(new Sequence(['fight_number' => 1], ['fight_number' => 2]))
        ->create();
    Fight::factory()->count(2)->for($newerEvent)
        ->state(new Sequence(['fight_number' => 1], ['fight_number' => 2]))
        ->create();

    $this->actingAs(User::factory()->create())
        ->get(route('fights.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('fights.data.0.event.name', 'Newer Event')
            ->where('fights.data.0.fight_number', 2)
            ->where('fights.data.1.event.name', 'Newer Event')
            ->where('fights.data.1.fight_number', 1)
            ->where('fights.data.2.event.name', 'Older Event')
            ->where('fights.data.2.fight_number', 2)
        );
});

test('a fight number can only be used once per event', function () {
    $event = Event::factory()->create();
    Fight::factory()->for($event)->create(['fight_number' => 1]);

    Fight::factory()->for($event)->create(['fight_number' => 1]);
})->throws(UniqueConstraintViolationException::class);
