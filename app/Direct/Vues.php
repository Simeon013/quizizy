<?php

namespace App\Direct;

use App\Jeu\Melange;
use App\Models\Choix;
use App\Models\Participant;
use App\Models\Question;
use App\Models\ReponseDirect;
use App\Models\Salle;

/**
 * Ce que voit chaque écran d'une salle. Le grand écran et le téléphone ne
 * reçoivent jamais la bonne réponse pendant la question.
 */
class Vues
{
    /** Un symbole par place, dessiné au feutre : on ne distingue pas les choix par la couleur seule. */
    public const SYMBOLES = ['triangle', 'rond', 'carre', 'etoile'];

    /** @return array<string, mixed> */
    public static function ecran(Salle $salle): array
    {
        $salle->loadMissing('quiz');
        $question = self::question($salle);
        $questionId = $salle->questionEnCoursId();
        $reponses = $questionId ? ReponseDirect::where('salle_id', $salle->id)->where('question_id', $questionId)->get() : collect();

        return [
            ...self::commun($salle),
            'joueurs' => $salle->presents()->orderBy('id')->get(['id', 'pseudo'])->toArray(),
            'question' => $question,
            'nbReponses' => $reponses->count(),
            'correction' => $salle->etat === Salle::CORRECTION ? [
                ...self::correction($salle),
                'repartition' => $reponses->countBy('choix_id')->all(),
            ] : null,
            'palmares' => in_array($salle->etat, [Salle::CORRECTION, Salle::TERMINEE], true) ? self::palmares($salle, 10) : [],
        ];
    }

    /** @return array<string, mixed> */
    public static function manette(Salle $salle, Participant $moi): array
    {
        $questionId = $salle->questionEnCoursId();
        $maReponse = $questionId
            ? ReponseDirect::where('participant_id', $moi->id)->where('question_id', $questionId)->first()
            : null;
        $classement = in_array($salle->etat, [Salle::CORRECTION, Salle::TERMINEE], true) ? self::palmares($salle) : [];
        $rang = collect($classement)->search(fn ($l) => $l['id'] === $moi->id);

        return [
            ...self::commun($salle),
            'moi' => [
                'pseudo' => $moi->pseudo,
                'retire' => $moi->estRetire(),
                // Les points ne se dévoilent qu'à la correction : pendant la question, ils trahiraient la réponse.
                'points' => $salle->etat === Salle::QUESTION ? null : $moi->points,
                'rang' => $rang === false ? null : $rang + 1,
            ],
            'joueurs' => $salle->presents()->count(),
            'question' => self::question($salle),
            'monChoix' => $maReponse?->choix_id,
            'correction' => $salle->etat === Salle::CORRECTION ? [
                ...self::correction($salle),
                'juste' => (bool) $maReponse?->juste,
                'points' => $maReponse?->points ?? 0,
                'repondu' => $maReponse !== null,
            ] : null,
            'podium' => $salle->etat === Salle::TERMINEE ? array_slice($classement, 0, 3) : [],
        ];
    }

    /** @return array<string, mixed> */
    private static function commun(Salle $salle): array
    {
        return [
            'version' => $salle->version,
            'etat' => $salle->etat,
            'code' => $salle->code,
            'titre' => $salle->quiz->titre,
            'position' => $salle->position,
            'total' => $salle->total(),
        ];
    }

    /** @return array<string, mixed>|null */
    private static function question(Salle $salle): ?array
    {
        $id = $salle->questionEnCoursId();
        if ($id === null || ! in_array($salle->etat, [Salle::QUESTION, Salle::CORRECTION], true)) {
            return null;
        }
        $question = Question::with('choix')->findOrFail($id);

        return [
            'id' => $question->id,
            'numero' => $salle->position + 1,
            'enonce' => $question->enonce,
            'choix' => Melange::stable($question->choix, $salle->id.$question->id)->take(count(self::SYMBOLES))
                ->values()->map(fn (Choix $c, int $i) => ['id' => $c->id, 'texte' => $c->texte, 'symbole' => self::SYMBOLES[$i]])->all(),
            'secondes' => $salle->secondes_par_question,
            'restantMs' => $salle->etat === Salle::QUESTION
                ? max(0, (int) now()->diffInMilliseconds($salle->question_fin_le, false))
                : 0,
        ];
    }

    /** @return array<string, mixed> */
    private static function correction(Salle $salle): array
    {
        $question = Question::with('choix')->findOrFail($salle->questionEnCoursId());

        return [
            'choixJusteId' => $question->choix->firstWhere('juste', true)?->id,
            'explication' => $question->explication,
        ];
    }

    /** @return list<array{id: int, pseudo: string, points: int, bonnes: int}> */
    private static function palmares(Salle $salle, ?int $limite = null): array
    {
        return $salle->presents()
            ->orderByDesc('points')->orderBy('id')
            ->when($limite, fn ($q) => $q->limit($limite))
            ->get(['id', 'pseudo', 'points', 'bonnes'])
            ->map(fn (Participant $p) => ['id' => $p->id, 'pseudo' => $p->pseudo, 'points' => $p->points, 'bonnes' => $p->bonnes])
            ->all();
    }
}
