<?php

return [
    // Fuseau des journées (série de jours) : celui du joueur principal.
    'fuseau' => env('EUREKA_FUSEAU', 'Africa/Porto-Novo'),

    // Nombre de questions au plus par partie.
    'questions_par_partie' => 10,

    // Secondes par question en révision (« À revoir »).
    'secondes_revision' => 30,

    // L'atelier : création de quiz assistée par IA.
    'atelier' => [
        // claude | factice (tests, sans appel réseau) | aucun (génération désactivée, écriture à la main seulement)
        'redacteur' => env('EUREKA_REDACTEUR', env('ANTHROPIC_API_KEY') ? 'claude' : 'aucun'),
        'cle_api' => env('ANTHROPIC_API_KEY'),
        'modele' => env('EUREKA_MODELE', 'claude-opus-5-5'),
        // Effort de réflexion : la justesse des faits compte plus que la vitesse.
        'effort' => env('EUREKA_EFFORT', 'high'),
        // Générations par compte et par jour (chaque appel coûte) ; les administrateurs n'ont pas de limite.
        'generations_par_jour' => (int) env('EUREKA_GENERATIONS_PAR_JOUR', 5),
        'source_max' => 12000,
    ],
];
