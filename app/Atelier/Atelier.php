<?php

namespace App\Atelier;

use App\Models\Generation;
use App\Models\Matiere;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * L'atelier : un auteur crée un quiz, l'IA propose des questions « au crayon »,
 * l'auteur les relit et les passe « à l'encre ». Rien n'est publié sans relecture.
 */
class Atelier
{
    public const QUESTIONS_MIN_POUR_PUBLIER = 5;

    public const QUESTIONS_MAX = 30;

    public static function redacteur(): ?Redacteur
    {
        $config = config('eureka.atelier');

        return match ($config['redacteur']) {
            'claude' => $config['cle_api'] ? new RedacteurClaude($config['cle_api'], $config['modele'], $config['effort']) : null,
            'factice' => new RedacteurFactice,
            default => null,
        };
    }

    /** Générations restantes aujourd'hui ; nul = sans limite. */
    public static function restantes(User $user): ?int
    {
        if ($user->is_admin) {
            return null;
        }
        $faites = Generation::where('user_id', $user->id)
            // Début de la journée locale, ramené en UTC comme les dates en base : sans
            // cette conversion, le quota sautait chaque nuit pendant une heure.
            ->where('created_at', '>=', now(config('eureka.fuseau'))->startOfDay()->utc())
            ->where('statut', '!=', 'echouee')
            ->count();

        return max(0, (int) config('eureka.atelier.generations_par_jour') - $faites);
    }

    public static function creerQuiz(User $user, string $titre, Matiere $matiere, ?string $description): Quiz
    {
        return Quiz::create([
            'matiere_id' => $matiere->id,
            'auteur_id' => $user->id,
            'titre' => $titre,
            'slug' => Str::slug(Str::limit($titre, 80, '')).'-'.Str::lower(Str::random(6)),
            'description' => $description,
            'publie' => false,
        ]);
    }

    /**
     * Demande des questions au rédacteur et les range au crayon à la fin du quiz.
     *
     * @return int nombre de questions ajoutées
     *
     * @throws ErreurRedaction
     */
    public static function proposer(Quiz $quiz, User $user, ?string $source, ?string $theme, int $nombre): int
    {
        $redacteur = self::redacteur();
        if (! $redacteur) {
            throw new ErreurRedaction('La génération n\'est pas disponible. Tu peux écrire les questions à la main.');
        }
        if (self::restantes($user) === 0) {
            throw new ErreurRedaction('Tu as utilisé toutes tes propositions pour aujourd\'hui. Reviens demain, ou écris tes questions à la main.', 'quota');
        }

        $existantes = $quiz->questions()->pluck('enonce')->all();
        $nombre = min($nombre, self::QUESTIONS_MAX - count($existantes));
        if ($nombre < 1) {
            throw new ErreurRedaction('Ce quiz a déjà le nombre maximum de questions.');
        }

        $quiz->loadMissing('matiere');
        $demande = new Demande($quiz->titre, $quiz->matiere->nom, $nombre, $source, $theme, $existantes);

        try {
            $proposition = $redacteur->proposer($demande);
        } catch (ErreurRedaction $e) {
            self::journal($user, $quiz, config('eureka.atelier.modele'), $e->statut, 0, erreur: $e->getMessage());
            throw $e;
        }

        $questions = Nettoyage::questions($proposition->questions, $existantes, $nombre);
        self::journal($user, $quiz, $proposition->modele, 'reussie', count($questions), $proposition->jetonsEntree, $proposition->jetonsSortie);

        if ($questions === []) {
            throw new ErreurRedaction('Aucune question solide n\'a pu être tirée de ce texte. Essaie avec un texte plus long ou un thème plus précis.');
        }

        DB::transaction(function () use ($quiz, $questions) {
            $ordre = (int) $quiz->questions()->max('ordre');
            foreach ($questions as $q) {
                self::ecrire($quiz, $q, ++$ordre, aLEncre: false);
            }
        });

        return count($questions);
    }

    /**
     * Enregistre une question (nouvelle ou corrigée). Le premier choix est le bon.
     *
     * @param  array{enonce: string, choix: list<string>, explication: string, indice: ?string}  $q
     */
    public static function ecrire(Quiz $quiz, array $q, int $ordre, bool $aLEncre, ?Question $question = null): Question
    {
        return DB::transaction(function () use ($quiz, $q, $ordre, $aLEncre, $question) {
            $question ??= new Question(['quiz_id' => $quiz->id, 'ordre' => $ordre]);
            $question->fill([
                'enonce' => $q['enonce'],
                'explication' => $q['explication'],
                'indice' => $q['indice'],
                'a_l_encre' => $aLEncre,
            ])->save();

            // Les choix sont réécrits ; ceux d'une question déjà jouée gardent leurs réponses (choix_id mis à nul).
            $question->choix()->delete();
            foreach ($q['choix'] as $i => $texte) {
                $question->choix()->create(['texte' => $texte, 'juste' => $i === 0, 'ordre' => $i]);
            }

            return $question;
        });
    }

    public static function publier(Quiz $quiz): void
    {
        $total = $quiz->questions()->count();
        $auCrayon = $quiz->questions()->where('a_l_encre', false)->count();

        if ($total < self::QUESTIONS_MIN_POUR_PUBLIER) {
            throw ValidationException::withMessages(['publier' => 'Il faut au moins '.self::QUESTIONS_MIN_POUR_PUBLIER.' questions pour publier.']);
        }
        if ($auCrayon > 0) {
            throw ValidationException::withMessages(['publier' => "Encore {$auCrayon} question".($auCrayon > 1 ? 's' : '').' au crayon : relis-les et passe-les à l\'encre avant de publier.']);
        }

        $quiz->forceFill(['publie' => true])->save();
    }

    private static function journal(User $user, Quiz $quiz, string $modele, string $statut, int $questions, int $entree = 0, int $sortie = 0, ?string $erreur = null): void
    {
        Generation::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'modele' => $modele,
            'statut' => $statut,
            'questions' => $questions,
            'jetons_entree' => $entree,
            'jetons_sortie' => $sortie,
            'erreur' => $erreur ? mb_substr($erreur, 0, 255) : null,
        ]);
    }
}
