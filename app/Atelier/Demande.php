<?php

namespace App\Atelier;

/**
 * Ce qu'on demande au rédacteur : un nombre de questions, à partir d'un texte
 * collé (prioritaire) ou d'un simple thème.
 */
final readonly class Demande
{
    /** @param list<string> $dejaPosees énoncés existants, à ne pas répéter */
    public function __construct(
        public string $titre,
        public string $matiere,
        public int $nombre,
        public ?string $source = null,
        public ?string $theme = null,
        public array $dejaPosees = [],
    ) {}
}
