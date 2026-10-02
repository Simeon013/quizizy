<?php

namespace App\Jeu;

use Illuminate\Support\Collection;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Mélange stable : la même graine donne toujours le même ordre. Recharger une
 * page ne change pas l'ordre des choix, et en direct le téléphone et le grand
 * écran montrent les mêmes symboles aux mêmes places.
 */
class Melange
{
    /**
     * @template T
     *
     * @param  Collection<int, T>  $elements
     * @return Collection<int, T>
     */
    public static function stable(Collection $elements, string $graine): Collection
    {
        $hasard = new Randomizer(new Mt19937(crc32($graine)));

        return collect($hasard->shuffleArray($elements->values()->all()));
    }
}
