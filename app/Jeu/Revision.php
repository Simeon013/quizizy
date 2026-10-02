<?php

namespace App\Jeu;

use App\Models\Reponse;

/**
 * « À revoir » : les questions dont la dernière réponse du joueur était fausse.
 * C'est une lecture, pas une liste stockée : réussir la question la retire
 * d'elle-même, sans drapeau à tenir à jour.
 */
class Revision
{
    /** @return list<int> */
    public static function questionIds(int $userId): array
    {
        $dernieres = Reponse::query()
            ->join('parties', 'parties.id', '=', 'reponses.partie_id')
            ->where('parties.user_id', $userId)
            ->orderByDesc('reponses.id')
            ->get(['reponses.question_id', 'reponses.juste']);

        $vues = [];
        $aRevoir = [];
        foreach ($dernieres as $reponse) {
            if (isset($vues[$reponse->question_id])) {
                continue;
            }
            $vues[$reponse->question_id] = true;
            if (! $reponse->juste) {
                $aRevoir[] = $reponse->question_id;
            }
        }

        return $aRevoir;
    }
}
