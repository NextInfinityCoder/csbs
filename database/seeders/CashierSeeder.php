<?php

namespace Database\Seeders;

use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Seeder;

class CashierSeeder extends Seeder
{
    /**
     * Create sample cashiers: two for each existing station plus a few without a station.
     */
    public function run(): void
    {
        Station::all()->each(function (Station $station): void {
            User::factory()->count(2)->cashier($station)->create();
        });

        User::factory()->count(3)->cashier()->create();
    }
}
