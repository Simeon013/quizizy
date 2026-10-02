<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jeu\AccesPartie;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class InscriptionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Inscription');
    }

    public function store(Request $request): RedirectResponse
    {
        // Champ piège : invisible pour un humain, rempli par les robots.
        if (filled($request->input('site_web'))) {
            return redirect()->route('accueil');
        }

        $donnees = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255', 'not_regex:/[\r\n]/', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'email.unique' => 'Un cahier existe déjà à cette adresse. Connecte-toi plutôt.',
        ]);

        $user = User::create($donnees);
        AccesPartie::rattacher($request, $user->id);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('cahier')->with('succes', 'Ton cahier est prêt.');
    }
}
