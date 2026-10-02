<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jeu\AccesPartie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ConnexionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Connexion');
    }

    public function store(Request $request): RedirectResponse
    {
        $identifiants = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($identifiants, $request->boolean('souvenir'))) {
            throw ValidationException::withMessages([
                'email' => 'Cette adresse et ce mot de passe ne correspondent à aucun cahier.',
            ]);
        }

        // Les copies faites en invité sont dans la session, qui va être régénérée : on les rattache avant.
        AccesPartie::rattacher($request, Auth::id());
        $request->session()->regenerate();

        return redirect()->intended(route('cahier'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('accueil');
    }
}
