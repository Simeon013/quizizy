<?php

namespace App\Http\Controllers;

use App\Jeu\AccesPartie;
use App\Jeu\Bulletin;
use App\Jeu\Deroulement;
use App\Jeu\Notation;
use App\Jeu\Revision;
use App\Jeu\SerieDeJours;
use App\Models\Gommette;
use App\Models\Partie;
use App\Models\Question;
use App\Support\Gommettes;
use App\Support\Niveaux;
use App\Support\Onglets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Le cahier personnel : étiquette, pages cornées, copies, à revoir, bulletin.
 */
class CahierController extends Controller
{
    public function cahier(Request $request): Response
    {
        $user = $request->user();
        $points = (int) Partie::where('user_id', $user->id)->whereNotNull('terminee_le')->sum('points');

        $resume = fn (Partie $p) => [
            'id' => $p->id,
            'titre' => $p->titre(),
            'onglet' => $p->quiz ? Onglets::couleur($p->quiz->matiere->onglet) : null,
            'position' => $p->position,
            'total' => $p->total(),
            'bonnes' => $p->bonnes,
            'surVingt' => Notation::surVingt($p->bonnes, $p->total()),
            'le' => ($p->terminee_le ?? $p->updated_at)->toIso8601String(),
        ];

        return Inertia::render('Cahier', [
            'etiquette' => [
                'nom' => $user->name,
                'niveau' => Niveaux::pour($points),
                'points' => $points,
                'serie' => SerieDeJours::pour($user->id),
                'copies' => Partie::where('user_id', $user->id)->whereNotNull('terminee_le')->count(),
            ],
            'cornees' => Partie::with('quiz.matiere')->where('user_id', $user->id)->whereNull('terminee_le')
                ->latest('updated_at')->limit(3)->get()->map($resume),
            'copies' => Partie::with('quiz.matiere')->where('user_id', $user->id)->whereNotNull('terminee_le')
                ->latest('terminee_le')->limit(6)->get()->map($resume),
            'aRevoir' => count(Revision::questionIds($user->id)),
            'gommettes' => $this->gommettes($user->id),
        ]);
    }

    public function bulletin(Request $request): Response
    {
        $lignes = Bulletin::pour($request->user()->id);

        return Inertia::render('Bulletin', [
            'lignes' => $lignes,
            'appreciation' => Bulletin::appreciation($lignes),
            'gommettes' => $this->gommettes($request->user()->id),
        ]);
    }

    public function aRevoir(Request $request): Response
    {
        $ids = Revision::questionIds($request->user()->id);

        return Inertia::render('ARevoir', [
            'questions' => Question::with(['quiz.matiere', 'choix'])->findMany(array_slice($ids, 0, 30))
                ->map(fn (Question $q) => [
                    'enonce' => $q->enonce,
                    'matiere' => $q->quiz->matiere->nom,
                    'onglet' => Onglets::couleur($q->quiz->matiere->onglet),
                ])->values(),
            'total' => count($ids),
        ]);
    }

    public function reviser(Request $request): RedirectResponse
    {
        $partie = Deroulement::demarrerRevision($request->user()->id);

        if (! $partie) {
            return redirect()->route('a-revoir')->with('alerte', 'Rien à revoir pour l\'instant. Bravo !');
        }
        AccesPartie::retenir($request, $partie);

        return redirect()->route('parties.show', $partie);
    }

    /** @return list<array<string, mixed>> toutes les gommettes, obtenues ou non */
    private function gommettes(int $userId): array
    {
        $obtenues = Gommette::where('user_id', $userId)->pluck('obtenue_le', 'cle');

        return collect(Gommettes::TOUTES)->keys()->map(fn ($cle) => [
            ...Gommettes::fiche($cle),
            'obtenue' => isset($obtenues[$cle]),
        ])->all();
    }
}
