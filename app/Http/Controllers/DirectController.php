<?php

namespace App\Http\Controllers;

use App\Direct\Animation;
use App\Direct\Vues;
use App\Models\Participant;
use App\Models\Quiz;
use App\Models\Salle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Le mode en direct. Les écrans interrogent l'état toutes les secondes
 * (`/etat?v=…`) ; s'il n'a pas bougé, la réponse tient en quelques octets.
 */
class DirectController extends Controller
{
    private const CLE_SESSION = 'participants_direct';

    // ---------- Côté joueur ----------

    public function formulaire(Request $request): Response
    {
        return Inertia::render('Direct/Rejoindre', [
            'code' => preg_replace('/\D/', '', (string) $request->query('code', '')),
        ]);
    }

    public function rejoindre(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'code' => ['required', 'string'],
            'pseudo' => ['required', 'string', 'min:2', 'max:20', 'not_regex:/[<>\r\n]/'],
        ], [
            'pseudo.min' => 'Ton pseudo doit faire au moins 2 caractères.',
            'pseudo.max' => 'Ton pseudo doit faire 20 caractères au plus.',
        ]);

        $code = preg_replace('/\D/', '', $donnees['code']);
        $salle = Salle::ouvertes()->where('code', $code)->first();
        if (! $salle) {
            throw ValidationException::withMessages(['code' => 'Aucune partie ouverte avec ce code. Vérifie les chiffres au tableau.']);
        }

        // Déjà dans cette salle depuis ce téléphone : on y retourne, sans créer de double.
        if ($this->participant($request, $salle)) {
            return redirect()->route('direct.manette', $salle);
        }

        $participant = Animation::rejoindre($salle, $donnees['pseudo']);
        $request->session()->put(self::CLE_SESSION.'.'.$salle->id, $participant->id);

        return redirect()->route('direct.manette', $salle);
    }

    public function manette(Request $request, Salle $salle): Response|RedirectResponse
    {
        $moi = $this->participant($request, $salle);
        if (! $moi) {
            return redirect()->route('direct.rejoindre', ['code' => $salle->code]);
        }

        return Inertia::render('Direct/Manette', [
            'salle' => $salle->id,
            'etat' => Vues::manette(Animation::actualiser($salle)->load('quiz'), $moi),
        ]);
    }

    public function etatManette(Request $request, Salle $salle): JsonResponse
    {
        $moi = $this->participant($request, $salle);
        abort_unless($moi, 404);

        return $this->etatSiChange($request, Animation::actualiser($salle), fn (Salle $s) => Vues::manette($s, $moi->refresh()));
    }

    public function repondre(Request $request, Salle $salle): JsonResponse
    {
        $moi = $this->participant($request, $salle);
        abort_unless($moi, 404);
        $donnees = $request->validate(['question_id' => ['required', 'integer'], 'choix_id' => ['required', 'integer']]);

        return response()->json(Animation::repondre($salle, $moi, $donnees['question_id'], $donnees['choix_id']));
    }

    // ---------- Côté animateur ----------

    public function ouvrir(Request $request, Quiz $quiz): RedirectResponse
    {
        abort_unless($quiz->jouablePar($request->user()), 404);
        if (! $quiz->questions()->exists()) {
            return back()->with('alerte', 'Ce quiz n\'a pas encore de question.');
        }

        return redirect()->route('direct.ecran', Animation::ouvrir($quiz, $request->user()->id));
    }

    public function ecran(Request $request, Salle $salle): Response
    {
        $this->autoriserAnimateur($request, $salle);

        return Inertia::render('Direct/Ecran', [
            'salle' => $salle->id,
            'lienRejoindre' => route('direct.rejoindre'),
            'etat' => Vues::ecran(Animation::actualiser($salle)),
        ]);
    }

    public function etatEcran(Request $request, Salle $salle): JsonResponse
    {
        $this->autoriserAnimateur($request, $salle);

        return $this->etatSiChange($request, Animation::actualiser($salle), fn (Salle $s) => Vues::ecran($s));
    }

    public function commande(Request $request, Salle $salle, string $action): JsonResponse
    {
        $this->autoriserAnimateur($request, $salle);

        $salle = match ($action) {
            'suivante' => Animation::suivante($salle),
            'corriger' => Animation::clore($salle),
            'terminer' => Animation::terminer($salle),
            default => abort(404),
        };

        return response()->json(Vues::ecran($salle->fresh()));
    }

    public function retirer(Request $request, Salle $salle, Participant $participant): JsonResponse
    {
        $this->autoriserAnimateur($request, $salle);
        Animation::retirer($salle, $participant);

        return response()->json(Vues::ecran($salle->fresh()));
    }

    // ---------- Outils ----------

    private function participant(Request $request, Salle $salle): ?Participant
    {
        $id = $request->session()->get(self::CLE_SESSION.'.'.$salle->id);

        return $id ? Participant::where('salle_id', $salle->id)->find($id) : null;
    }

    private function autoriserAnimateur(Request $request, Salle $salle): void
    {
        abort_unless($salle->animateur_id === $request->user()->id, 404);
    }

    /** L'état complet si la version a bougé, sinon juste la version. */
    private function etatSiChange(Request $request, Salle $salle, \Closure $vue): JsonResponse
    {
        if ((int) $request->query('v', -1) === $salle->version) {
            return response()->json(['version' => $salle->version, 'inchange' => true]);
        }

        return response()->json($vue($salle->loadMissing('quiz')));
    }
}
