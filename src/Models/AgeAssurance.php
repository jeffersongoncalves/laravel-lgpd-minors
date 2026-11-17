<?php

namespace JeffersonGoncalves\LgpdMinors\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\LgpdMinors\Enums\AgeBand;
use JeffersonGoncalves\LgpdMinors\Enums\AssuranceMethod;

/**
 * The result of one age check. Only the result: never the document, selfie or birth date
 * used to reach it (Lei 15.211/2025, art. 13).
 *
 * @property string $subject_type
 * @property int|string $subject_id
 * @property AgeBand $band
 * @property AssuranceMethod $method
 * @property string|null $provider
 * @property Carbon $assured_at
 * @property Carbon|null $expires_at
 * @property-read Model|null $subject
 */
class AgeAssurance extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('lgpd-minors.tables.assurances', 'lgpd_age_assurances');
    }

    protected function casts(): array
    {
        return [
            'band' => AgeBand::class,
            'method' => AssuranceMethod::class,
            'assured_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function isValid(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeValid(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
