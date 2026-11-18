<?php

namespace Database\Factories;

use App\EventStatus;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->city().' Derby',
            'commission_rate' => fake()->randomFloat(2, 5, 12),
            'commission_amount' => fake()->randomFloat(2, 0, 500000),
            'multiplier' => 2,
            'status' => fake()->randomElement(EventStatus::cases()),
            'date' => fake()->dateTimeBetween('-3 months', '+1 month'),
        ];
    }

    /**
     * Indicate that the event is upcoming.
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventStatus::Upcoming,
            'commission_amount' => 0,
            'date' => fake()->dateTimeBetween('+1 day', '+1 month'),
        ]);
    }

    /**
     * Indicate that the event is ongoing and can be encoded.
     */
    public function ongoing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventStatus::Ongoing,
        ]);
    }
}
