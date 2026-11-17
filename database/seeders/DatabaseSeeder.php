<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with the starting accounts, stations and sample data.
     */
    public function run(): void
    {
        $this->call([
            SetupSeeder::class,
        ]);
    }
}
