<?php

use App\Models\Quiz;
use Database\Seeders\ContenuSeeder;
use Inertia\Testing\AssertableInertia as Page;

beforeEach(fn () => $this->seed(ContenuSeeder::class));

it('affiche les pages publiques', function (string $url, string $composant) {
    $this->get($url)->assertOk()->assertInertia(fn (Page $p) => $p->component($composant));
})->with([
    ['/', 'Accueil'],
    ['/matieres', 'Matieres'],
    ['/matieres/sciences', 'Matiere'],
    ['/quiz/le-corps-humain', 'Quiz'],
    ['/connexion', 'Auth/Connexion'],
    ['/inscription', 'Auth/Inscription'],
]);

it('liste les cinq matières dans l\'ordre du cahier', function () {
    $this->get('/matieres')->assertInertia(fn (Page $p) => $p
        ->has('matieres', 5)
        ->where('matieres.0.nom', 'Sciences')
        ->where('matieres.0.quiz', 2));
});

it('dessine une page 404 avec Gribouille', function () {
    $this->get('/matieres/inexistante')->assertNotFound()->assertInertia(fn (Page $p) => $p->component('Erreur')->where('statut', 404));
});

it('cache un quiz non publié', function () {
    Quiz::where('slug', 'grandes-dates')->update(['publie' => false]);
    $this->get('/quiz/grandes-dates')->assertNotFound();
    $this->post('/quiz/grandes-dates/parties')->assertNotFound();
});

it('réserve le cahier aux comptes', function (string $url) {
    $this->get($url)->assertRedirect('/connexion');
})->with(['/cahier', '/bulletin', '/a-revoir']);

it('pose les en-têtes de sécurité', function () {
    $this->get('/')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'same-origin');
});
