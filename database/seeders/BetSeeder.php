<?php

namespace Database\Seeders;

use App\BetSide;
use App\FightResult;
use App\Models\Bet;
use App\Models\Fight;
use App\Models\User;
use Illuminate\Database\Seeder;

class BetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cashiers = User::query()->cashiers()->get();

        if ($cashiers->isEmpty()) {
            return;
        }

        Fight::query()
            ->whereIn('result', [FightResult::Meron, FightResult::Wala, FightResult::Draw])
            ->latest('id')
            ->limit(40)
            ->get()
            ->each(function (Fight $fight) use ($cashiers): void {
                foreach (range(1, fake()->numberBetween(5, 15)) as $ignored) {
                    $cashier = $cashiers->random();
                    $betSide = fake()->randomElement(BetSide::cases());
                    $amount = fake()->randomElement([100, 200, 300, 500, 1_000, 2_000, 5_000]);
                    $isWinningBet = $betSide->value === $fight->result->value;
                    $isClaimed = $isWinningBet && fake()->boolean(70);

                    Bet::factory()->create([
                        'fight_id' => $fight->id,
                        'event_id' => $fight->event_id,
                        'user_id' => $cashier->id,
                        'station_id' => $cashier->station_id,
                        'amount' => $amount,
                        'bet_side' => $betSide,
                        'claim_amount' => $isClaimed ? $this->payoutFor($fight, $betSide, $amount) : null,
                        'claim_from_user_id' => $isClaimed ? $cashiers->random()->id : null,
                        'claimed_at' => $isClaimed ? $fight->bet_closed?->copy()->addMinutes(fake()->numberBetween(5, 60)) : null,
                        'created_at' => $fight->bet_open?->copy()->addSeconds(fake()->numberBetween(0, 180)),
                    ]);
                }
            });
    }

    /**
     * Calculate the sample payout for a winning bet.
     */
    private function payoutFor(Fight $fight, BetSide $betSide, int $amount): float
    {
        return match ($betSide) {
            BetSide::Meron => round($amount * (float) $fight->meron_odds / 100, 2),
            BetSide::Wala => round($amount * (float) $fight->wala_odds / 100, 2),
            BetSide::Draw => $amount * 8,
        };
    }
}
