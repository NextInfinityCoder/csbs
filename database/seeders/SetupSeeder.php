<?php

namespace Database\Seeders;

use App\Models\Station;
use App\Models\User;
use App\UserType;
use Illuminate\Database\Seeder;

class SetupSeeder extends Seeder
{
    public const int STATION_COUNT = 10;

    public const string DEFAULT_PASSWORD = 'password';

    /**
     * Create the starting admin and encoder accounts, the stations and one cashier per station.
     *
     * Safe to run more than once: existing accounts and stations are kept as they are.
     */
    public function run(): void
    {
        $this->createUser('Administrator', 'admin@csbs.com', UserType::Admin);
        $this->createUser('Encoder', 'encoder@csbs.com', UserType::Encoder);

        foreach (range(1, self::STATION_COUNT) as $number) {
            $station = Station::firstOrCreate(['name' => "Station {$number}"]);

            $this->createUser("Cashier {$number}", "cashier{$number}@example.com", UserType::Cashier, $station);
        }
    }

    /**
     * Create a verified user unless one with the email already exists.
     */
    private function createUser(string $name, string $email, UserType $type, ?Station $station = null): void
    {
        $user = User::firstOrNew(['email' => $email]);

        if ($user->exists) {
            return;
        }

        $user->forceFill([
            'name' => $name,
            'type' => $type,
            'station_id' => $station?->id,
            'password' => self::DEFAULT_PASSWORD,
            'email_verified_at' => now(),
        ])->save();
    }
}
