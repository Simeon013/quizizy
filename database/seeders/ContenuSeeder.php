<?php

namespace Database\Seeders;

use App\Models\Matiere;
use App\Models\Quiz;
use App\Models\Reponse;
use Illuminate\Database\Seeder;

/**
 * Le contenu de départ, lu dans database/seeders/contenu/*.php.
 * Peut tourner en production et plusieurs fois : il met à jour par slug, et ne
 * remplace les questions d'un quiz que si aucune partie n'y a encore répondu.
 */
class ContenuSeeder extends Seeder
{
    public function run(): void
    {
        $fichiers = glob(__DIR__.'/contenu/*.php');
        sort($fichiers);

        foreach ($fichiers as $ordre => $fichier) {
            $donnees = require $fichier;

            $matiere = Matiere::updateOrCreate(['slug' => $donnees['slug']], [
                'nom' => $donnees['nom'],
                'description' => $donnees['description'],
                'onglet' => $donnees['onglet'],
                'ordre' => $ordre,
            ]);

            foreach ($donnees['quiz'] as $q) {
                $quiz = Quiz::updateOrCreate(['slug' => $q['slug']], [
                    'matiere_id' => $matiere->id,
                    'titre' => $q['titre'],
                    'description' => $q['description'],
                ]);

                // Supprimer des questions déjà jouées effacerait les réponses des joueurs (cascade).
                $dejaJoue = Reponse::whereIn('question_id', $quiz->questions()->select('id'))->exists();
                if ($dejaJoue) {
                    continue;
                }

                $quiz->questions()->delete();
                foreach ($q['questions'] as $i => [$enonce, $choix, $explication, $indice]) {
                    $question = $quiz->questions()->create([
                        'enonce' => $enonce,
                        'explication' => $explication,
                        'indice' => $indice,
                        'ordre' => $i,
                    ]);
                    foreach ($choix as $j => $texte) {
                        $question->choix()->create(['texte' => $texte, 'juste' => $j === 0, 'ordre' => $j]);
                    }
                }
            }
        }
    }
}
