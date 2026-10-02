<?php

namespace App\Jeu;

use App\Models\Matiere;
use App\Models\Partie;
use App\Support\Onglets;

/**
 * Le bulletin : moyenne sur 20 par matière, calculée sur les parties terminées.
 * L'appréciation est écrite au stylo vert : elle encourage toujours.
 */
class Bulletin
{
    /** @return list<array<string, mixed>> */
    public static function pour(int $userId): array
    {
        $parties = Partie::where('user_id', $userId)
            ->where('type', Partie::TYPE_QUIZ)
            ->whereNotNull('terminee_le')
            ->with('quiz:id,matiere_id')
            ->get(['id', 'quiz_id', 'questions', 'bonnes']);

        $parMatiere = $parties->filter(fn (Partie $p) => $p->quiz !== null)->groupBy(fn (Partie $p) => $p->quiz->matiere_id);

        return Matiere::orderBy('ordre')->get()
            ->map(function (Matiere $matiere) use ($parMatiere) {
                $siennes = $parMatiere->get($matiere->id, collect());
                $moyenne = $siennes->isEmpty() ? null
                    : round($siennes->avg(fn (Partie $p) => Notation::surVingt($p->bonnes, $p->total())) * 2) / 2;

                return [
                    'matiere' => $matiere->nom,
                    'slug' => $matiere->slug,
                    'onglet' => Onglets::couleur($matiere->onglet),
                    'copies' => $siennes->count(),
                    'moyenne' => $moyenne,
                ];
            })
            ->values()
            ->all();
    }

    /** @param list<array<string, mixed>> $lignes */
    public static function appreciation(array $lignes): string
    {
        $notees = array_values(array_filter($lignes, fn ($l) => $l['moyenne'] !== null));

        if ($notees === []) {
            return 'Le bulletin se remplit dès la première copie. À toi de jouer !';
        }

        usort($notees, fn ($a, $b) => $b['moyenne'] <=> $a['moyenne']);
        $meilleure = $notees[0];
        $moinsBonne = $notees[count($notees) - 1];
        $generale = array_sum(array_column($notees, 'moyenne')) / count($notees);

        $debut = match (true) {
            $generale >= 16 => 'Excellent travail',
            $generale >= 12 => 'Du bon travail',
            $generale >= 8 => 'Des efforts qui paient',
            default => 'Chaque copie te fait progresser',
        };

        if (count($notees) === 1 || $meilleure['moyenne'] - $moinsBonne['moyenne'] < 2) {
            return "{$debut}, régulier dans toutes les matières.";
        }

        return "{$debut}, surtout en {$meilleure['matiere']}. Ose un peu plus la matière « {$moinsBonne['matiere']} » !";
    }
}
