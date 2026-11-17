<?php

namespace JeffersonGoncalves\LgpdMinors\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Account suspended on well-founded signs that a minor uses it outside the rules,
 * with an appeal (Lei 15.211/2025, art. 24, §4).
 *
 * @property string $subject_type
 * @property int|string $subject_id
 * @property string $reason
 * @property string|null $appeal
 * @property Carbon|null $appealed_at
 * @property string|null $outcome lifted|upheld
 * @property string|null $decision
 * @property Carbon|null $resolved_at
 * @property Carbon $created_at
 * @property-read Model|null $subject
 */
class Suspension extends Model
{
    public const LIFTED = 'lifted';

    public const UPHELD = 'upheld';

    protected $guarded = [];

    public function getTable(): string
    {
        return config('lgpd-minors.tables.suspensions', 'lgpd_minor_suspensions');
    }

    protected function casts(): array
    {
        return [
            'appealed_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** Still blocks the account: not resolved, or resolved by upholding it. */
    public function isInForce(): bool
    {
        return $this->outcome !== self::LIFTED;
    }

    public function awaitsDecision(): bool
    {
        return $this->appealed_at !== null && $this->resolved_at === null;
    }

    public function appeal(string $text): static
    {
        if ($this->resolved_at !== null) {
            throw new LogicException('This suspension was already decided.');
        }

        $this->forceFill(['appeal' => $text, 'appealed_at' => now()])->save();

        return $this;
    }

    public function lift(?string $decision = null): static
    {
        return $this->resolve(self::LIFTED, $decision);
    }

    public function uphold(?string $decision = null): static
    {
        return $this->resolve(self::UPHELD, $decision);
    }

    protected function resolve(string $outcome, ?string $decision): static
    {
        $this->forceFill(['outcome' => $outcome, 'decision' => $decision, 'resolved_at' => now()])->save();

        return $this;
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeInForce(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('outcome')->orWhere('outcome', '!=', self::LIFTED));
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeAwaitingDecision(Builder $query): void
    {
        $query->whereNotNull('appealed_at')->whereNull('resolved_at');
    }
}
