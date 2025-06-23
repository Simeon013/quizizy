<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class QuestionController extends Controller
{
    /**
     * Affiche la liste des questions
     */
    public function index()
    {
        $questions = Question::with(['category', 'answers'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/Questions/Index', [
            'questions' => $questions
        ]);
    }

    /**
     * Affiche le formulaire de création d'une question
     */
    public function create()
    {
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Questions/Create', [
            'categories' => $categories,
            'difficultyLevels' => [
                ['value' => 'easy', 'label' => 'Facile'],
                ['value' => 'medium', 'label' => 'Moyen'],
                ['value' => 'hard', 'label' => 'Difficile'],
            ]
        ]);
    }

    /**
     * Enregistre une nouvelle question
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'question_text' => 'required|string|max:1000',
            'category_id' => 'required|exists:categories,id',
            'difficulty' => 'required|in:easy,medium,hard',
            'explanation' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'answers' => 'required|array|min:2|max:6',
            'answers.*.text' => 'required|string|max:500',
            'answers.*.is_correct' => 'required|boolean',
        ]);

        // Vérifier qu'il y a au moins une réponse correcte
        $hasCorrectAnswer = collect($validated['answers'])->contains('is_correct', true);
        
        if (!$hasCorrectAnswer) {
            return back()->withErrors([
                'answers' => 'Au moins une réponse doit être marquée comme correcte.'
            ]);
        }

        // Créer la question
        $question = Question::create([
            'question_text' => $validated['question_text'],
            'category_id' => $validated['category_id'],
            'difficulty' => $validated['difficulty'],
            'explanation' => $validated['explanation'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Créer les réponses
        foreach ($validated['answers'] as $answerData) {
            $question->answers()->create([
                'answer_text' => $answerData['text'],
                'is_correct' => $answerData['is_correct'],
            ]);
        }

        return redirect()->route('admin.questions.index')
            ->with('success', 'La question a été créée avec succès.');
    }

    /**
     * Affiche le formulaire d'édition d'une question
     */
    public function edit(Question $question)
    {
        $question->load('answers');
        
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Questions/Edit', [
            'question' => $question,
            'categories' => $categories,
            'difficultyLevels' => [
                ['value' => 'easy', 'label' => 'Facile'],
                ['value' => 'medium', 'label' => 'Moyen'],
                ['value' => 'hard', 'label' => 'Difficile'],
            ]
        ]);
    }

    /**
     * Met à jour une question existante
     */
    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'question_text' => 'required|string|max:1000',
            'category_id' => 'required|exists:categories,id',
            'difficulty' => 'required|in:easy,medium,hard',
            'explanation' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $question->update($validated);

        return redirect()->route('admin.questions.index')
            ->with('success', 'La question a été mise à jour avec succès.');
    }

    /**
     * Supprime une question
     */
    public function destroy(Question $question)
    {
        // Supprimer d'abord les réponses associées
        $question->answers()->delete();
        
        // Puis supprimer la question
        $question->delete();

        return redirect()->route('admin.questions.index')
            ->with('success', 'La question a été supprimée avec succès.');
    }

    /**
     * Gère les réponses d'une question (ajout, modification, suppression)
     */
    public function manageAnswers(Request $request, Question $question)
    {
        $validated = $request->validate([
            'answers' => 'required|array|min:2|max:6',
            'answers.*.id' => 'nullable|exists:answers,id',
            'answers.*.text' => 'required|string|max:500',
            'answers.*.is_correct' => 'required|boolean',
        ]);

        // Vérifier qu'il y a au moins une réponse correcte
        $hasCorrectAnswer = collect($validated['answers'])->contains('is_correct', true);
        
        if (!$hasCorrectAnswer) {
            return response()->json([
                'message' => 'Au moins une réponse doit être marquée comme correcte.'
            ], 422);
        }

        // Récupérer les IDs des réponses existantes
        $existingAnswerIds = $question->answers()->pluck('id')->toArray();
        $updatedAnswerIds = [];

        // Mettre à jour ou créer les réponses
        foreach ($validated['answers'] as $answerData) {
            if (isset($answerData['id'])) {
                // Mettre à jour une réponse existante
                $answer = $question->answers()->find($answerData['id']);
                if ($answer) {
                    $answer->update([
                        'answer_text' => $answerData['text'],
                        'is_correct' => $answerData['is_correct'],
                    ]);
                    $updatedAnswerIds[] = $answer->id;
                }
            } else {
                // Créer une nouvelle réponse
                $newAnswer = $question->answers()->create([
                    'answer_text' => $answerData['text'],
                    'is_correct' => $answerData['is_correct'],
                ]);
                $updatedAnswerIds[] = $newAnswer->id;
            }
        }

        // Supprimer les réponses qui ne sont plus dans la liste
        $answersToDelete = array_diff($existingAnswerIds, $updatedAnswerIds);
        if (!empty($answersToDelete)) {
            $question->answers()->whereIn('id', $answersToDelete)->delete();
        }

        return response()->json([
            'message' => 'Les réponses ont été mises à jour avec succès.'
        ]);
    }
}
