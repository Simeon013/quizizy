<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'utilisateur' => $user ? ['nom' => $user->name] : null,
            'flash' => [
                'succes' => fn () => $request->session()->get('succes'),
                'alerte' => fn () => $request->session()->get('alerte'),
            ],
        ];
    }
}
