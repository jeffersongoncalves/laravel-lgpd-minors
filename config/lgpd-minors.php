<?php

return [

    'tables' => [
        'assurances' => 'lgpd_age_assurances',
        'guardians' => 'lgpd_guardian_links',
        'suspensions' => 'lgpd_minor_suspensions',
    ],

    /*
     * Methods that count as reliable age verification (Lei 15.211/2025, art. 9, §1).
     * Self-declaration is never reliable; leave it out.
     */
    'reliable_methods' => [
        'os_signal',
        'store_signal',
        'document',
        'facial_estimation',
        'verifiable_credential',
    ],

    /*
     * How long a result stays valid, per method (CarbonInterval string, null = forever).
     * Expired results are ignored, so the user is asked again as they grow up.
     */
    'valid_for' => [
        'self_declaration' => '1 year',
        'os_signal' => '30 days',
        'store_signal' => '30 days',
        'document' => '1 year',
        'facial_estimation' => '1 year',
        'verifiable_credential' => '1 year',
        'guardian' => '1 year',
    ],

    /*
     * The product is directed to, or likely accessed by, children and adolescents
     * (art. 1). Users with no age result are then treated as minors where it matters:
     * they cannot lower the protective defaults without a guardian.
     */
    'likely_access' => true,

    /*
     * Where `lgpd.adult` sends users without a reliable adult result. Null = 403.
     */
    'verify_route' => null,

];
