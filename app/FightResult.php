<?php

namespace App;

enum FightResult: string
{
    case Pending = 'pending';
    case Meron = 'meron';
    case Wala = 'wala';
    case Draw = 'draw';
    case Cancelled = 'cancelled';

    /**
     * Determine if every bet on a fight with this result is refunded, earning no commission.
     */
    public function isRefunded(): bool
    {
        return in_array($this, [self::Draw, self::Cancelled], true);
    }
}
