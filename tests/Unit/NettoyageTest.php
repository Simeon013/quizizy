<?php

use App\Atelier\Nettoyage;

it('écarte les questions bancales venues du modèle', function () {
    $brutes = [
        ['enonce' => 'Bonne question ?', 'choix' => ['A', 'B', 'C', 'D'], 'explication' => 'Parce que A.', 'indice' => 'Pense à A.'],
        ['enonce' => 'Choix en double ?', 'choix' => ['Oui', ' oui ', 'Non'], 'explication' => 'X', 'indice' => ''],
        ['enonce' => 'Trop de choix ?', 'choix' => ['1', '2', '3', '4', '5'], 'explication' => 'X', 'indice' => ''],
        ['enonce' => '', 'choix' => ['A', 'B'], 'explication' => 'X', 'indice' => ''],
        ['enonce' => 'Sans explication ?', 'choix' => ['A', 'B'], 'explication' => '  ', 'indice' => ''],
        ['enonce' => 'bonne QUESTION', 'choix' => ['A', 'B'], 'explication' => 'Doublon d\'énoncé.', 'indice' => ''],
        'pas un tableau',
        ['enonce' => 'Déjà posée !', 'choix' => ['A', 'B'], 'explication' => 'X', 'indice' => ''],
        ['enonce' => '<b>Balises</b> retirées ?', 'choix' => ['Vrai', 'Faux'], 'explication' => 'Oui.', 'indice' => null],
    ];

    $gardees = Nettoyage::questions($brutes, ['Déjà posée ?'], 10);

    expect(array_column($gardees, 'enonce'))->toBe(['Bonne question ?', 'Balises retirées ?'])
        ->and($gardees[1]['indice'])->toBeNull();
});

it('s\'arrête au nombre demandé', function () {
    $brutes = array_map(fn ($i) => ['enonce' => "Q{$i} ?", 'choix' => ['A', 'B'], 'explication' => 'E', 'indice' => ''], range(1, 8));

    expect(Nettoyage::questions($brutes, [], 3))->toHaveCount(3);
});
