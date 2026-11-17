<?php

namespace App\Models;

use App\BetSide;
use Database\Factories\BetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $barcode
 * @property int $event_id
 * @property int $fight_id
 * @property int $user_id
 * @property int|null $station_id
 * @property int|null $station_sequence
 * @property string $amount
 * @property BetSide $bet_side
 * @property string|null $claim_amount
 * @property int|null $claim_from_user_id
 * @property Carbon|null $claimed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Event $event
 * @property-read Fight $fight
 * @property-read User $user
 * @property-read Station|null $station
 * @property-read User|null $claimedFrom
 */
#[Fillable([
    'barcode',
    'event_id',
    'fight_id',
    'user_id',
    'station_id',
    'station_sequence',
    'amount',
    'bet_side',
    'claim_amount',
    'claim_from_user_id',
    'claimed_at',
])]
class Bet extends Model
{
    /** @use HasFactory<BetFactory> */
    use HasFactory;

    public const int BARCODE_MIN = 100_000_000_000;

    public const int BARCODE_MAX = 999_999_999_999;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::creating(function (Bet $bet): void {
            $bet->barcode ??= static::generateUniqueBarcode();

            if ($bet->station_id !== null && $bet->station_sequence === null) {
                $bet->station_sequence = static::nextStationSequence($bet->station_id);
            }
        });
    }

    /**
     * Generate a random 12-digit barcode that is not used by any bet yet.
     */
    public static function generateUniqueBarcode(): string
    {
        do {
            $barcode = (string) random_int(self::BARCODE_MIN, self::BARCODE_MAX);
        } while (static::query()->where('barcode', $barcode)->exists());

        return $barcode;
    }

    /**
     * Generate the given number of random 12-digit barcodes that no bet uses yet.
     *
     * @return list<string>
     */
    public static function generateUniqueBarcodes(int $count): array
    {
        $barcodes = [];

        while (count($barcodes) < $count) {
            $candidates = [];

            while (count($barcodes) + count($candidates) < $count) {
                $candidate = (string) random_int(self::BARCODE_MIN, self::BARCODE_MAX);

                if (! isset($barcodes[$candidate])) {
                    $candidates[$candidate] = true;
                }
            }

            $usedBarcodes = static::query()
                ->whereIn('barcode', array_map('strval', array_keys($candidates)))
                ->pluck('barcode')
                ->all();

            $barcodes += array_diff_key($candidates, array_flip($usedBarcodes));
        }

        return array_map('strval', array_keys($barcodes));
    }

    /**
     * Lock the given stations and get the latest transaction number of each.
     *
     * Must run inside a database transaction so concurrent bets at the same
     * stations wait until the new numbers are saved.
     *
     * @param  list<int>  $stationIds
     * @return array<int, int>
     */
    public static function lockLatestStationSequences(array $stationIds): array
    {
        if ($stationIds === []) {
            return [];
        }

        Station::query()->whereKey($stationIds)->lockForUpdate()->get(['id']);

        $latestSequences = static::query()
            ->whereIn('station_id', $stationIds)
            ->groupBy('station_id')
            ->selectRaw('station_id, MAX(station_sequence) AS latest_sequence')
            ->toBase()
            ->pluck('latest_sequence', 'station_id');

        return collect($stationIds)
            ->mapWithKeys(fn (int $stationId): array => [$stationId => (int) ($latestSequences[$stationId] ?? 0)])
            ->all();
    }

    /**
     * Get the next transaction number for a station.
     *
     * Bets should be created inside a database transaction so concurrent
     * bets at the same station wait for each other; the unique index on
     * (station_id, station_sequence) guards against duplicates either way.
     */
    public static function nextStationSequence(int $stationId): int
    {
        Station::query()->whereKey($stationId)->lockForUpdate()->first();

        return (int) static::query()->where('station_id', $stationId)->max('station_sequence') + 1;
    }

    /**
     * Get the event the bet was placed in.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the fight the bet was placed on.
     *
     * @return BelongsTo<Fight, $this>
     */
    public function fight(): BelongsTo
    {
        return $this->belongsTo(Fight::class);
    }

    /**
     * Get the cashier who accepted the bet.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the station where the bet was placed.
     *
     * @return BelongsTo<Station, $this>
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /**
     * Get the cashier who paid out the claim.
     *
     * @return BelongsTo<User, $this>
     */
    public function claimedFrom(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claim_from_user_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'station_sequence' => 'integer',
            'amount' => 'decimal:2',
            'bet_side' => BetSide::class,
            'claim_amount' => 'decimal:2',
            'claimed_at' => 'datetime',
        ];
    }
}
