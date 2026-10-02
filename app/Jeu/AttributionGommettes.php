<?php

namespace App\Jeu;

use App\Models\Gommette;
use App\Models\Partie;
use App\Support\Gommettes;

/**
 * Décerne les gommettes méritées à la fin d'une partie. Chaque gommette ne
 * s'obtient qu'une fois (index unique en base).
 */
class AttributionGommettes
{
    /** @return list<string> clés nouvellement obtenues */
    public static function apres(Partie $partie): array
    {
        if ($partie->user_id === null) {
            return [];
        }

        $reponses = $partie->reponses()->get();
        $total = $partie->total();
        $parties = Partie::where('user_id', $partie->user_id)->whereNotNull('terminee_le');

        $meritees = array_filter([
            'premiere-copie' => true,
            'sans-faute' => $total >= 5 && $partie->bonnes === $total,
            'serie-5' => $partie->serie_max >= 5,
            'eclair' => $reponses->contains(fn ($r) => $r->juste && $r->duree_ms < 3000),
            'debrouillard' => $total >= 5 && ! $reponses->contains('indice', true) && $partie->bonnes / $total >= 0.8,
            'tete-chercheuse' => (clone $parties)->join('quiz', 'quiz.id', '=', 'parties.quiz_id')
                ->distinct()->count('quiz.matiere_id') >= 3,
            'revision' => $partie->type === Partie::TYPE_REVISION && $partie->bonnes > 0,
            'assidu' => SerieDeJours::pour($partie->user_id) >= 3,
        ]);

        $dejaObtenues = Gommette::where('user_id', $partie->user_id)->pluck('cle')->all();
        $nouvelles = array_values(array_diff(array_keys($meritees), $dejaObtenues));

        foreach ($nouvelles as $cle) {
            assert(isset(Gommettes::TOUTES[$cle]));
            Gommette::create([
                'user_id' => $partie->user_id,
                'cle' => $cle,
                'partie_id' => $partie->id,
                'obtenue_le' => now(),
            ]);
        }

        return $nouvelles;
    }
}
