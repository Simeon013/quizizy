<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as RegleMotDePasse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Mot de passe oublié : un lien à usage unique, valable une heure
 * (config/auth.php), envoyé par email.
 */
class MotDePasseController extends Controller
{
    public function oubli(): Response
    {
        return Inertia::render('Auth/Oubli');
    }

    public function envoyer(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'not_regex:/[\r\n]/']]);

        // Adresse inconnue, déjà demandé il y a moins d'une minute, ou envoyé : même réponse.
        // Répondre autrement dirait à n'importe qui quelles adresses ont un cahier.
        Password::sendResetLink($request->only('email'));

        return back()->with('succes', 'Si un cahier existe à cette adresse, un lien vient d\'y être envoyé. Pense à regarder dans les courriers indésirables.');
    }

    public function formulaire(Request $request, string $jeton): Response
    {
        return Inertia::render('Auth/NouveauMotDePasse', [
            'jeton' => $jeton,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function enregistrer(Request $request): RedirectResponse
    {
        $request->validate([
            'jeton' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', RegleMotDePasse::min(8)],
        ]);

        $statut = Password::reset(
            ['token' => $request->input('jeton'), ...$request->only('email', 'password', 'password_confirmation')],
            function (User $user, string $motDePasse) {
                $user->forceFill(['password' => $motDePasse, 'remember_token' => Str::random(60)])->save();
                // Quelqu'un qui connaissait l'ancien mot de passe ne garde aucune session ouverte.
                DB::table('sessions')->where('user_id', $user->id)->delete();
                Auth::login($user);
            },
        );

        if ($statut !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'Ce lien n\'est plus valable : il sert une seule fois, pendant une heure.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route('cahier')->with('succes', 'Nouveau mot de passe enregistré. Te revoilà dans ton cahier !');
    }
}
