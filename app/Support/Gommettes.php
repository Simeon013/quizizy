<?php

namespace App\Support;

/**
 * Le catalogue des gommettes (les badges). Les clés sont stockées en base :
 * ne jamais en renommer une, en ajouter une nouvelle.
 */
class Gommettes
{
    public const TOUTES = [
        'premiere-copie' => ['nom' => 'Première copie', 'description' => 'Terminer une première partie.', 'symbole' => 'plume', 'couleur' => '#FFE27A'],
        'sans-faute' => ['nom' => 'Sans faute', 'description' => 'Tout juste sur une partie d\'au moins 5 questions.', 'symbole' => 'etoile', 'couleur' => '#F2B705'],
        'serie-5' => ['nom' => 'Sur sa lancée', 'description' => '5 bonnes réponses d\'affilée.', 'symbole' => 'cinq', 'couleur' => '#8CC8FF'],
        'eclair' => ['nom' => 'Éclair', 'description' => 'Une bonne réponse en moins de 3 secondes.', 'symbole' => 'eclair', 'couleur' => '#FFB4A2'],
        'debrouillard' => ['nom' => 'Débrouillard', 'description' => 'Au moins 8 sur 10 sans décoller un seul indice.', 'symbole' => 'coche', 'couleur' => '#B9F5C9'],
        'tete-chercheuse' => ['nom' => 'Tête chercheuse', 'description' => 'Avoir joué dans 3 matières différentes.', 'symbole' => 'boussole', 'couleur' => '#E4C8FF'],
        'revision' => ['nom' => 'Leçon apprise', 'description' => 'Réussir une question « à revoir ».', 'symbole' => 'boucle', 'couleur' => '#FFCF9E'],
        'assidu' => ['nom' => 'Assidu', 'description' => 'Jouer 3 jours d\'affilée.', 'symbole' => 'calendrier', 'couleur' => '#8CFFB9'],
    ];

    /** @return array<string, mixed> */
    public static function fiche(string $cle): array
    {
        return ['cle' => $cle, ...self::TOUTES[$cle]];
    }
}
