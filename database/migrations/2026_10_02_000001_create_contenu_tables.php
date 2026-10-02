<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le contenu du cahier : matières (les intercalaires), quiz, questions et choix.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matieres', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 60);
            $table->string('slug', 80)->unique();
            $table->string('description', 255)->nullable();
            // Une clé de App\Support\Onglets, jamais une couleur libre.
            $table->string('onglet', 20);
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();
        });

        Schema::create('quiz', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matiere_id')->constrained('matieres')->cascadeOnDelete();
            $table->foreignId('auteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titre', 120);
            $table->string('slug', 140)->unique();
            $table->string('description', 255)->nullable();
            $table->unsignedSmallInteger('secondes_par_question')->default(30);
            $table->boolean('publie')->default(true);
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quiz')->cascadeOnDelete();
            $table->text('enonce');
            // L'explication est la note du stylo vert : toujours présente.
            $table->text('explication');
            $table->string('indice', 255)->nullable();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();
        });

        Schema::create('choix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->string('texte', 255);
            $table->boolean('juste')->default(false);
            $table->unsignedSmallInteger('ordre')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('choix');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('quiz');
        Schema::dropIfExists('matieres');
    }
};
