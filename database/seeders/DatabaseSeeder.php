<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ContenuSeeder::class);

        // Compte de démonstration : jamais en production.
        if (! app()->isProduction()) {
            User::firstOrCreate(['email' => 'demo@eureka.test'], ['name' => 'Awa', 'password' => 'password']);
        }
    }
}
