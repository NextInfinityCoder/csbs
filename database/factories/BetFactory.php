<?php

namespace Database\Factories;

use App\BetSide;
use App\Models\Bet;
use App\Models\Fight;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bet>
 */
class BetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fight_id' => Fight::factory(),
            'event_id' => fn (array $attributes): int => Fight::findOrFail($attributes['fight_id'])->event_id,
            'user_id' => User::factory()->cashier(),
            'station_id' => fn (array $attributes): ?int => User::findOrFail($attributes['user_id'])->station_id,
            'amount' => fake()->randomElement([100, 200, 300, 500, 1_000, 2_000, 5_000]),
            'bet_side' => fake()->randomElement(BetSide::cases()),
            'claim_amount' => null,
            'claim_from_user_id' => null,
            'claimed_at' => null,
        ];
    }

    /**
     * Indicate that the bet has been claimed.
     */
    public function claimed(?User $claimedFrom = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'claim_amount' => round($attributes['amount'] * fake()->randomFloat(2, 1.5, 2.2), 2),
            'claim_from_user_id' => $claimedFrom->id ?? $attributes['user_id'],
            'claimed_at' => now(),
        ]);
    }
}
