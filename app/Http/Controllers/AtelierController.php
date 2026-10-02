<?php

namespace App\Http\Controllers;

use App\Atelier\Atelier;
use App\Atelier\ErreurRedaction;
use App\Atelier\Nettoyage;
use App\Models\Matiere;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Reponse;
use App\Support\Onglets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * L'atelier : créer un quiz, faire proposer des questions au crayon, les relire,
 * les passer à l'encre, publier.
 */
class AtelierController extends Controller
{
    public function index(Request $request): Response
    {
        $quiz = Quiz::with('matiere')->withCount([
            'questions',
            'questions as au_crayon_count' => fn ($q) => $q->where('a_l_encre', false),
        ])->where('auteur_id', $request->user()->id)->latest('updated_at')->get();

        return Inertia::render('Atelier/Index', [
            'quiz' => $quiz->map(fn (Quiz $q) => [
                'slug' => $q->slug,
                'titre' => $q->titre,
                'matiere' => $q->matiere->resume(),
                'questions' => $q->questions_count,
                'auCrayon' => $q->au_crayon_count,
                'publie' => $q->publie,
                'auCatalogue' => $q->au_catalogue,
            ]),
            ...$this->etatGeneration($request),
        ]);
    }

    public function nouveau(Request $request): Response
    {
        return Inertia::render('Atelier/Nouveau', [
            'matieres' => Matiere::orderBy('ordre')->get()->map(fn (Matiere $m) => ['id' => $m->id, ...$m->resume()]),
            'sourceMax' => config('eureka.atelier.source_max'),
            ...$this->etatGeneration($request),
        ]);
    }

    public function creer(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'titre' => ['required', 'string', 'min:3', 'max:120'],
            'matiere_id' => ['required', Rule::exists('matieres', 'id')],
            'description' => ['nullable', 'string', 'max:255'],
            'mode' => ['required', Rule::in(['texte', 'theme', 'main'])],
            'source' => ['required_if:mode,texte', 'nullable', 'string', 'min:200', 'max:'.config('eureka.atelier.source_max')],
            'theme' => ['required_if:mode,theme', 'nullable', 'string', 'min:3', 'max:200'],
            'nombre' => ['required_unless:mode,main', 'nullable', 'integer', 'min:3', 'max:10'],
        ], [
            'source.min' => 'Colle un texte d\'au moins 200 caractères : en dessous, il n\'y a pas de quoi faire des questions solides.',
            'source.required_if' => 'Colle le texte dont tu veux tirer les questions.',
            'theme.required_if' => 'Indique le thème des questions.',
        ]);

        $quiz = Atelier::creerQuiz($request->user(), $donnees['titre'], Matiere::findOrFail($donnees['matiere_id']), $donnees['description'] ?? null);

        if ($donnees['mode'] === 'main') {
            return redirect()->route('atelier.quiz', $quiz);
        }

        return $this->genererPuisRetour($request, $quiz, $donnees);
    }

    public function quiz(Request $request, Quiz $quiz): Response
    {
        $this->autoriser($request, $quiz);
        $quiz->load(['matiere', 'questions.choix']);
        $jouees = Reponse::whereIn('question_id', $quiz->questions->pluck('id'))->distinct()->pluck('question_id')->flip();

        return Inertia::render('Atelier/Quiz', [
            'quiz' => [
                'slug' => $quiz->slug,
                'titre' => $quiz->titre,
                'description' => $quiz->description,
                'matiereId' => $quiz->matiere_id,
                'matiere' => $quiz->matiere->resume(),
                'publie' => $quiz->publie,
                'auCatalogue' => $quiz->au_catalogue,
                'lien' => route('quiz.show', $quiz),
            ],
            'questions' => $quiz->questions->map(fn (Question $q) => [
                'id' => $q->id,
                'enonce' => $q->enonce,
                'explication' => $q->explication,
                'indice' => $q->indice,
                'aLEncre' => $q->a_l_encre,
                'jouee' => isset($jouees[$q->id]),
                // Ici l'auteur voit la bonne réponse : c'est sa copie de correcteur.
                'choix' => $q->choix->map(fn ($c) => ['texte' => $c->texte, 'juste' => $c->juste])->values(),
            ]),
            'matieres' => Matiere::orderBy('ordre')->get()->map(fn (Matiere $m) => ['id' => $m->id, 'nom' => $m->nom, 'onglet' => Onglets::couleur($m->onglet)]),
            'minimum' => Atelier::QUESTIONS_MIN_POUR_PUBLIER,
            'sourceMax' => config('eureka.atelier.source_max'),
            'admin' => (bool) $request->user()->is_admin,
            ...$this->etatGeneration($request),
        ]);
    }

    public function modifier(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->autoriser($request, $quiz);
        $quiz->update($request->validate([
            'titre' => ['required', 'string', 'min:3', 'max:120'],
            'matiere_id' => ['required', Rule::exists('matieres', 'id')],
            'description' => ['nullable', 'string', 'max:255'],
        ]));

        return back()->with('succes', 'Couverture mise à jour.');
    }

    public function proposer(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->autoriser($request, $quiz);
        $donnees = $request->validate([
            'mode' => ['required', Rule::in(['texte', 'theme'])],
            'source' => ['required_if:mode,texte', 'nullable', 'string', 'min:200', 'max:'.config('eureka.atelier.source_max')],
            'theme' => ['required_if:mode,theme', 'nullable', 'string', 'min:3', 'max:200'],
            'nombre' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        return $this->genererPuisRetour($request, $quiz, $donnees);
    }

    public function ajouterQuestion(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->autoriser($request, $quiz);
        if ($quiz->questions()->count() >= Atelier::QUESTIONS_MAX) {
            throw ValidationException::withMessages(['enonce' => 'Ce quiz a déjà le nombre maximum de questions.']);
        }
        Atelier::ecrire($quiz, $this->questionValidee($request), (int) $quiz->questions()->max('ordre') + 1, aLEncre: true);

        return back()->with('succes', 'Question ajoutée à l\'encre.');
    }

    public function corrigerQuestion(Request $request, Quiz $quiz, Question $question): RedirectResponse
    {
        $this->autoriserQuestion($request, $quiz, $question);
        // Corriger une question, c'est l'avoir relue : elle passe à l'encre.
        Atelier::ecrire($quiz, $this->questionValidee($request), $question->ordre, aLEncre: true, question: $question);

        return back()->with('succes', 'Question corrigée et passée à l\'encre.');
    }

    public function encrer(Request $request, Quiz $quiz, Question $question): RedirectResponse
    {
        $this->autoriserQuestion($request, $quiz, $question);
        $question->update(['a_l_encre' => true]);

        return back();
    }

    public function encrerTout(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->autoriser($request, $quiz);
        $n = $quiz->questions()->where('a_l_encre', false)->update(['a_l_encre' => true]);

        return back()->with('succes', "{$n} question".($n > 1 ? 's passées' : ' passée').' à l\'encre.');
    }

    public function gommer(Request $request, Quiz $quiz, Question $question): RedirectResponse
    {
        $this->autoriserQuestion($request, $quiz, $question);
        if (Reponse::where('question_id', $question->id)->exists()) {
            throw ValidationException::withMessages(['gommer' => 'Cette question a déjà été jouée : la gommer effacerait les copies des joueurs. Corrige-la plutôt.']);
        }
        $question->delete();
        if ($quiz->publie && $quiz->questions()->count() < Atelier::QUESTIONS_MIN_POUR_PUBLIER) {
            $quiz->update(['publie' => false]);
        }

        return back()->with('succes', 'Question gommée.');
    }

    public function publier(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->autoriser($request, $quiz);
        Atelier::publier($quiz);

        return back()->with('succes', 'Quiz publié ! Partage son lien ou anime-le en direct.');
    }

    public function depublier(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->autoriser($request, $quiz);
        $quiz->update(['publie' => false, 'au_catalogue' => false]);

        return back()->with('succes', 'Quiz retiré : il ne se joue plus jusqu\'à sa prochaine publication.');
    }

    /** Mettre au catalogue public : réservé aux administrateurs. */
    public function catalogue(Request $request, Quiz $quiz): RedirectResponse
    {
        abort_unless($request->user()->is_admin, 404);
        abort_unless($quiz->publie, 409);
        $quiz->update(['au_catalogue' => ! $quiz->au_catalogue]);

        return back();
    }

    // ---------- Outils ----------

    private function genererPuisRetour(Request $request, Quiz $quiz, array $donnees): RedirectResponse
    {
        // La génération prend souvent plusieurs dizaines de secondes, sans file d'attente sur un mutualisé.
        set_time_limit(180);

        try {
            $ajoutees = Atelier::proposer(
                $quiz,
                $request->user(),
                $donnees['mode'] === 'texte' ? trim($donnees['source']) : null,
                $donnees['mode'] === 'theme' ? trim($donnees['theme']) : null,
                (int) $donnees['nombre'],
            );
        } catch (ErreurRedaction $e) {
            return redirect()->route('atelier.quiz', $quiz)->with('alerte', $e->getMessage());
        }

        return redirect()->route('atelier.quiz', $quiz)
            ->with('succes', "Gribouille a proposé {$ajoutees} question".($ajoutees > 1 ? 's' : '').' au crayon. Relis-les avant de les passer à l\'encre.');
    }

    /** @return array{enonce: string, choix: list<string>, explication: string, indice: ?string} */
    private function questionValidee(Request $request): array
    {
        $d = $request->validate([
            'enonce' => ['required', 'string', 'max:'.Nettoyage::ENONCE_MAX],
            'choix' => ['required', 'array', 'min:2', 'max:4'],
            'choix.*' => ['required', 'string', 'max:'.Nettoyage::CHOIX_MAX, 'distinct:ignore_case'],
            'juste' => ['required', 'integer', 'min:0'],
            'explication' => ['required', 'string', 'max:'.Nettoyage::EXPLICATION_MAX],
            'indice' => ['nullable', 'string', 'max:'.Nettoyage::INDICE_MAX],
        ], [
            'choix.*.distinct' => 'Deux choix sont identiques.',
            'choix.*.required' => 'Un choix est vide.',
            'explication.required' => 'L\'explication est la note du stylo vert : elle aide à retenir, elle est obligatoire.',
        ]);

        $choix = array_values($d['choix']);
        if (! isset($choix[$d['juste']])) {
            throw ValidationException::withMessages(['juste' => 'Coche la bonne réponse.']);
        }
        // Convention du contenu : le premier choix est le bon (mélangé à l'affichage).
        $bonne = $choix[$d['juste']];
        unset($choix[$d['juste']]);

        return [
            'enonce' => trim($d['enonce']),
            'choix' => array_map('trim', [$bonne, ...array_values($choix)]),
            'explication' => trim($d['explication']),
            'indice' => filled($d['indice'] ?? null) ? trim($d['indice']) : null,
        ];
    }

    private function etatGeneration(Request $request): array
    {
        return [
            'generationDisponible' => Atelier::redacteur() !== null,
            'generationsRestantes' => Atelier::restantes($request->user()),
        ];
    }

    private function autoriser(Request $request, Quiz $quiz): void
    {
        abort_unless($quiz->auteur_id === $request->user()->id, 404);
    }

    private function autoriserQuestion(Request $request, Quiz $quiz, Question $question): void
    {
        $this->autoriser($request, $quiz);
        abort_unless($question->quiz_id === $quiz->id, 404);
    }
}
