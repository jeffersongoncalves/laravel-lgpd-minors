<?php

namespace JeffersonGoncalves\LgpdMinors\Facades;

use Illuminate\Support\Facades\Facade;
use JeffersonGoncalves\LgpdMinors\MinorsManager;

/**
 * @method static \JeffersonGoncalves\LgpdMinors\Models\AgeAssurance assure(\Illuminate\Database\Eloquent\Model $subject, \JeffersonGoncalves\LgpdMinors\Enums\AgeBand|int $band, \JeffersonGoncalves\LgpdMinors\Enums\AssuranceMethod $method, ?string $provider = null)
 * @method static \JeffersonGoncalves\LgpdMinors\Enums\AgeBand band(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool isVerifiedAdult(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool treatAsMinor(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool allowsProfiling(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool needsGuardian(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool canLowerProtection(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool hasGuardian(\Illuminate\Database\Eloquent\Model $minor)
 * @method static \JeffersonGoncalves\LgpdMinors\Models\GuardianLink link(\Illuminate\Database\Eloquent\Model $minor, \Illuminate\Database\Eloquent\Model $guardian, ?string $relationship = null, bool $verified = false)
 * @method static \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model> guardians(\Illuminate\Database\Eloquent\Model $minor)
 * @method static \JeffersonGoncalves\LgpdMinors\Models\Suspension suspend(\Illuminate\Database\Eloquent\Model $subject, string $reason)
 * @method static \JeffersonGoncalves\LgpdMinors\Models\Suspension|null suspension(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static bool isSuspended(?\Illuminate\Database\Eloquent\Model $subject)
 * @method static \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model> missingGuardian()
 *
 * @see MinorsManager
 */
class LgpdMinors extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MinorsManager::class;
    }
}
