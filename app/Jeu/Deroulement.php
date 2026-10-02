<?php

namespace App\Jeu;

use App\Models\Choix;
use App\Models\Partie;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Reponse;
use App\Support\Onglets;
use Illuminate\Support\Facades\DB;
use Random\Randomizer;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Le déroulement d'une partie, entièrement décidé par le serveur :
 * tirage des questions, affichage (début du chrono), indice, réponse, fin.
 */
class Deroulement
{
    public static function demarrerQuiz(Quiz $quiz, ?int $userId): Partie
    {
        $ids = $quiz->questions()->pluck('id')->all();

        return self::creer($ids, $userId, $quiz->id, Partie::TYPE_QUIZ, $quiz->secondes_par_question);
    }

    public static function demarrerRevision(int $userId): ?Partie
    {
        $ids = Revision::questionIds($userId);

        return $ids === [] ? null
            : self::creer($ids, $userId, null, Partie::TYPE_REVISION, (int) config('eureka.secondes_revision'));
    }

    /** @param list<int> $ids */
    private static function creer(array $ids, ?int $userId, ?int $quizId, string $type, int $secondes): Partie
    {
        $tirees = array_slice((new Randomizer)->shuffleArray($ids), 0, (int) config('eureka.questions_par_partie'));

        return Partie::create([
            'user_id' => $userId,
            'quiz_id' => $quizId,
            'type' => $type,
            'questions' => $tirees,
            'secondes_par_question' => $secondes,
        ])->refresh();
    }

    /**
     * Affiche la question en cours : c'est ici que le chrono démarre. Recharger
     * la page ne le remet pas à zéro.
     *
     * @return array<string, mixed>|null nul quand la partie est terminée
     */
    public static function questionEnCours(Partie $partie): ?array
    {
        $id = $partie->questionEnCoursId();
        if ($id === null) {
            return null;
        }

        if ($partie->question_affichee_le === null) {
            $partie->forceFill(['question_affichee_le' => now()])->save();
        }

        $question = Question::with(['choix', 'quiz.matiere'])->findOrFail($id);
        $ecouleMs = (int) $partie->question_affichee_le->diffInMilliseconds(now(), true);

        return [
            'id' => $question->id,
            'numero' => $partie->position + 1,
            'total' => $partie->total(),
            'enonce' => $question->enonce,
            'matiere' => $question->quiz->matiere->nom,
            'onglet' => Onglets::couleur($question->quiz->matiere->onglet),
            // Ordre des choix mélangé, mais stable pour une partie donnée : recharger ne le change pas.
            // Jamais le champ `juste` ici.
            'choix' => Melange::stable($question->choix, $partie->id.$question->id)
                ->map(fn (Choix $c) => ['id' => $c->id, 'texte' => $c->texte])->values()->all(),
            'aUnIndice' => $question->indice !== null,
            'indice' => $partie->indice_pris ? $question->indice : null,
            'secondes' => $partie->secondes_par_question,
            'restantMs' => max(0, $partie->secondes_par_question * 1000 - $ecouleMs),
        ];
    }

    public static function indice(Partie $partie): ?string
    {
        $id = $partie->questionEnCoursId();
        if ($id === null || $partie->question_affichee_le === null) {
            throw new ConflictHttpException('Aucune question en cours.');
        }

        $indice = Question::whereKey($id)->value('indice');
        if ($indice !== null && ! $partie->indice_pris) {
            $partie->forceFill(['indice_pris' => true])->save();
        }

        return $indice;
    }

    /**
     * Enregistre la réponse à la question en cours et rend la correction.
     * Choix nul, ou réponse arrivée après le chrono : « temps écoulé ».
     *
     * @return array<string, mixed>
     */
    public static function repondre(Partie $partie, int $questionId, ?int $choixId): array
    {
        return DB::transaction(function () use ($partie, $questionId, $choixId) {
            /** @var Partie $partie */
            $partie = Partie::whereKey($partie->id)->lockForUpdate()->firstOrFail();
            $id = $partie->questionEnCoursId();

            // Double clic, ou deux onglets : la question a déjà reçu sa réponse.
            if ($id === null || $id !== $questionId || $partie->question_affichee_le === null) {
                throw new ConflictHttpException('Cette question a déjà reçu une réponse.');
            }

            $question = Question::with('choix')->findOrFail($id);
            $choix = $choixId === null ? null : $question->choix->firstWhere('id', $choixId);
            if ($choixId !== null && $choix === null) {
                throw new UnprocessableEntityHttpException('Ce choix n\'appartient pas à la question.');
            }

            $dureeMs = (int) $partie->question_affichee_le->diffInMilliseconds(now(), true);
            $tempsEcoule = $dureeMs > $partie->secondes_par_question * 1000 + Notation::TOLERANCE_MS;
            if ($tempsEcoule) {
                $choix = null;
            }

            $juste = (bool) $choix?->juste;
            $indice = $partie->indice_pris;
            $points = Notation::points($juste, $indice, $dureeMs, $partie->secondes_par_question);

            Reponse::create([
                'partie_id' => $partie->id,
                'question_id' => $question->id,
                'choix_id' => $choix?->id,
                'juste' => $juste,
                'indice' => $indice,
                'points' => $points,
                'duree_ms' => min($dureeMs, 4_000_000_000),
            ]);

            $serie = $juste ? $partie->serie + 1 : 0;
            $position = $partie->position + 1;
            $termine = $position >= $partie->total();

            $partie->forceFill([
                'position' => $position,
                'question_affichee_le' => null,
                'indice_pris' => false,
                'points' => $partie->points + $points,
                'bonnes' => $partie->bonnes + ($juste ? 1 : 0),
                'serie' => $serie,
                'serie_max' => max($partie->serie_max, $serie),
                'terminee_le' => $termine ? now() : null,
            ])->save();

            if ($termine) {
                AttributionGommettes::apres($partie);
            }

            return [
                'juste' => $juste,
                'choixId' => $choix?->id,
                'choixJusteId' => $question->choix->firstWhere('juste', true)?->id,
                'tempsEcoule' => $tempsEcoule || $choixId === null,
                'explication' => $question->explication,
                'points' => $points,
                'indice' => $indice,
                'totalPoints' => $partie->points,
                'serie' => $serie,
                'termine' => $termine,
            ];
        });
    }
}
