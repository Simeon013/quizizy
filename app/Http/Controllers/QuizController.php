<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Question;
use App\Models\UserResponse;
use App\Models\UserStat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class QuizController extends Controller
{
    /**
     * Affiche la page de sélection de catégorie
     */
    public function selectCategory()
    {
        $categories = Category::where('is_active', true)
            ->withCount(['questions' => function($query) {
                $query->where('is_active', true);
            }])
            ->having('questions_count', '>', 0)
            ->orderBy('name')
            ->get();

        // Récupérer les statistiques de l'utilisateur connecté (si authentifié)
        $userStats = [];
        if (Auth::check()) {
            $userStats = UserStat::firstOrCreate(
                ['user_id' => Auth::id()],
                ['level' => 1, 'xp' => 0, 'quizzes_completed' => 0]
            );
        }

        return Inertia::render('Quiz/SelectCategory', [
            'categories' => $categories,
            'userStats' => $userStats ?? null,
        ]);
    }

    /**
     * Commence un nouveau quiz pour une catégorie donnée
     */
    public function startQuiz(Request $request, Category $category)
    {
        $request->validate([
            'question_count' => 'required|integer|min:5|max:20',
            'difficulty' => 'nullable|in:easy,medium,hard',
        ]);

        // Vérifier que la catégorie est active
        if (!$category->is_active) {
            return redirect()->route('quiz.select-category')
                ->with('error', 'Cette catégorie n\'est pas disponible pour le moment.');
        }

        // Récupérer les questions aléatoires
        $questions = Question::where('category_id', $category->id)
            ->where('is_active', true)
            ->when($request->difficulty, function($query, $difficulty) {
                return $query->where('difficulty', $difficulty);
            })
            ->with(['answers' => function($query) {
                $query->inRandomOrder();
            }])
            ->inRandomOrder()
            ->take($request->question_count)
            ->get();

        // Vérifier qu'il y a suffisamment de questions
        if ($questions->count() < 5) {
            return redirect()->route('quiz.select-category')
                ->with('error', 'Pas assez de questions disponibles pour cette catégorie. Veuillez en sélectionner une autre.');
        }

        // Si l'utilisateur est connecté, enregistrer le quiz en cours
        if (Auth::check()) {
            $quizSession = $request->user()->quizSessions()->create([
                'category_id' => $category->id,
                'question_count' => $questions->count(),
                'completed' => false,
            ]);

            // Préparer les questions pour la session
            $questionsData = $questions->map(function($question) use ($quizSession) {
                return [
                    'quiz_session_id' => $quizSession->id,
                    'question_id' => $question->id,
                    'answered' => false,
                    'is_correct' => false,
                ];
            });

            // Ajouter les questions à la session
            $quizSession->sessionQuestions()->createMany($questionsData->toArray());

            // Stocker l'ID de la session de quiz
            session(['current_quiz_session_id' => $quizSession->id]);
        }

        // Préparer les données pour le frontend
        $questionsData = $questions->map(function($question) {
            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'difficulty' => $question->difficulty,
                'answers' => $question->answers->map(function($answer) {
                    return [
                        'id' => $answer->id,
                        'answer_text' => $answer->answer_text,
                    ];
                }),
            ];
        });

        return Inertia::render('Quiz/Play', [
            'category' => $category->only(['id', 'name', 'color', 'icon']),
            'questions' => $questionsData,
            'questionCount' => $questions->count(),
            'timeLimit' => $questions->count() * 30, // 30 secondes par question
        ]);
    }

    /**
     * Soumet une réponse à une question
     */
    public function submitAnswer(Request $request)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answer_id' => 'required|exists:answers,id',
        ]);

        $question = Question::findOrFail($request->question_id);
        $selectedAnswer = $question->answers()->findOrFail($request->answer_id);
        $isCorrect = (bool) $selectedAnswer->is_correct;

        // Si l'utilisateur est connecté, enregistrer la réponse
        if (Auth::check() && session()->has('current_quiz_session_id')) {
            $quizSession = $request->user()->quizSessions()
                ->where('id', session('current_quiz_session_id'))
                ->where('completed', false)
                ->first();

            if ($quizSession) {
                // Mettre à jour ou créer la réponse de l'utilisateur
                $quizSession->userResponses()->updateOrCreate(
                    ['question_id' => $question->id],
                    [
                        'answer_id' => $selectedAnswer->id,
                        'is_correct' => $isCorrect,
                    ]
                );

                // Mettre à jour la question de la session
                $quizSession->sessionQuestions()
                    ->where('question_id', $question->id)
                    ->update([
                        'answered' => true,
                        'is_correct' => $isCorrect,
                    ]);
            }
        }

        return response()->json([
            'is_correct' => $isCorrect,
            'correct_answer_id' => $question->answers()->where('is_correct', true)->first()->id,
            'explanation' => $question->explanation,
        ]);
    }

    /**
     * Termine le quiz en cours et calcule le score
     */
    public function completeQuiz(Request $request)
    {
        if (!Auth::check() || !session()->has('current_quiz_session_id')) {
            return response()->json(['message' => 'Aucun quiz en cours'], 400);
        }

        $quizSession = $request->user()->quizSessions()
            ->where('id', session('current_quiz_session_id'))
            ->where('completed', false)
            ->with(['category', 'sessionQuestions'])
            ->firstOrFail();

        // Calculer le score
        $totalQuestions = $quizSession->question_count;
        $correctAnswers = $quizSession->sessionQuestions->where('is_correct', true)->count();
        $score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100) : 0;

        // Mettre à jour la session de quiz
        $quizSession->update([
            'completed' => true,
            'score' => $score,
            'completed_at' => now(),
        ]);

        // Calculer l'XP gagnée (10 XP par bonne réponse)
        $xpEarned = $correctAnswers * 10;
        
        // Mettre à jour les statistiques de l'utilisateur
        $userStat = UserStat::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['level' => 1, 'xp' => 0, 'quizzes_completed' => 0]
        );

        // Ajouter l'XP et vérifier le niveau
        $userStat->increment('xp', $xpEarned);
        $userStat->increment('quizzes_completed');
        
        // Vérifier si l'utilisateur monte de niveau (1000 XP par niveau)
        $newLevel = floor($userStat->xp / 1000) + 1;
        if ($newLevel > $userStat->level) {
            $userStat->level = $newLevel;
            $userStat->save();
        }

        // Nettoyer la session
        session()->forget('current_quiz_session_id');

        return response()->json([
            'score' => $score,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'xp_earned' => $xpEarned,
            'level_up' => $newLevel > $userStat->level - 1 ? $newLevel : null,
        ]);
    }

    /**
     * Affiche les résultats d'un quiz terminé
     */
    public function showResults($sessionId)
    {
        $quizSession = Auth::user()->quizSessions()
            ->with(['category', 'sessionQuestions.question', 'userResponses.answer'])
            ->findOrFail($sessionId);

        return Inertia::render('Quiz/Results', [
            'quizSession' => $quizSession,
            'userStats' => Auth::user()->stat,
        ]);
    }

    /**
     * Affiche l'historique des quiz de l'utilisateur
     */
    public function history()
    {
        $quizSessions = Auth::user()->quizSessions()
            ->with('category')
            ->orderBy('completed_at', 'desc')
            ->paginate(10);

        return Inertia::render('Quiz/History', [
            'quizSessions' => $quizSessions,
            'userStats' => Auth::user()->stat,
        ]);
    }

    /**
     * Affiche le classement
     */
    public function leaderboard()
    {
        // Classement global
        $globalLeaderboard = UserStat::with('user')
            ->orderBy('xp', 'desc')
            ->take(50)
            ->get();

        // Classement par catégorie
        $categories = Category::where('is_active', true)->get();
        $categoryLeaderboards = [];

        foreach ($categories as $category) {
            $categoryLeaderboards[$category->id] = [
                'name' => $category->name,
                'top_users' => $category->topUsers()->take(10)->get(),
            ];
        }

        // Position de l'utilisateur actuel
        $userPosition = null;
        if (Auth::check()) {
            $userPosition = UserStat::where('xp', '>', Auth::user()->stat->xp ?? 0)
                ->count() + 1;
        }

        return Inertia::render('Quiz/Leaderboard', [
            'globalLeaderboard' => $globalLeaderboard,
            'categoryLeaderboards' => $categoryLeaderboards,
            'userPosition' => $userPosition,
            'userStats' => Auth::user()->stat ?? null,
        ]);
    }
}
