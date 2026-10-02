<?php

namespace App\Atelier;

interface Redacteur
{
    /** @throws ErreurRedaction */
    public function proposer(Demande $demande): Proposition;
}
