<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /**
     * Afficher la liste des catégories
     */
    public function index()
    {
        $categories = Category::all();
        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        return Inertia::render('Admin/Categories/Create');
    }

    /**
     * Enregistrer une nouvelle catégorie
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'color' => 'nullable|string|size:7',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? '#3B82F6',
            'icon' => $validated['icon'] ?? 'question',
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Catégorie créée avec succès');
    }

    /**
     * Afficher une catégorie spécifique
     */
    public function show(Category $category)
    {
        return Inertia::render('Admin/Categories/Show', [
            'category' => $category->loadCount('questions'),
        ]);
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Category $category)
    {
        return Inertia::render('Admin/Categories/Edit', [
            'category' => $category,
        ]);
    }

    /**
     * Mettre à jour une catégorie
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'color' => 'nullable|string|size:7',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'],
            'icon' => $validated['icon'],
            'is_active' => $validated['is_active'],
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Catégorie mise à jour avec succès');
    }

    /**
     * Supprimer une catégorie
     */
    public function destroy(Category $category)
    {
        // Vérifier s'il y a des questions associées
        if ($category->questions()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer cette catégorie car elle contient des questions.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Catégorie supprimée avec succès');
    }
}
