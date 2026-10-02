<?php

use App\Models\Question;
use App\Models\Quiz;
use Database\Seeders\ContenuSeeder;

beforeEach(fn () => $this->seed(ContenuSeeder::class));

it('donne à chaque question une seule bonne réponse, une explication et un indice', function () {
    $questions = Question::with('choix')->get();
    expect($questions)->toHaveCount(80);

    foreach ($questions as $q) {
        expect($q->choix->where('juste', true))->toHaveCount(1, "« {$q->enonce} »")
            ->and($q->choix)->toHaveCount(4)
            ->and($q->choix->pluck('texte')->unique())->toHaveCount(4)
            ->and($q->explication)->not->toBeEmpty()
            ->and($q->indice)->not->toBeEmpty();
    }
});

it('peut être rejoué sans doublon', function () {
    $this->seed(ContenuSeeder::class);
    expect(Quiz::count())->toBe(10)->and(Question::count())->toBe(80);
});

it('ne remplace pas les questions d\'un quiz déjà joué', function () {
    $id = demarrerPartie();
    repondre($id);
    $avant = Question::where('quiz_id', Quiz::where('slug', 'le-corps-humain')->value('id'))->pluck('id')->all();

    $this->seed(ContenuSeeder::class);

    expect(Question::where('quiz_id', Quiz::where('slug', 'le-corps-humain')->value('id'))->pluck('id')->all())->toBe($avant);
});
