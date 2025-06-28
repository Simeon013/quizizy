<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Category;
use App\Models\Question;
use App\Models\UserStat;

class StatisticController extends Controller
{
    /**
     * Affiche les statistiques globales
     */
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_categories' => Category::count(),
            'total_questions' => Question::count(),
            'active_users' => User::where('is_active', true)->count(),
            'avg_score' => UserStat::avg('average_score') ?? 0,
        ];

        return Inertia::render('Admin/Stats/Index', [
            'stats' => $stats
        ]);
    }
}
