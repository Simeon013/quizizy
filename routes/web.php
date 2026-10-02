<?php

use App\Http\Controllers\Auth\ConnexionController;
use App\Http\Controllers\Auth\InscriptionController;
use App\Http\Controllers\CahierController;
use App\Http\Controllers\CatalogueController;
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

Route::middleware('auth')->group(function () {
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
});
