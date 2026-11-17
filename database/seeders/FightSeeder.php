<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Fight;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class FightSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Event::all()->each(function (Event $event): void {
            Fight::factory()
                ->count(fake()->numberBetween(5, 15))
                ->for($event)
                ->state(new Sequence(fn (Sequence $sequence): array => [
                    'fight_number' => $sequence->index + 1,
                    'percentage' => $event->commission_rate,
                ]))
                ->create();
        });
    }
}
