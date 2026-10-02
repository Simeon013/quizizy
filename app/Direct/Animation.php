<?php

namespace App\Direct;

use App\Jeu\Notation;
use App\Models\Participant;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\ReponseDirect;
use App\Models\Salle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Random\Randomizer;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Le déroulé d'une salle en direct. Comme en solo, le serveur décide de tout ;
 * chaque changement augmente `version`, que les écrans comparent pour savoir
 * s'il faut se mettre à jour.
 */
class Animation
{
    /** Au-delà, l'écran de l'animateur n'a plus la place d'afficher tout le monde. */
    public const JOUEURS_MAX = 60;

    public static function ouvrir(Quiz $quiz, int $animateurId): Salle
    {
        $ids = $quiz->questions()->pluck('id')->all();

        return Salle::create([
            'code' => self::nouveauCode(),
            'quiz_id' => $quiz->id,
            'animateur_id' => $animateurId,
            'questions' => array_slice((new Randomizer)->shuffleArray($ids), 0, (int) config('eureka.questions_par_partie')),
            'secondes_par_question' => $quiz->secondes_par_question,
        ])->refresh();
    }

    /** Six chiffres, uniques parmi les salles ouvertes. */
    private static function nouveauCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
        } while (Salle::ouvertes()->where('code', $code)->exists());

        return $code;
    }

    public static function rejoindre(Salle $salle, string $pseudo): Participant
    {
        $pseudo = trim(preg_replace('/\s+/u', ' ', $pseudo));

        return DB::transaction(function () use ($salle, $pseudo) {
            $salle = Salle::whereKey($salle->id)->lockForUpdate()->firstOrFail();

            if ($salle->presents()->count() >= self::JOUEURS_MAX) {
                throw ValidationException::withMessages(['code' => 'Cette partie est complète.']);
            }
            $pris = $salle->participants()->whereRaw('lower(pseudo) = ?', [mb_strtolower($pseudo)])->exists();
            if ($pris) {
                throw ValidationException::withMessages(['pseudo' => 'Ce pseudo est déjà pris dans cette partie. Choisis-en un autre.']);
            }

            $participant = $salle->participants()->create(['pseudo' => $pseudo]);
            self::changer($salle);

            return $participant;
        });
    }

    /**
     * Ferme la question en cours si son temps est écoulé. Appelée à chaque
     * lecture de l'état : c'est ce qui fait avancer la salle sans tâche planifiée.
     */
    public static function actualiser(Salle $salle): Salle
    {
        if ($salle->etat === Salle::QUESTION && now()->gt($salle->question_fin_le)) {
            return self::clore($salle);
        }

        return $salle;
    }

    /** Lance la question suivante, ou termine après la dernière. */
    public static function suivante(Salle $salle): Salle
    {
        return DB::transaction(function () use ($salle) {
            $salle = Salle::whereKey($salle->id)->lockForUpdate()->firstOrFail();

            if (! in_array($salle->etat, [Salle::ATTENTE, Salle::CORRECTION], true)) {
                throw new ConflictHttpException('La question en cours n\'est pas terminée.');
            }
            if ($salle->etat === Salle::CORRECTION && $salle->estDerniere()) {
                return self::terminer($salle);
            }

            $salle->forceFill([
                'etat' => Salle::QUESTION,
                'position' => $salle->position + 1,
                'question_debut_le' => now(),
                'question_fin_le' => now()->addSeconds($salle->secondes_par_question),
            ]);
            self::changer($salle);

            return $salle;
        });
    }

    /** Fin de la question : maintenant (bouton « Corriger ») ou quand le temps est écoulé. */
    public static function clore(Salle $salle): Salle
    {
        return DB::transaction(function () use ($salle) {
            $salle = Salle::whereKey($salle->id)->lockForUpdate()->firstOrFail();
            if ($salle->etat !== Salle::QUESTION) {
                return $salle;
            }

            // Ceux qui n'ont pas répondu perdent leur série.
            $ontRepondu = ReponseDirect::where('salle_id', $salle->id)
                ->where('question_id', $salle->questionEnCoursId())->pluck('participant_id');
            $salle->participants()->whereNotIn('id', $ontRepondu)->update(['serie' => 0]);

            $salle->forceFill(['etat' => Salle::CORRECTION, 'question_fin_le' => min(now(), $salle->question_fin_le)]);
            self::changer($salle);

            return $salle;
        });
    }

    public static function terminer(Salle $salle): Salle
    {
        $salle->forceFill(['etat' => Salle::TERMINEE, 'terminee_le' => now()]);
        self::changer($salle);

        return $salle;
    }

    public static function retirer(Salle $salle, Participant $participant): void
    {
        abort_unless($participant->salle_id === $salle->id, 404);
        $participant->forceFill(['retire_le' => now()])->save();
        self::changer($salle);
    }

    /** @return array{enregistre: bool} */
    public static function repondre(Salle $salle, Participant $participant, int $questionId, int $choixId): array
    {
        return DB::transaction(function () use ($salle, $participant, $questionId, $choixId) {
            $salle = Salle::whereKey($salle->id)->lockForUpdate()->firstOrFail();
            $participant->refresh();

            $limite = $salle->question_fin_le?->copy()->addMilliseconds(Notation::TOLERANCE_MS);
            if ($salle->etat !== Salle::QUESTION || $salle->questionEnCoursId() !== $questionId || now()->gt($limite)) {
                throw new ConflictHttpException('Trop tard pour cette question.');
            }
            if ($participant->estRetire()) {
                throw new ConflictHttpException('Tu ne fais plus partie de cette partie.');
            }
            if (ReponseDirect::where('participant_id', $participant->id)->where('question_id', $questionId)->exists()) {
                throw new ConflictHttpException('Tu as déjà répondu.');
            }

            $choix = Question::findOrFail($questionId)->choix()->whereKey($choixId)->first();
            if (! $choix) {
                throw ValidationException::withMessages(['choix_id' => 'Ce choix n\'appartient pas à la question.']);
            }

            $dureeMs = (int) $salle->question_debut_le->diffInMilliseconds(now(), true);
            $points = Notation::points($choix->juste, false, $dureeMs, $salle->secondes_par_question);

            ReponseDirect::create([
                'salle_id' => $salle->id,
                'participant_id' => $participant->id,
                'question_id' => $questionId,
                'choix_id' => $choix->id,
                'juste' => $choix->juste,
                'points' => $points,
                'duree_ms' => $dureeMs,
            ]);
            $participant->forceFill([
                'points' => $participant->points + $points,
                'bonnes' => $participant->bonnes + ($choix->juste ? 1 : 0),
                'serie' => $choix->juste ? $participant->serie + 1 : 0,
            ])->save();
            self::changer($salle);

            // Tout le monde a répondu : inutile d'attendre la fin du crayon.
            $reponses = ReponseDirect::where('salle_id', $salle->id)->where('question_id', $questionId)->count();
            if ($reponses >= $salle->presents()->count()) {
                self::clore($salle);
            }

            return ['enregistre' => true];
        });
    }

    private static function changer(Salle $salle): void
    {
        $salle->version++;
        $salle->save();
    }
}
