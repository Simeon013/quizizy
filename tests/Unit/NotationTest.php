<?php

use App\Jeu\Notation;

it('note une bonne réponse avec un bonus de rapidité', function () {
    expect(Notation::points(true, false, 0, 30))->toBe(150)
        ->and(Notation::points(true, false, 15000, 30))->toBe(125)
        ->and(Notation::points(true, false, 30000, 30))->toBe(100)
        ->and(Notation::points(true, false, 60000, 30))->toBe(100);
});

it('donne zéro à une mauvaise réponse et la moitié avec un indice', function () {
    expect(Notation::points(false, false, 1000, 30))->toBe(0)
        ->and(Notation::points(true, true, 1000, 30))->toBe(50);
});

it('calcule la note sur 20 au demi-point', function () {
    expect(Notation::surVingt(7, 8))->toBe(17.5)
        ->and(Notation::surVingt(1, 3))->toBe(6.5)
        ->and(Notation::surVingt(0, 0))->toBe(0.0);
});
