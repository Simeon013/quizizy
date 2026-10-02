<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        // Chaque limite a sa propre clé : deux limites qui partagent une clé partagent leur compteur.
        RateLimiter::for('parties', fn (Request $r) => [
            Limit::perMinute(20)->by('parties-minute|'.$r->ip()),
            Limit::perDay(400)->by('parties-jour|'.$r->ip()),
        ]);
        RateLimiter::for('jeu', fn (Request $r) => Limit::perMinute(180)->by('jeu|'.$r->ip()));
        // En direct, toute une classe partage souvent la même adresse IP (même box,
        // même réseau d'école) : on compte par joueur (ou par compte pour l'animateur),
        // avec un plafond par IP assez large pour une classe. Pas par identifiant de
        // session : un client sans cookie en change à chaque requête et échapperait à la limite.
        RateLimiter::for('direct', function (Request $r) {
            $qui = $r->user() ? 'compte:'.$r->user()->id
                : (($joueurs = $r->session()->get('participants_direct')) ? 'joueur:'.md5(json_encode($joueurs)) : 'ip:'.$r->ip());

            return [
                Limit::perMinute(150)->by('direct|'.$qui),
                Limit::perMinute(3000)->by('direct-ip|'.$r->ip()),
            ];
        });
        RateLimiter::for('rejoindre', fn (Request $r) => [
            Limit::perMinute(10)->by('rejoindre-session|'.$r->session()->getId()),
            Limit::perMinute(120)->by('rejoindre-ip|'.$r->ip()),
        ]);
        RateLimiter::for('connexion', fn (Request $r) => [
            Limit::perMinute(5)->by('connexion|'.mb_strtolower((string) $r->input('email')).'|'.$r->ip()),
            Limit::perMinute(20)->by('connexion-ip|'.$r->ip()),
        ]);
        RateLimiter::for('inscription', fn (Request $r) => [
            Limit::perMinute(5)->by('inscription-minute|'.$r->ip()),
            Limit::perDay(30)->by('inscription-jour|'.$r->ip()),
        ]);
    }
}
