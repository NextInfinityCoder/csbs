<?php

namespace App\Models;

use App\UserType;
use Database\Factories\StationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, User> $cashiers
 */
#[Fillable(['name', 'description'])]
class Station extends Model
{
    /** @use HasFactory<StationFactory> */
    use HasFactory;

    /**
     * Get the cashiers assigned to the station.
     *
     * @return HasMany<User, $this>
     */
    public function cashiers(): HasMany
    {
        return $this->hasMany(User::class)->where('type', UserType::Cashier);
    }
}
