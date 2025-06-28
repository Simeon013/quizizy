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

    /**
     * Effectuer une recherche globale dans l'administration
     */
    public function search(Request $request)
    {
        $query = $request->input('query', '');
        
        if (empty($query)) {
            return redirect()->route('admin.dashboard');
        }

        // Recherche dans les catégories
        $categories = Category::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->limit(5)
            ->get();

        // Recherche dans les questions
        $questions = Question::where('question_text', 'like', "%{$query}%")
            ->orWhere('explanation', 'like', "%{$query}%")
            ->with('category')
            ->limit(5)
            ->get();

        // Recherche dans les utilisateurs
        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(5)
            ->get();

        return Inertia::render('Admin/Search/Results', [
            'query' => $query,
            'results' => [
                'categories' => $categories,
                'questions' => $questions,
                'users' => $users,
            ],
        ]);
    }
}
