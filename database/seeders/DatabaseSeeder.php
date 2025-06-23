<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Création de l'administrateur
        User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
            // 'is_admin' => true,
            // 'total_xp' => 0,
            // 'level' => 1,
        ]);

        // Exécution des seeders
        $this->call([
            CategorySeeder::class,
            QuestionSeeder::class,
        ]);
    }
}
