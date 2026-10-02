<?php

use App\Models\Generation;
use App\Models\Matiere;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Reponse;
use App\Models\User;
use Database\Seeders\ContenuSeeder;
use Inertia\Testing\AssertableInertia as Page;

beforeEach(function () {
    $this->seed(ContenuSeeder::class);
    $this->auteur = User::factory()->create();
    $this->actingAs($this->auteur);
});

function creerAvecTheme(int $nombre = 6): Quiz
{
    test()->post('/atelier', [
        'titre' => 'Les volcans',
        'matiere_id' => Matiere::where('slug', 'sciences')->value('id'),
        'mode' => 'theme',
        'theme' => 'les volcans',
        'nombre' => $nombre,
    ])->assertRedirect();

    return Quiz::where('titre', 'Les volcans')->latest('id')->firstOrFail();
}

it('réserve l\'atelier aux comptes', function () {
    auth()->logout();
    $this->get('/atelier')->assertRedirect('/connexion');
});

it('crée un quiz dont les questions proposées arrivent au crayon', function () {
    $quiz = creerAvecTheme(6);

    expect($quiz->auteur_id)->toBe($this->auteur->id)
        ->and($quiz->publie)->toBeFalse()
        ->and($quiz->questions()->count())->toBe(6)
        ->and($quiz->questions()->where('a_l_encre', true)->count())->toBe(0);
    expect(Generation::where('user_id', $this->auteur->id)->first())
        ->statut->toBe('reussie')->questions->toBe(6);

    // Chaque question a une seule bonne réponse, la première.
    $q = $quiz->questions()->with('choix')->first();
    expect($q->choix->where('juste', true)->count())->toBe(1)->and($q->choix->first()->juste)->toBeTrue();

    $this->get("/atelier/{$quiz->slug}")->assertOk()->assertInertia(fn (Page $p) => $p
        ->component('Atelier/Quiz')->has('questions', 6)->where('questions.0.aLEncre', false));
});

it('crée un quiz à la main sans appeler l\'IA', function () {
    $this->post('/atelier', [
        'titre' => 'Mon quiz maison',
        'matiere_id' => Matiere::first()->id,
        'mode' => 'main',
    ])->assertRedirect();

    expect(Quiz::where('titre', 'Mon quiz maison')->first()->questions()->count())->toBe(0)
        ->and(Generation::count())->toBe(0);
});

it('cache l\'atelier d\'un quiz aux autres comptes', function () {
    $quiz = creerAvecTheme();
    $this->actingAs(User::factory()->create());

    $this->get("/atelier/{$quiz->slug}")->assertNotFound();
    $this->post("/atelier/{$quiz->slug}/publier")->assertNotFound();
    $this->get("/quiz/{$quiz->slug}")->assertNotFound();
    $this->post("/quiz/{$quiz->slug}/parties")->assertNotFound();
});

it('ne publie qu\'une fois tout relu et passé à l\'encre', function () {
    $quiz = creerAvecTheme(6);

    $this->post("/atelier/{$quiz->slug}/publier")->assertSessionHasErrors('publier');
    expect($quiz->fresh()->publie)->toBeFalse();

    $this->post("/atelier/{$quiz->slug}/encre")->assertRedirect();
    $this->post("/atelier/{$quiz->slug}/publier")->assertSessionHasNoErrors();
    expect($quiz->fresh()->publie)->toBeTrue()->and($quiz->fresh()->au_catalogue)->toBeFalse();
});

it('exige au moins cinq questions pour publier', function () {
    $quiz = creerAvecTheme(3);
    $this->post("/atelier/{$quiz->slug}/encre");

    $this->post("/atelier/{$quiz->slug}/publier")->assertSessionHasErrors('publier');
});

it('corrige une question : la bonne réponse cochée devient la bonne, et la question passe à l\'encre', function () {
    $quiz = creerAvecTheme(5);
    $question = $quiz->questions()->first();

    $this->put("/atelier/{$quiz->slug}/questions/{$question->id}", [
        'enonce' => 'Quel volcan a détruit Pompéi ?',
        'choix' => ['Etna', 'Vésuve', 'Stromboli'],
        'juste' => 1,
        'explication' => 'Le Vésuve est entré en éruption en 79.',
        'indice' => 'Il domine la baie de Naples.',
    ])->assertSessionHasNoErrors();

    $question->refresh()->load('choix');
    expect($question->a_l_encre)->toBeTrue()
        ->and($question->choix)->toHaveCount(3)
        ->and($question->choix->firstWhere('juste', true)->texte)->toBe('Vésuve');
});

it('refuse deux choix identiques', function () {
    $quiz = creerAvecTheme(5);
    $this->post("/atelier/{$quiz->slug}/questions", [
        'enonce' => 'Question ?', 'choix' => ['Oui', 'oui'], 'juste' => 0, 'explication' => 'Parce que.',
    ])->assertSessionHasErrors('choix.1');
});

it('ne gomme pas une question déjà jouée', function () {
    $quiz = creerAvecTheme(5);
    $this->post("/atelier/{$quiz->slug}/encre");

    // L'auteur essaie son brouillon : la première question reçoit une réponse.
    $id = demarrerPartie($quiz->slug);
    repondre($id);
    $jouee = Reponse::first()->question_id;

    $this->delete("/atelier/{$quiz->slug}/questions/{$jouee}")->assertSessionHasErrors('gommer');
    expect(Question::find($jouee))->not->toBeNull();
});

it('limite les propositions par jour, sauf pour un administrateur', function () {
    config(['eureka.atelier.generations_par_jour' => 2]);
    $quiz = creerAvecTheme(3);
    $this->post("/atelier/{$quiz->slug}/propositions", ['mode' => 'theme', 'theme' => 'les séismes', 'nombre' => 2]);
    $this->post("/atelier/{$quiz->slug}/propositions", ['mode' => 'theme', 'theme' => 'les geysers', 'nombre' => 2])
        ->assertSessionHas('alerte');

    expect($quiz->questions()->count())->toBe(5);

    $this->auteur->forceFill(['is_admin' => true])->save();
    $this->post("/atelier/{$quiz->slug}/propositions", ['mode' => 'theme', 'theme' => 'les geysers', 'nombre' => 2])
        ->assertSessionHas('succes');
});

it('reste utilisable à la main quand la génération est coupée', function () {
    config(['eureka.atelier.redacteur' => 'aucun']);
    $this->get('/atelier/nouveau')->assertInertia(fn (Page $p) => $p->where('generationDisponible', false));

    $this->post('/atelier', ['titre' => 'Sans IA', 'matiere_id' => Matiere::first()->id, 'mode' => 'theme', 'theme' => 'la mer', 'nombre' => 5])
        ->assertSessionHas('alerte');
});

it('met au catalogue public sur décision d\'un administrateur seulement', function () {
    $quiz = creerAvecTheme(5);
    $this->post("/atelier/{$quiz->slug}/encre");
    $this->post("/atelier/{$quiz->slug}/publier");

    $this->post("/atelier/{$quiz->slug}/catalogue")->assertNotFound();
    $this->get('/matieres/sciences')->assertInertia(fn (Page $p) => $p->has('quiz', 2));

    $this->auteur->forceFill(['is_admin' => true])->save();
    $this->post("/atelier/{$quiz->slug}/catalogue")->assertRedirect();
    $this->get('/matieres/sciences')->assertInertia(fn (Page $p) => $p->has('quiz', 3));
});

it('compte le quota sur la journée locale, même la nuit en temps universel', function () {
    // 23 h 30 UTC = 0 h 30 à Porto-Novo : la journée locale a commencé à 23 h UTC.
    $this->travelTo(now()->utc()->setTime(23, 30));
    config(['eureka.atelier.generations_par_jour' => 1]);

    $quiz = creerAvecTheme(3);
    $this->post("/atelier/{$quiz->slug}/propositions", ['mode' => 'theme', 'theme' => 'les séismes', 'nombre' => 2])
        ->assertSessionHas('alerte');
});
