<?php

use App\Models\Choix;
use App\Models\Partie;
use App\Models\User;
use Database\Seeders\ContenuSeeder;
use Inertia\Testing\AssertableInertia as Page;

beforeEach(fn () => $this->seed(ContenuSeeder::class));

it('démarre une partie en invité et affiche la première question sans la réponse', function () {
    $id = demarrerPartie();

    $this->get("/parties/{$id}")->assertOk()->assertInertia(fn (Page $p) => $p
        ->component('Partie')
        ->where('question.numero', 1)
        ->where('question.total', 8)
        ->has('question.choix', 4)
        ->missing('question.choix.0.juste')
        ->where('question.indice', null));

    expect(Partie::find($id)->question_affichee_le)->not->toBeNull();
});

it('ne laisse pas voir la partie d\'un autre', function () {
    $id = demarrerPartie();

    $this->flushSession();
    $this->get("/parties/{$id}")->assertNotFound();
    $this->postJson("/parties/{$id}/indice")->assertNotFound();

    $this->actingAs(User::factory()->create())->get("/parties/{$id}")->assertNotFound();
});

it('corrige une bonne réponse avec le bonus de rapidité', function () {
    $id = demarrerPartie();
    $this->get("/parties/{$id}");

    $correction = repondre($id, juste: true)->assertOk()->json();

    expect($correction['juste'])->toBeTrue()
        ->and($correction['points'])->toBeGreaterThan(100)->toBeLessThanOrEqual(150)
        ->and($correction['choixId'])->toBe($correction['choixJusteId'])
        ->and($correction['explication'])->not->toBeEmpty()
        ->and($correction['termine'])->toBeFalse();
});

it('corrige une mauvaise réponse et désigne la bonne', function () {
    $id = demarrerPartie();
    $correction = repondre($id, juste: false)->json();

    expect($correction['juste'])->toBeFalse()
        ->and($correction['points'])->toBe(0)
        ->and($correction['choixJusteId'])->not->toBe($correction['choixId']);
    expect(Choix::find($correction['choixJusteId'])->juste)->toBeTrue();
});

it('divise les points par deux quand l\'indice est décollé', function () {
    $id = demarrerPartie();
    $this->get("/parties/{$id}");

    $indice = $this->postJson("/parties/{$id}/indice")->assertOk()->json('indice');
    expect($indice)->not->toBeEmpty();

    // Recharger la page garde l'indice visible : on ne le décolle pas deux fois gratuitement.
    $this->get("/parties/{$id}")->assertInertia(fn (Page $p) => $p->where('question.indice', $indice));

    $correction = repondre($id, juste: true)->json();
    expect($correction['points'])->toBe(50)->and($correction['indice'])->toBeTrue();
});

it('compte faux une réponse arrivée après le chrono', function () {
    $id = demarrerPartie();
    $this->get("/parties/{$id}");

    $this->travel(40)->seconds();
    $correction = repondre($id, juste: true)->json();

    expect($correction['juste'])->toBeFalse()->and($correction['tempsEcoule'])->toBeTrue()->and($correction['points'])->toBe(0);
});

it('refuse une seconde réponse à la même question', function () {
    $id = demarrerPartie();
    $this->get("/parties/{$id}");
    $partie = Partie::find($id);
    $questionId = $partie->questionEnCoursId();
    $choix = Choix::where('question_id', $questionId)->first();

    $this->postJson("/parties/{$id}/reponse", ['question_id' => $questionId, 'choix_id' => $choix->id])->assertOk();
    $this->postJson("/parties/{$id}/reponse", ['question_id' => $questionId, 'choix_id' => $choix->id])->assertConflict();
});

it('refuse un choix qui appartient à une autre question', function () {
    $id = demarrerPartie();
    $this->get("/parties/{$id}");
    $questionId = Partie::find($id)->questionEnCoursId();
    $autre = Choix::where('question_id', '!=', $questionId)->first();

    $this->postJson("/parties/{$id}/reponse", ['question_id' => $questionId, 'choix_id' => $autre->id])->assertUnprocessable();
});

it('ne démarre pas le chrono de la question suivante avant qu\'on la demande', function () {
    $id = demarrerPartie();
    repondre($id);

    expect(Partie::find($id)->question_affichee_le)->toBeNull();
    $this->postJson("/parties/{$id}/question")->assertOk()->assertJsonPath('question.numero', 2);
});

it('termine la partie et rend une copie corrigée', function () {
    $id = demarrerPartie();
    for ($i = 0; $i < 8; $i++) {
        $correction = repondre($id, juste: $i !== 0)->json();
    }
    expect($correction['termine'])->toBeTrue();

    $this->get("/parties/{$id}")->assertRedirect("/parties/{$id}/copie");
    $this->get("/parties/{$id}/copie")->assertOk()->assertInertia(fn (Page $p) => $p
        ->component('Copie')
        ->where('partie.bonnes', 7)
        ->where('partie.total', 8)
        ->where('partie.surVingt', 17.5)
        ->where('invite', true)
        ->has('questions', 8)
        ->has('questions.0.choix', 4)
        ->has('gommettes', 0));
});

it('décerne des gommettes à un joueur connecté', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $id = demarrerPartie();
    for ($i = 0; $i < 8; $i++) {
        repondre($id, juste: true);
    }

    $cles = $user->gommettes()->pluck('cle')->all();
    expect($cles)->toContain('premiere-copie', 'sans-faute', 'serie-5', 'debrouillard')
        ->not->toContain('tete-chercheuse', 'revision');

    $this->get("/parties/{$id}/copie")->assertInertia(fn (Page $p) => $p->where('invite', false)->has('gommettes', count($cles)));
});
