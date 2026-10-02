<?php

namespace App\Atelier;

/**
 * Les questions proposées, « au crayon ». Chaque question : énoncé, choix (le
 * premier est le bon, comme dans le contenu du catalogue), explication, indice.
 */
final readonly class Proposition
{
    /** @param list<array{enonce: string, choix: list<string>, explication: string, indice: string}> $questions */
    public function __construct(
        public array $questions,
        public string $modele,
        public int $jetonsEntree = 0,
        public int $jetonsSortie = 0,
    ) {}
}
