<?php

use App\Http\Middleware\EnTetesSecurite;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            EnTetesSecurite::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('connexion'));
        $middleware->redirectUsersTo(fn () => route('cahier'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Pages d'erreur dessinées (Gribouille), sauf les 500 en développement.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $statut = $response->getStatusCode();

            if ($statut === 419) {
                return back()->with('alerte', 'La page avait expiré. Réessaie.');
            }

            if (in_array($statut, [403, 404, 429, 500, 503], true)
                && ! $request->expectsJson()
                && ! ($statut === 500 && app()->hasDebugModeEnabled())) {
                return Inertia::render('Erreur', ['statut' => $statut])
                    ->toResponse($request)
                    ->setStatusCode($statut);
            }

            return $response;
        });
    })->create();
