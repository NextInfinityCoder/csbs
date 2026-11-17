<?php

namespace Database\Factories;

use App\FightResult;
use App\Models\Event;
use App\Models\Fight;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fight>
 */
class FightFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $meronAmount = fake()->numberBetween(5_000, 200_000);
        $walaAmount = fake()->numberBetween(5_000, 200_000);
        $percentage = 7;
        $totalPool = $meronAmount + $walaAmount;
        $commission = round($totalPool * $percentage / 100, 2);
        $betOpen = fake()->dateTimeBetween('-1 month', 'now');

        return [
            'event_id' => Event::factory(),
            'fight_number' => fake()->unique()->numberBetween(1, 1_000_000),
            'meron_amount' => $meronAmount,
            'wala_amount' => $walaAmount,
            'draw_amount' => fake()->numberBetween(0, 5_000),
            'meron_odds' => Fight::calculateOdds($meronAmount, $walaAmount, $percentage),
            'wala_odds' => Fight::calculateOdds($walaAmount, $meronAmount, $percentage),
            'percentage' => $percentage,
            'commission' => $commission,
            'result' => fake()->randomElement([FightResult::Meron, FightResult::Wala, FightResult::Draw]),
            'bet_open' => $betOpen,
            'bet_closed' => (clone $betOpen)->modify('+'.fake()->numberBetween(3, 10).' minutes'),
            'canceled_at' => null,
        ];
    }

    /**
     * Indicate that betting has not started for the fight yet.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'meron_amount' => 0,
            'wala_amount' => 0,
            'draw_amount' => 0,
            'meron_odds' => 0,
            'wala_odds' => 0,
            'commission' => 0,
            'result' => FightResult::Pending,
            'bet_open' => null,
            'bet_closed' => null,
        ]);
    }

    /**
     * Indicate that the fight was cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'commission' => 0,
            'result' => FightResult::Cancelled,
            'canceled_at' => now(),
        ]);
    }
}
