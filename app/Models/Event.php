<?php

namespace App\Models;

use App\EventStatus;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $commission_rate
 * @property string $commission_amount
 * @property string $multiplier
 * @property EventStatus $status
 * @property Carbon $date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Fight> $fights
 * @property-read Collection<int, Bet> $bets
 */
#[Fillable(['name', 'commission_rate', 'commission_amount', 'multiplier', 'status', 'date'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * Get the bets placed in the event.
     *
     * @return HasMany<Bet, $this>
     */
    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class);
    }

    /**
     * Get the fights for the event.
     *
     * @return HasMany<Fight, $this>
     */
    public function fights(): HasMany
    {
        return $this->hasMany(Fight::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'multiplier' => 'decimal:2',
            'status' => EventStatus::class,
            'date' => 'datetime',
        ];
    }
}
