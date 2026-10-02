<?php

namespace App\Atelier;

use Illuminate\Support\Str;

/**
 * Ce qui sort du modèle n'est jamais enregistré tel quel : chaque question est
 * vérifiée, et écartée si elle ne tient pas (choix en double, champ vide…).
 */
class Nettoyage
{
    public const ENONCE_MAX = 300;

    public const CHOIX_MAX = 120;

    public const EXPLICATION_MAX = 600;

    public const INDICE_MAX = 200;

    /**
     * @param  list<string>  $dejaPosees
     * @return list<array{enonce: string, choix: list<string>, explication: string, indice: ?string}>
     */
    public static function questions(array $brutes, array $dejaPosees, int $maximum): array
    {
        $vus = array_map(fn ($e) => self::cle($e), $dejaPosees);
        $retenues = [];

        foreach ($brutes as $brute) {
            $q = is_array($brute) ? self::question($brute) : null;
            if ($q === null || in_array(self::cle($q['enonce']), $vus, true)) {
                continue;
            }
            $vus[] = self::cle($q['enonce']);
            $retenues[] = $q;
            if (count($retenues) >= $maximum) {
                break;
            }
        }

        return $retenues;
    }

    /** @return array{enonce: string, choix: list<string>, explication: string, indice: ?string}|null */
    public static function question(array $brute): ?array
    {
        $enonce = self::texte($brute['enonce'] ?? null, self::ENONCE_MAX);
        $explication = self::texte($brute['explication'] ?? null, self::EXPLICATION_MAX);
        $indice = self::texte($brute['indice'] ?? null, self::INDICE_MAX);
        $choix = array_values(array_filter(array_map(
            fn ($c) => self::texte($c, self::CHOIX_MAX),
            is_array($brute['choix'] ?? null) ? $brute['choix'] : [],
        )));

        // Deux à quatre choix, tous différents (le mode en direct n'en affiche que quatre).
        $distincts = array_unique(array_map(fn ($c) => self::cle($c), $choix));
        if ($enonce === null || $explication === null || count($choix) < 2 || count($choix) > 4 || count($distincts) !== count($choix)) {
            return null;
        }

        return ['enonce' => $enonce, 'choix' => $choix, 'explication' => $explication, 'indice' => $indice];
    }

    private static function texte(mixed $valeur, int $max): ?string
    {
        if (! is_string($valeur)) {
            return null;
        }
        $propre = trim(preg_replace('/\s+/u', ' ', strip_tags($valeur)));

        return $propre === '' || mb_strlen($propre) > $max ? null : $propre;
    }

    private static function cle(string $texte): string
    {
        return Str::of($texte)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', '')->toString();
    }
}
