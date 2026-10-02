<?php

namespace App\Atelier;

use RuntimeException;

/** Une génération qui n'a pas abouti. Le message s'affiche tel quel à l'auteur. */
class ErreurRedaction extends RuntimeException
{
    public function __construct(string $message, public readonly string $statut = 'echouee')
    {
        parent::__construct($message);
    }
}
