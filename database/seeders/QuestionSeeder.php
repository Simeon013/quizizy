<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Category;
use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();
        
        // Questions pour chaque catégorie
        $questions = [
            // Géographie
            'Géographie' => [
                [
                    'question_text' => 'Quelle est la capitale de la France ?',
                    'difficulty' => 'easy',
                    'answers' => [
                        ['answer_text' => 'Paris', 'is_correct' => true],
                        ['answer_text' => 'Lyon', 'is_correct' => false],
                        ['answer_text' => 'Marseille', 'is_correct' => false],
                        ['answer_text' => 'Bordeaux', 'is_correct' => false],
                    ],
                ],
                [
                    'question_text' => 'Quel est le plus grand océan du monde ?',
                    'difficulty' => 'easy',
                    'answers' => [
                        ['answer_text' => 'Océan Atlantique', 'is_correct' => false],
                        ['answer_text' => 'Océan Pacifique', 'is_correct' => true],
                        ['answer_text' => 'Océan Indien', 'is_correct' => false],
                        ['answer_text' => 'Océan Arctique', 'is_correct' => false],
                    ],
                ],
            ],
            // Histoire
            'Histoire' => [
                [
                    'question_text' => 'En quelle année a eu lieu la Révolution française ?',
                    'difficulty' => 'medium',
                    'answers' => [
                        ['answer_text' => '1776', 'is_correct' => false],
                        ['answer_text' => '1789', 'is_correct' => true],
                        ['answer_text' => '1799', 'is_correct' => false],
                        ['answer_text' => '1815', 'is_correct' => false],
                    ],
                ],
            ],
            // Culture Générale
            'Culture Générale' => [
                [
                    'question_text' => 'Combien de jours compte une année bissextile ?',
                    'difficulty' => 'easy',
                    'answers' => [
                        ['answer_text' => '364', 'is_correct' => false],
                        ['answer_text' => '365', 'is_correct' => false],
                        ['answer_text' => '366', 'is_correct' => true],
                        ['answer_text' => '367', 'is_correct' => false],
                    ],
                ],
            ],
            // Sciences
            'Sciences' => [
                [
                    'question_text' => 'Quel est l\'élément chimique dont le symbole est "O" ?',
                    'difficulty' => 'easy',
                    'answers' => [
                        ['answer_text' => 'Or', 'is_correct' => false],
                        ['answer_text' => 'Osmium', 'is_correct' => false],
                        ['answer_text' => 'Oxygène', 'is_correct' => true],
                        ['answer_text' => 'Oganesson', 'is_correct' => false],
                    ],
                ],
            ],
            // Sports
            'Sports' => [
                [
                    'question_text' => 'Combien de joueurs composent une équipe de football sur le terrain ?',
                    'difficulty' => 'easy',
                    'answers' => [
                        ['answer_text' => '10', 'is_correct' => false],
                        ['answer_text' => '11', 'is_correct' => true],
                        ['answer_text' => '12', 'is_correct' => false],
                        ['answer_text' => '9', 'is_correct' => false],
                    ],
                ],
            ],
            // Cinéma
            'Cinéma' => [
                [
                    'question_text' => 'Qui a réalisé le film "Inception" ?',
                    'difficulty' => 'medium',
                    'answers' => [
                        ['answer_text' => 'Steven Spielberg', 'is_correct' => false],
                        ['answer_text' => 'Christopher Nolan', 'is_correct' => true],
                        ['answer_text' => 'James Cameron', 'is_correct' => false],
                        ['answer_text' => 'Quentin Tarantino', 'is_correct' => false],
                    ],
                ],
            ],
            // Musique
            'Musique' => [
                [
                    'question_text' => 'Quel groupe britannique était également connu sous le nom de "Fab Four" ?',
                    'difficulty' => 'easy',
                    'answers' => [
                        ['answer_text' => 'The Rolling Stones', 'is_correct' => false],
                        ['answer_text' => 'The Beatles', 'is_correct' => true],
                        ['answer_text' => 'Led Zeppelin', 'is_correct' => false],
                        ['answer_text' => 'Queen', 'is_correct' => false],
                    ],
                ],
            ],
            // Technologie
            'Technologie' => [
                [
                    'question_text' => 'Quelle entreprise a développé le système d\'exploitation Windows ?',
                    'difficulty' => 'easy',
                    'answers' => [
                        ['answer_text' => 'Apple', 'is_correct' => false],
                        ['answer_text' => 'Microsoft', 'is_correct' => true],
                        ['answer_text' => 'Google', 'is_correct' => false],
                        ['answer_text' => 'IBM', 'is_correct' => false],
                    ],
                ],
            ],
        ];

        foreach ($categories as $category) {
            if (isset($questions[$category->name])) {
                foreach ($questions[$category->name] as $questionData) {
                    $question = Question::create([
                        'category_id' => $category->id,
                        'question_text' => $questionData['question_text'],
                        'difficulty' => $questionData['difficulty'],
                        'is_active' => true,
                    ]);

                    foreach ($questionData['answers'] as $answerData) {
                        Answer::create([
                            'question_id' => $question->id,
                            'answer_text' => $answerData['answer_text'],
                            'is_correct' => $answerData['is_correct'],
                        ]);
                    }
                }
            }
        }

        $this->command->info('Questions et réponses d\'exemple créées avec succès !');
    }
}
