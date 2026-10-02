<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le jeu : une partie (une copie), ses réponses, et les gommettes gagnées.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            // ULID : l'adresse d'une partie ne se devine pas en incrémentant un numéro.
            $table->ulid('id')->primary();
            // Nul pour un invité : la partie est alors rattachée à sa session.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            // Nul pour une révision (« À revoir »), qui pioche dans plusieurs quiz.
            $table->foreignId('quiz_id')->nullable()->constrained('quiz')->nullOnDelete();
            $table->string('type', 12)->default('quiz');
            // Identifiants des questions tirées, dans l'ordre de la partie.
            $table->json('questions');
            $table->unsignedSmallInteger('secondes_par_question');
            $table->unsignedSmallInteger('position')->default(0);
            // Moment où la question en cours a été affichée : le chrono se mesure côté serveur.
            $table->timestamp('question_affichee_le')->nullable();
            // L'indice de la question en cours a-t-il été décollé ?
            $table->boolean('indice_pris')->default(false);
            $table->unsignedInteger('points')->default(0);
            $table->unsignedSmallInteger('bonnes')->default(0);
            $table->unsignedSmallInteger('serie')->default(0);
            $table->unsignedSmallInteger('serie_max')->default(0);
            $table->timestamp('terminee_le')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'terminee_le']);
        });

        Schema::create('reponses', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('partie_id')->constrained('parties')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            // Nul quand le temps est écoulé sans réponse.
            $table->foreignId('choix_id')->nullable()->constrained('choix')->nullOnDelete();
            $table->boolean('juste');
            $table->boolean('indice')->default(false);
            $table->unsignedSmallInteger('points')->default(0);
            $table->unsignedInteger('duree_ms');
            $table->timestamp('created_at')->nullable();

            // Une seule réponse par question et par partie : on ne rejoue pas une question.
            $table->unique(['partie_id', 'question_id']);
        });

        Schema::create('gommettes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Une clé de App\Support\Gommettes.
            $table->string('cle', 40);
            $table->foreignUlid('partie_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->timestamp('obtenue_le');

            $table->unique(['user_id', 'cle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gommettes');
        Schema::dropIfExists('reponses');
        Schema::dropIfExists('parties');
    }
};
