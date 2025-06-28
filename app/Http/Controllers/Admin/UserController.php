<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Affiche la liste des utilisateurs
     */
    public function index()
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => \App\Models\User::paginate(10)
        ]);
    }
}
