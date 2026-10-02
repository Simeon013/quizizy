<?php

namespace App\Http\Controllers;

use App\Models\Matiere;
use App\Models\Partie;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ce qui se consulte sans compte : la couverture, les intercalaires, les quiz.
 */
class CatalogueController extends Controller
{
    public function accueil(): Response
    {
        return Inertia::render('Accueil', [
            'matieres' => $this->listeMatieres(),
            'questionsEnTout' => Question::count(),
        ]);
    }

    private function listeMatieres(): array
    {
        return Matiere::orderBy('ordre')
            ->withCount('quizPublies')
            ->get()
            ->map(fn (Matiere $m) => [...$m->resume(), 'quiz' => $m->quiz_publies_count])
            ->all();
    }

    public function index(): Response
    {
        return Inertia::render('Matieres', ['matieres' => $this->listeMatieres()]);
    }

    public function matiere(Request $request, Matiere $matiere): Response
    {
        $quiz = $matiere->quizPublies()->withCount('questions')->orderBy('id')->get();
        $termines = $this->terminesParQuiz($request);

        return Inertia::render('Matiere', [
            'matiere' => $matiere->resume(),
            'quiz' => $quiz->map(fn (Quiz $q) => [
                'titre' => $q->titre,
                'slug' => $q->slug,
                'description' => $q->description,
                'questions' => min($q->questions_count, (int) config('eureka.questions_par_partie')),
                'meilleure' => $termines[$q->id] ?? null,
            ])->all(),
        ]);
    }

    public function quiz(Request $request, Quiz $quiz): Response
    {
        abort_unless($quiz->publie, 404);
        $quiz->load('matiere')->loadCount('questions');

        $enCours = $request->user()
            ? Partie::where('user_id', $request->user()->id)->where('quiz_id', $quiz->id)
                ->whereNull('terminee_le')->latest()->value('id')
            : null;

        return Inertia::render('Quiz', [
            'quiz' => [
                'titre' => $quiz->titre,
                'slug' => $quiz->slug,
                'description' => $quiz->description,
                'questions' => min($quiz->questions_count, (int) config('eureka.questions_par_partie')),
                'secondes' => $quiz->secondes_par_question,
            ],
            'matiere' => $quiz->matiere->resume(),
            'meilleure' => $this->terminesParQuiz($request)[$quiz->id] ?? null,
            'enCours' => $enCours,
        ]);
    }

    /**
     * Meilleure copie de l'utilisateur par quiz (bonnes / total).
     *
     * @return array<int, array{bonnes: int, total: int}>
     */
    private function terminesParQuiz(Request $request): array
    {
        if (! $request->user()) {
            return [];
        }

        return Partie::where('user_id', $request->user()->id)
            ->whereNotNull('terminee_le')->whereNotNull('quiz_id')
            ->get(['quiz_id', 'bonnes', 'questions'])
            ->groupBy('quiz_id')
            ->map(fn ($parties) => $parties->map(fn (Partie $p) => ['bonnes' => $p->bonnes, 'total' => $p->total()])
                ->sortByDesc(fn ($c) => $c['bonnes'] / max(1, $c['total']))->first())
            ->all();
    }
}
