<?php

return [
    // Fuseau des journées (série de jours) : celui du joueur principal.
    'fuseau' => env('EUREKA_FUSEAU', 'Africa/Porto-Novo'),

    // Nombre de questions au plus par partie.
    'questions_par_partie' => 10,

    // Secondes par question en révision (« À revoir »).
    'secondes_revision' => 30,
];
