<?php

namespace JeffersonGoncalves\LgpdMinors;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use JeffersonGoncalves\LgpdMinors\Enums\AgeBand;
use JeffersonGoncalves\LgpdMinors\Enums\AssuranceMethod;
use JeffersonGoncalves\LgpdMinors\Models\AgeAssurance;
use JeffersonGoncalves\LgpdMinors\Models\GuardianLink;
use JeffersonGoncalves\LgpdMinors\Models\Suspension;

class MinorsManager
{
    /**
     * Record the result of an age check. Pass the age or the band, never the document.
     */
    public function assure(Model $subject, AgeBand|int $band, AssuranceMethod $method, ?string $provider = null): AgeAssurance
    {
        $validFor = config('lgpd-minors.valid_for.'.$method->value);

        return AgeAssurance::create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'band' => is_int($band) ? AgeBand::fromAge($band) : $band,
            'method' => $method,
            'provider' => $provider,
            'assured_at' => now(),
            'expires_at' => $validFor === null ? null : now()->add(CarbonInterval::make((string) $validFor)),
        ]);
    }

    /**
     * Current band. Reliable results outweigh self-declaration; among the results that
     * count, the most protective wins (Decreto 12.880/2026, art. 25, §4).
     */
    public function band(?Model $subject): AgeBand
    {
        return $this->decide($subject)['band'];
    }

    /** Reliable adult result and nothing reliable saying otherwise (Lei 15.211/2025, art. 9, §1). */
    public function isVerifiedAdult(?Model $subject): bool
    {
        $decision = $this->decide($subject);

        return $decision['reliable'] && $decision['band'] === AgeBand::Adult;
    }

    /** A known minor, or nobody knows and the product is likely accessed by minors. */
    public function treatAsMinor(?Model $subject): bool
    {
        $band = $this->band($subject);

        return $band->isMinor() || ($band === AgeBand::Unknown && (bool) config('lgpd-minors.likely_access', true));
    }

    /** Behavioural profiling for ads is banned for minors (arts. 22 and 26): only verified adults. */
    public function allowsProfiling(?Model $subject): bool
    {
        return $this->isVerifiedAdult($subject);
    }

    /** Up to 16 and no active guardian link (art. 24). */
    public function needsGuardian(?Model $subject): bool
    {
        return $subject !== null && $this->band($subject)->needsGuardian() && ! $this->hasGuardian($subject);
    }

    /** Without a linked guardian, a minor's protective defaults cannot be lowered (art. 24, §5). */
    public function canLowerProtection(?Model $subject): bool
    {
        return $subject !== null && (! $this->treatAsMinor($subject) || $this->hasGuardian($subject));
    }

    public function hasGuardian(Model $minor): bool
    {
        return $this->links($minor)->scopes('active')->exists();
    }

    public function link(Model $minor, Model $guardian, ?string $relationship = null, bool $verified = false): GuardianLink
    {
        return GuardianLink::create([
            'minor_type' => $minor->getMorphClass(),
            'minor_id' => $minor->getKey(),
            'guardian_type' => $guardian->getMorphClass(),
            'guardian_id' => $guardian->getKey(),
            'relationship' => $relationship,
            'verified_at' => $verified ? now() : null,
        ]);
    }

    /**
     * Active guardians of a minor.
     *
     * @return Collection<int, Model>
     */
    public function guardians(Model $minor): Collection
    {
        return $this->links($minor)->scopes('active')->with('guardian')->get()
            ->map(fn (GuardianLink $link): ?Model => $link->guardian)
            ->filter()
            ->values();
    }

    public function suspend(Model $subject, string $reason): Suspension
    {
        return Suspension::create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'reason' => $reason,
        ]);
    }

    public function suspension(?Model $subject): ?Suspension
    {
        if ($subject === null) {
            return null;
        }

        return Suspension::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->scopes('inForce')
            ->latest('id')
            ->first();
    }

    public function isSuspended(?Model $subject): bool
    {
        return $this->suspension($subject) !== null;
    }

    /**
     * Subjects up to 16 with no active guardian link.
     *
     * @return Collection<int, Model>
     */
    public function missingGuardian(): Collection
    {
        // ponytail: loads every subject with a valid result; paginate by subject if this grows large
        return AgeAssurance::query()->scopes('valid')->with('subject')->get()
            ->map(fn (AgeAssurance $assurance): ?Model => $assurance->subject)
            ->filter()
            ->unique(fn (Model $subject): string => $subject->getMorphClass().'#'.$subject->getKey())
            ->filter(fn (Model $subject): bool => $this->needsGuardian($subject))
            ->values();
    }

    /**
     * @return array{band: AgeBand, reliable: bool}
     */
    protected function decide(?Model $subject): array
    {
        if ($subject === null) {
            return ['band' => AgeBand::Unknown, 'reliable' => false];
        }

        $valid = AgeAssurance::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->scopes('valid')
            ->get();

        $reliable = $valid->filter(fn (AgeAssurance $a): bool => $a->method->isReliable());
        $counted = $reliable->isNotEmpty() ? $reliable : $valid;

        /** @var AgeAssurance|null $winner */
        $winner = $counted->sortByDesc(fn (AgeAssurance $a): int => $a->band->protection())->first();

        return ['band' => $winner->band ?? AgeBand::Unknown, 'reliable' => $reliable->isNotEmpty()];
    }

    /**
     * @return Builder<GuardianLink>
     */
    protected function links(Model $minor): Builder
    {
        return GuardianLink::query()
            ->where('minor_type', $minor->getMorphClass())
            ->where('minor_id', $minor->getKey());
    }
}
