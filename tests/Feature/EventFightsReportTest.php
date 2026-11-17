<?php

use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the printable fight list shows every fight of the event in fight order', function () {
    $this->freezeSecond();
    $event = Event::factory()->create();
    foreach ([3, 1, 2] as $fightNumber) {
        Fight::factory()->for($event)->create(['fight_number' => $fightNumber]);
    }
    Fight::factory()->create();

    $this->actingAs(User::factory()->create(['name' => 'Report Admin']))
        ->get(route('events.fights-report', $event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('print/event-fights')
            ->where('event.id', $event->id)
            ->has('fights', 3)
            ->where('fights.0.fight_number', 1)
            ->where('fights.1.fight_number', 2)
            ->where('fights.2.fight_number', 3)
            ->where('businessName', config('app.name'))
            ->missing('generatedBy')
            ->where('generatedAt', now()->toIso8601String())
        );
});

test('an event without fights has an empty fight list', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('events.fights-report', Event::factory()->create()))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('fights', 0));
});

test('guests cannot view the printable fight list', function () {
    $this->get(route('events.fights-report', Event::factory()->create()))
        ->assertRedirect(route('login'));
});
