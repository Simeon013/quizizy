<?php

namespace App\Support;

/**
 * Le niveau écrit sur l'étiquette du cahier, selon les points cumulés.
 */
class Niveaux
{
    public const PALIERS = [
        0 => 'Page blanche',
        500 => 'Esprit curieux',
        2000 => 'Esprit vif',
        5000 => 'Tête bien faite',
        10000 => 'Puits de science',
        25000 => 'Encyclopédie vivante',
    ];

    /** @return array{nom: string, prochain: ?int} */
    public static function pour(int $points): array
    {
        $nom = self::PALIERS[0];
        $prochain = null;
        foreach (self::PALIERS as $seuil => $libelle) {
            if ($points >= $seuil) {
                $nom = $libelle;
            } else {
                $prochain = $seuil;
                break;
            }
        }

        return ['nom' => $nom, 'prochain' => $prochain];
    }
}
