<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Afficher le tableau de bord de l'administration
     */
    public function __invoke(Request $request)
    {
        $stats = [
            'categories' => [
                'total' => Category::count(),
                'active' => Category::where('is_active', true)->count(),
            ],
            'questions' => [
                'total' => Question::count(),
                'per_category' => Category::withCount('questions')->get(),
            ],
            'users' => [
                'total' => User::count(),
                'new_this_month' => User::where('created_at', '>=', now()->subMonth())->count(),
            ],
            'recent_activity' => [
                // À implémenter : Dernières activités ou statistiques
            ],
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
        ]);
    }
}
