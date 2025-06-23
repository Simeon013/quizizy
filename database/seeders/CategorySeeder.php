<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Géographie',
                'description' => 'Testez vos connaissances sur les pays, les capitales et les drapeaux du monde entier.',
                'color' => '#3B82F6', // Bleu
                'icon' => 'globe-europe',
            ],
            [
                'name' => 'Histoire',
                'description' => 'Découvrez des faits historiques et des événements marquants à travers les âges.',
                'color' => '#EF4444', // Rouge
                'icon' => 'landmark',
            ],
            [
                'name' => 'Culture Générale',
                'description' => 'Un mélange de questions variées pour tester votre culture générale.',
                'color' => '#10B981', // Vert
                'icon' => 'lightbulb',
            ],
            [
                'name' => 'Sciences',
                'description' => 'Explorez les mystères de la physique, de la chimie et de la biologie.',
                'color' => '#8B5CF6', // Violet
                'icon' => 'flask',
            ],
            [
                'name' => 'Sports',
                'description' => 'Pour les passionnés de sport, testez vos connaissances sur les athlètes et les compétitions.',
                'color' => '#F59E0B', // Jaune
                'icon' => 'trophy',
            ],
            [
                'name' => 'Cinéma',
                'description' => 'Des classiques aux sorties récentes, saurez-vous reconnaître ces films et acteurs ?',
                'color' => '#EC4899', // Rose
                'icon' => 'film',
            ],
            [
                'name' => 'Musique',
                'description' => 'Reconnaîtrez-vous ces titres, artistes et albums qui ont marqué l\'histoire de la musique ?',
                'color' => '#06B6D4', // Cyan
                'icon' => 'music',
            ],
            [
                'name' => 'Technologie',
                'description' => 'Informatique, nouvelles technologies et innovations numériques n\'auront plus de secrets pour vous.',
                'color' => '#6B7280', // Gris
                'icon' => 'laptop-code',
            ],
        ];


        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('Catégories par défaut créées avec succès !');
    }
}
