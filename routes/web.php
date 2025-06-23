<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\QuestionController;

// Page d'accueil publique
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Tableau de bord utilisateur
Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Routes d'administration
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    // Tableau de bord
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, '__invoke'])
        ->name('dashboard');
    
    // Gestion des catégories
    Route::resource('categories', CategoryController::class)->except(['show']);
    
    // Gestion des questions
    Route::resource('questions', QuestionController::class)->except(['show']);
    Route::post('questions/{question}/answers', [QuestionController::class, 'manageAnswers'])
        ->name('questions.answers.manage');
    
    // Redirection de la racine de l'admin vers le tableau de bord
    Route::redirect('/', '/admin/dashboard');
});

// Paramètres utilisateur
require __DIR__.'/settings.php';

// Authentification
require __DIR__.'/auth.php';
