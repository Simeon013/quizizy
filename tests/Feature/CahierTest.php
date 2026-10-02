<?php

use App\Jeu\Revision;
use App\Jeu\SerieDeJours;
use App\Models\Partie;
use App\Models\User;
use App\Support\Gommettes;
use Database\Seeders\ContenuSeeder;
use Inertia\Testing\AssertableInertia as Page;

beforeEach(function () {
    $this->seed(ContenuSeeder::class);
    $this->user = User::factory()->create(['name' => 'Awa']);
    $this->actingAs($this->user);
});

function jouerQuiz(string $slug, int $fausses): string
{
    $id = demarrerPartie($slug);
    for ($i = 0; $i < 8; $i++) {
        repondre($id, juste: $i >= $fausses);
    }

    return $id;
}

it('range les erreurs dans « À revoir » puis les retire une fois réussies', function () {
    jouerQuiz('le-corps-humain', fausses: 3);
    expect(Revision::questionIds($this->user->id))->toHaveCount(3);

    $this->post('/a-revoir/parties')->assertRedirect();
    $revision = Partie::where('type', 'revision')->firstOrFail();
    expect($revision->total())->toBe(3);

    for ($i = 0; $i < 3; $i++) {
        repondre($revision->id, juste: true);
    }

    expect(Revision::questionIds($this->user->id))->toBeEmpty()
        ->and($this->user->gommettes()->pluck('cle'))->toContain('revision');
});

it('ne lance pas de révision quand il n\'y a rien à revoir', function () {
    $this->post('/a-revoir/parties')->assertRedirect('/a-revoir')->assertSessionHas('alerte');
});

it('affiche le cahier avec son étiquette', function () {
    jouerQuiz('le-corps-humain', fausses: 0);

    $this->get('/cahier')->assertOk()->assertInertia(fn (Page $p) => $p
        ->component('Cahier')
        ->where('etiquette.nom', 'Awa')
        ->where('etiquette.copies', 1)
        ->where('etiquette.serie', 1)
        ->has('copies', 1)
        ->has('gommettes', count(Gommettes::TOUTES)));
});

it('calcule la moyenne du bulletin par matière', function () {
    jouerQuiz('le-corps-humain', fausses: 2); // 6/8 = 15/20
    jouerQuiz('l-espace-et-la-terre', fausses: 0); // 20/20

    $this->get('/bulletin')->assertInertia(fn (Page $p) => $p
        ->component('Bulletin')
        ->where('lignes.0.matiere', 'Sciences')
        ->where('lignes.0.moyenne', 17.5)
        ->where('lignes.0.copies', 2)
        ->where('lignes.1.moyenne', null));
});

it('compte la série de jours d\'affilée', function () {
    foreach ([2, 1, 0] as $jours) {
        $this->travelTo(now()->subDays($jours)->setTime(12, 0));
        jouerQuiz('le-corps-humain', fausses: 0);
        $this->travelBack();
    }

    expect(SerieDeJours::pour($this->user->id))->toBe(3)
        ->and($this->user->gommettes()->pluck('cle'))->toContain('assidu');
});
