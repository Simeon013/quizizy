<?php

namespace App\Support;

/**
 * Les couleurs d'intercalaires. Liste fermée : une matière choisit une clé,
 * jamais une couleur libre (le texte posé dessus reste l'encre bleue foncée,
 * contrôlée pour le contraste sur chacune).
 */
class Onglets
{
    public const TOUS = [
        'ciel' => '#8CC8FF',
        'corail' => '#FFB4A2',
        'menthe' => '#B9F5C9',
        'citron' => '#FFE27A',
        'lilas' => '#E4C8FF',
        'peche' => '#FFCF9E',
    ];

    public static function couleur(string $cle): string
    {
        return self::TOUS[$cle] ?? self::TOUS['ciel'];
    }
}
