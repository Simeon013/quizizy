<?php

namespace App\Jeu;

/**
 * Le barème. Une bonne réponse vaut 100 points, plus un bonus de rapidité
 * jusqu'à 50. Décoller l'indice coûte la moitié des points et le bonus.
 */
class Notation
{
    public const BASE = 100;

    public const BONUS_MAX = 50;

    /** Délai accordé après la fin du chrono, pour la latence du réseau. */
    public const TOLERANCE_MS = 2000;

    public static function points(bool $juste, bool $indice, int $dureeMs, int $secondes): int
    {
        if (! $juste) {
            return 0;
        }

        if ($indice) {
            return intdiv(self::BASE, 2);
        }

        $reste = max(0.0, 1 - $dureeMs / ($secondes * 1000));

        return self::BASE + (int) round(self::BONUS_MAX * $reste);
    }

    /** Note sur 20, au demi-point près. */
    public static function surVingt(int $bonnes, int $total): float
    {
        return $total === 0 ? 0.0 : round($bonnes / $total * 40) / 2;
    }
}
