<?php

namespace App\Models;

use App\FightResult;
use Database\Factories\FightFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_id
 * @property int $fight_number
 * @property string $meron_amount
 * @property string $wala_amount
 * @property string $draw_amount
 * @property string $meron_odds
 * @property string $wala_odds
 * @property string $percentage
 * @property string $commission
 * @property FightResult $result
 * @property Carbon|null $bet_open
 * @property Carbon|null $bet_closed
 * @property Carbon|null $canceled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Event $event
 * @property-read Collection<int, Bet> $bets
 */
#[Fillable([
    'event_id',
    'fight_number',
    'meron_amount',
    'wala_amount',
    'draw_amount',
    'meron_odds',
    'wala_odds',
    'percentage',
    'commission',
    'result',
    'bet_open',
    'bet_closed',
    'canceled_at',
])]
class Fight extends Model
{
    /** @use HasFactory<FightFactory> */
    use HasFactory;

    /**
     * Get the bets placed on the fight.
     *
     * @return HasMany<Bet, $this>
     */
    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class);
    }

    /**
     * Calculate the payout percentage of a side, rounded down to one decimal.
     *
     * A side with 2,000 against 2,000 at a 10% commission pays 180.0%,
     * so a winning bet of 100 claims 180.
     */
    public static function calculateOdds(float $amount, float $againstAmount, float $commissionRate): float
    {
        if ($amount <= 0) {
            return 0.0;
        }

        $totalPercentage = 100;
        $percentage = ($totalPercentage - $commissionRate) / 100;
        $odds = $totalPercentage * ($amount + $againstAmount) * $percentage / $amount;

        return floor(round($odds * 10, 6)) / 10;
    }

    /**
     * Get the event the fight belongs to.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fight_number' => 'integer',
            'meron_amount' => 'decimal:2',
            'wala_amount' => 'decimal:2',
            'draw_amount' => 'decimal:2',
            'meron_odds' => 'decimal:2',
            'wala_odds' => 'decimal:2',
            'percentage' => 'decimal:2',
            'commission' => 'decimal:2',
            'result' => FightResult::class,
            'bet_open' => 'datetime',
            'bet_closed' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }
}
