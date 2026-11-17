<?php

namespace JeffersonGoncalves\LgpdMinors\Enums;

enum AssuranceMethod: string
{
    /** Never enough for adult-only content (Lei 15.211/2025, art. 9, §1). */
    case SelfDeclaration = 'self_declaration';

    /** Age signal from the operating system (Decreto 12.880/2026, art. 26). */
    case OsSignal = 'os_signal';

    /** Age signal from the app store (Decreto 12.880/2026, art. 26). */
    case StoreSignal = 'store_signal';

    case Document = 'document';

    case FacialEstimation = 'facial_estimation';

    case VerifiableCredential = 'verifiable_credential';

    /** Age stated by a linked, verified guardian. */
    case Guardian = 'guardian';

    public function isReliable(): bool
    {
        return in_array($this->value, (array) config('lgpd-minors.reliable_methods', []), true);
    }
}
