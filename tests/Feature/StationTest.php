<?php

use App\Models\Station;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access stations', function () {
    $station = Station::factory()->create();

    $this->get(route('stations.index'))->assertRedirect(route('login'));
    $this->post(route('stations.store'), ['name' => 'Station X'])->assertRedirect(route('login'));
    $this->put(route('stations.update', $station), ['name' => 'Station X'])->assertRedirect(route('login'));

    expect(Station::count())->toBe(1);
});

test('authenticated users can view a paginated list of stations with cashier counts', function () {
    $station = Station::factory()->create(['name' => 'AAA Station']);
    User::factory()->count(2)->cashier($station)->create();
    User::factory()->encoder()->create(['station_id' => $station->id]);
    Station::factory()->count(11)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('stations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('stations/index')
            ->has('stations.data', 10)
            ->where('stations.total', 12)
            ->where('stations.data.0.name', 'AAA Station')
            ->where('stations.data.0.cashiers_count', 2)
        );
});

test('authenticated users can create a station', function (?string $description) {
    $this->actingAs(User::factory()->create())
        ->post(route('stations.store'), [
            'name' => 'Station 1',
            'description' => $description,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $station = Station::sole();

    expect($station->name)->toBe('Station 1')
        ->and($station->description)->toBe($description);
})->with([
    'with description' => ['Near the entrance'],
    'without description' => [null],
]);

test('authenticated users can update a station', function () {
    $station = Station::factory()->create(['name' => 'Station 1', 'description' => 'Old']);

    $this->actingAs(User::factory()->create())
        ->put(route('stations.update', $station), [
            'name' => 'Station 1',
            'description' => '',
        ])
        ->assertSessionHasNoErrors();

    expect($station->fresh()->description)->toBeNull();
});

test('station input is validated', function (array $input, string $invalidField) {
    Station::factory()->create(['name' => 'Taken']);

    $this->actingAs(User::factory()->create())
        ->post(route('stations.store'), ['name' => 'Station 1', ...$input])
        ->assertSessionHasErrors($invalidField);

    expect(Station::count())->toBe(1);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'duplicate name' => [['name' => 'Taken'], 'name'],
    'name too long' => [['name' => str_repeat('a', 256)], 'name'],
    'description too long' => [['description' => str_repeat('a', 1001)], 'description'],
]);
