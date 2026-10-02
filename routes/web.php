<?php

use App\Http\Controllers\AtelierController;
use App\Http\Controllers\Auth\ConnexionController;
use App\Http\Controllers\Auth\InscriptionController;
use App\Http\Controllers\Auth\MotDePasseController;
use App\Http\Controllers\CahierController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\DirectController;
use App\Http\Controllers\PartieController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogueController::class, 'accueil'])->name('accueil');
Route::get('/matieres', [CatalogueController::class, 'index'])->name('matieres');
Route::get('/matieres/{matiere}', [CatalogueController::class, 'matiere'])->name('matieres.show');
Route::get('/quiz/{quiz}', [CatalogueController::class, 'quiz'])->name('quiz.show');

// Jouer : ouvert aux invités, la partie est alors liée à leur session.
Route::post('/quiz/{quiz}/parties', [PartieController::class, 'demarrer'])
    ->middleware('throttle:parties')->name('parties.store');
Route::get('/parties/{partie}', [PartieController::class, 'afficher'])->name('parties.show');
Route::get('/parties/{partie}/copie', [PartieController::class, 'copie'])->name('parties.copie');
Route::middleware('throttle:jeu')->group(function () {
    Route::post('/parties/{partie}/question', [PartieController::class, 'question'])->name('parties.question');
    Route::post('/parties/{partie}/indice', [PartieController::class, 'indice'])->name('parties.indice');
    Route::post('/parties/{partie}/reponse', [PartieController::class, 'repondre'])->name('parties.reponse');
});

// Mode en direct, côté joueur : pas de compte, un pseudo suffit.
Route::get('/rejoindre', [DirectController::class, 'formulaire'])->name('direct.rejoindre');
Route::post('/rejoindre', [DirectController::class, 'rejoindre'])->middleware('throttle:rejoindre');
Route::get('/direct/{salle}', [DirectController::class, 'manette'])->name('direct.manette');
Route::middleware('throttle:direct')->group(function () {
    Route::get('/direct/{salle}/etat', [DirectController::class, 'etatManette'])->name('direct.etat');
    Route::post('/direct/{salle}/reponse', [DirectController::class, 'repondre'])->name('direct.reponse');
});

Route::middleware('auth')->group(function () {
    // Mode en direct, côté animateur.
    Route::post('/quiz/{quiz}/direct', [DirectController::class, 'ouvrir'])->middleware('throttle:parties')->name('direct.ouvrir');
    Route::get('/animer/{salle}', [DirectController::class, 'ecran'])->name('direct.ecran');
    Route::middleware('throttle:direct')->group(function () {
        Route::get('/animer/{salle}/etat', [DirectController::class, 'etatEcran'])->name('direct.ecran.etat');
        Route::post('/animer/{salle}/{action}', [DirectController::class, 'commande'])
            ->whereIn('action', ['suivante', 'corriger', 'terminer'])->name('direct.commande');
        Route::delete('/animer/{salle}/participants/{participant}', [DirectController::class, 'retirer'])->name('direct.retirer');
    });

    // L'atelier : créer ses quiz.
    Route::prefix('atelier')->name('atelier.')->controller(AtelierController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/nouveau', 'nouveau')->name('nouveau');
        Route::post('/', 'creer')->middleware('throttle:atelier')->name('creer');
        Route::get('/{quiz}', 'quiz')->name('quiz');
        Route::patch('/{quiz}', 'modifier')->name('modifier');
        Route::post('/{quiz}/propositions', 'proposer')->middleware('throttle:atelier')->name('proposer');
        Route::post('/{quiz}/questions', 'ajouterQuestion')->name('questions.ajouter');
        Route::put('/{quiz}/questions/{question}', 'corrigerQuestion')->name('questions.corriger');
        Route::post('/{quiz}/encre', 'encrerTout')->name('encrer-tout');
        Route::post('/{quiz}/questions/{question}/encre', 'encrer')->name('questions.encrer');
        Route::delete('/{quiz}/questions/{question}', 'gommer')->name('questions.gommer');
        Route::post('/{quiz}/publier', 'publier')->name('publier');
        Route::post('/{quiz}/depublier', 'depublier')->name('depublier');
        Route::post('/{quiz}/catalogue', 'catalogue')->name('catalogue');
    });

    Route::get('/cahier', [CahierController::class, 'cahier'])->name('cahier');
    Route::get('/bulletin', [CahierController::class, 'bulletin'])->name('bulletin');
    Route::get('/a-revoir', [CahierController::class, 'aRevoir'])->name('a-revoir');
    Route::post('/a-revoir/parties', [CahierController::class, 'reviser'])
        ->middleware('throttle:parties')->name('a-revoir.reviser');
    Route::post('/deconnexion', [ConnexionController::class, 'destroy'])->name('deconnexion');
});

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [ConnexionController::class, 'create'])->name('connexion');
    Route::post('/connexion', [ConnexionController::class, 'store'])->middleware('throttle:connexion');
    Route::get('/inscription', [InscriptionController::class, 'create'])->name('inscription');
    Route::post('/inscription', [InscriptionController::class, 'store'])->middleware('throttle:inscription');

    Route::get('/mot-de-passe-oublie', [MotDePasseController::class, 'oubli'])->name('mot-de-passe.oubli');
    Route::post('/mot-de-passe-oublie', [MotDePasseController::class, 'envoyer'])->middleware('throttle:oubli');
    Route::get('/mot-de-passe/{jeton}', [MotDePasseController::class, 'formulaire'])->name('mot-de-passe.nouveau');
    Route::post('/mot-de-passe', [MotDePasseController::class, 'enregistrer'])->middleware('throttle:oubli')->name('mot-de-passe.enregistrer');
});
