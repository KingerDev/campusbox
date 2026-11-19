<?php

return [
    // Dvojfaktorové overenie je predvolene dobrovoľné: majiteľovi, manažérovi a správcovi ho panel len odporúča pásom hore.
    // S PANEL_2FA_REQUIRED=true ich bez neho nepustí ďalej a pošle ich na nastavenie.
    'two_factor_required' => (bool) env('PANEL_2FA_REQUIRED', false),

    // Kam okrem správcov (users.is_admin) poslať oznámenie o novej registrácii podniku; viac adries oddeľte čiarkou.
    'notify_email' => env('PANEL_NOTIFY_EMAIL'),

    // Ukážkový podnik: ako dlho kópia žije a koľko ich môže existovať naraz.
    'demo_hours' => (int) env('PANEL_DEMO_HOURS', 24),
    'demo_limit' => (int) env('PANEL_DEMO_LIMIT', 300),
];
