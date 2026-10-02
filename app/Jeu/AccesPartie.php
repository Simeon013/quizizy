<?php

namespace App\Jeu;

use App\Models\Partie;
use Illuminate\Http\Request;

/**
 * Qui peut voir une partie : son joueur connecté, ou l'invité dont la session
 * l'a créée. Pour tout autre, la partie n'existe pas (404, pas 403).
 */
class AccesPartie
{
    private const CLE_SESSION = 'parties_invite';

    public static function retenir(Request $request, Partie $partie): void
    {
        if ($partie->user_id === null) {
            $request->session()->push(self::CLE_SESSION, $partie->id);
        }
    }

    public static function verifier(Request $request, Partie $partie): void
    {
        $autorise = $partie->user_id !== null
            ? $partie->user_id === $request->user()?->id
            : in_array($partie->id, $request->session()->get(self::CLE_SESSION, []), true);

        abort_unless($autorise, 404);
    }

    /**
     * À l'inscription ou à la connexion, les copies faites en invité dans cette
     * même session rejoignent le cahier. Seulement celles de la session : jamais
     * une partie désignée par le navigateur.
     */
    public static function rattacher(Request $request, int $userId): void
    {
        $ids = $request->session()->pull(self::CLE_SESSION, []);

        if ($ids !== []) {
            Partie::whereIn('id', $ids)->whereNull('user_id')->update(['user_id' => $userId]);
        }
    }
}
