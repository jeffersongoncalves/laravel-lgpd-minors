<?php

namespace JeffersonGoncalves\LgpdMinors\Enums;

/**
 * Age range kept instead of the birth date (Decreto 12.880/2026, art. 25, §1).
 */
enum AgeBand: string
{
    /** Under 12: a child (ECA, art. 2). */
    case Child = 'child';

    /** 12 to 16: an adolescent whose account must be linked to a guardian (Lei 15.211/2025, art. 24). */
    case YoungAdolescent = 'young_adolescent';

    /** 17. */
    case Adolescent = 'adolescent';

    case Adult = 'adult';

    case Unknown = 'unknown';

    public static function fromAge(int $age): self
    {
        return match (true) {
            $age < 12 => self::Child,
            $age <= 16 => self::YoungAdolescent,
            $age < 18 => self::Adolescent,
            default => self::Adult,
        };
    }

    public function isMinor(): bool
    {
        return in_array($this, [self::Child, self::YoungAdolescent, self::Adolescent], true);
    }

    /** "Até 16 anos", read inclusively (the more protective reading). */
    public function needsGuardian(): bool
    {
        return in_array($this, [self::Child, self::YoungAdolescent], true);
    }

    /** Higher = more protective; used to pick the band when results diverge (Decreto 12.880/2026, art. 25, §4). */
    public function protection(): int
    {
        return match ($this) {
            self::Child => 4,
            self::YoungAdolescent => 3,
            self::Adolescent => 2,
            self::Adult => 1,
            self::Unknown => 0,
        };
    }
}
