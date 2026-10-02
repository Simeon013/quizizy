<?php

namespace App\Jeu;

use App\Models\Partie;
use Illuminate\Support\Carbon;

/**
 * Nombre de jours d'affilée avec au moins une partie terminée, jusqu'à
 * aujourd'hui (ou hier : la série n'est pas perdue avant la fin de la journée).
 */
class SerieDeJours
{
    public static function pour(int $userId): int
    {
        $fuseau = config('eureka.fuseau');

        $jours = Partie::where('user_id', $userId)
            ->whereNotNull('terminee_le')
            ->where('terminee_le', '>=', now()->subDays(400))
            ->pluck('terminee_le')
            ->map(fn (Carbon $date) => $date->copy()->setTimezone($fuseau)->toDateString())
            ->unique()
            ->flip();

        $jour = now($fuseau)->startOfDay();
        if (! isset($jours[$jour->toDateString()])) {
            $jour->subDay();
        }

        $serie = 0;
        while (isset($jours[$jour->toDateString()])) {
            $serie++;
            $jour->subDay();
        }

        return $serie;
    }
}
