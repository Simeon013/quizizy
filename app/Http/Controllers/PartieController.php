<?php

namespace App\Http\Controllers;

use App\Jeu\AccesPartie;
use App\Jeu\Deroulement;
use App\Jeu\Notation;
use App\Models\Gommette;
use App\Models\Partie;
use App\Models\Question;
use App\Models\Quiz;
use App\Support\Gommettes;
use App\Support\Onglets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartieController extends Controller
{
    public function demarrer(Request $request, Quiz $quiz): RedirectResponse
    {
        abort_unless($quiz->publie, 404);

        $partie = Deroulement::demarrerQuiz($quiz, $request->user()?->id);
        AccesPartie::retenir($request, $partie);

        return redirect()->route('parties.show', $partie);
    }

    public function afficher(Request $request, Partie $partie): Response|RedirectResponse
    {
        AccesPartie::verifier($request, $partie);

        if ($partie->estTerminee()) {
            return redirect()->route('parties.copie', $partie);
        }

        return Inertia::render('Partie', [
            'partie' => $this->entete($partie),
            'question' => Deroulement::questionEnCours($partie),
        ]);
    }

    /** Passe à la question suivante, après la correction. */
    public function question(Request $request, Partie $partie): JsonResponse
    {
        AccesPartie::verifier($request, $partie);

        return response()->json(['question' => Deroulement::questionEnCours($partie)]);
    }

    public function indice(Request $request, Partie $partie): JsonResponse
    {
        AccesPartie::verifier($request, $partie);

        return response()->json(['indice' => Deroulement::indice($partie)]);
    }

    public function repondre(Request $request, Partie $partie): JsonResponse
    {
        AccesPartie::verifier($request, $partie);
        $donnees = $request->validate([
            'question_id' => ['required', 'integer'],
            'choix_id' => ['nullable', 'integer'],
        ]);

        return response()->json(Deroulement::repondre($partie, $donnees['question_id'], $donnees['choix_id'] ?? null));
    }

    public function copie(Request $request, Partie $partie): Response|RedirectResponse
    {
        AccesPartie::verifier($request, $partie);

        if (! $partie->estTerminee()) {
            return redirect()->route('parties.show', $partie);
        }

        $partie->load(['reponses', 'quiz.matiere']);
        $questions = Question::with(['choix', 'quiz.matiere'])->findMany($partie->questions)->keyBy('id');
        $reponses = $partie->reponses->keyBy('question_id');

        $lignes = collect($partie->questions)->map(function (int $id) use ($questions, $reponses) {
            $question = $questions[$id] ?? null;
            $reponse = $reponses[$id] ?? null;
            if (! $question || ! $reponse) {
                return null;
            }

            return [
                'enonce' => $question->enonce,
                'explication' => $question->explication,
                'juste' => $reponse->juste,
                'tempsEcoule' => $reponse->choix_id === null,
                'indice' => $reponse->indice,
                'points' => $reponse->points,
                'onglet' => Onglets::couleur($question->quiz->matiere->onglet),
                'choix' => $question->choix->map(fn ($c) => [
                    'texte' => $c->texte,
                    'juste' => $c->juste,
                    'choisi' => $c->id === $reponse->choix_id,
                ])->all(),
            ];
        })->filter()->values();

        $precedente = $partie->user_id && $partie->quiz_id
            ? Partie::where('user_id', $partie->user_id)->where('quiz_id', $partie->quiz_id)
                ->whereNotNull('terminee_le')->where('terminee_le', '<', $partie->terminee_le)
                ->latest('terminee_le')->first()
            : null;

        return Inertia::render('Copie', [
            'partie' => [
                ...$this->entete($partie),
                'bonnes' => $partie->bonnes,
                'points' => $partie->points,
                'serieMax' => $partie->serie_max,
                'surVingt' => Notation::surVingt($partie->bonnes, $partie->total()),
                'progres' => $precedente ? $partie->bonnes - $precedente->bonnes : null,
            ],
            'questions' => $lignes,
            'gommettes' => Gommette::where('partie_id', $partie->id)->pluck('cle')
                ->map(fn ($cle) => Gommettes::fiche($cle))->all(),
            'invite' => $partie->user_id === null,
        ]);
    }

    /** @return array<string, mixed> */
    private function entete(Partie $partie): array
    {
        $partie->loadMissing('quiz.matiere');

        return [
            'id' => $partie->id,
            'titre' => $partie->titre(),
            'type' => $partie->type,
            'total' => $partie->total(),
            'points' => $partie->points,
            'serie' => $partie->serie,
            'quiz' => $partie->quiz?->slug,
            'matiere' => $partie->quiz?->matiere->resume(),
        ];
    }
}
