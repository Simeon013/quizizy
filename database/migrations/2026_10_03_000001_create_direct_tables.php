<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le mode en direct : une salle animée sur grand écran, des joueurs qui
 * répondent depuis leur téléphone avec un code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // Code à 6 chiffres tapé par les joueurs ; unique parmi les salles ouvertes seulement.
            $table->string('code', 6)->index();
            $table->foreignId('quiz_id')->constrained('quiz')->cascadeOnDelete();
            $table->foreignId('animateur_id')->constrained('users')->cascadeOnDelete();
            // attente | question | correction | terminee
            $table->string('etat', 12)->default('attente');
            $table->json('questions');
            $table->unsignedSmallInteger('secondes_par_question');
            // -1 tant que la première question n'est pas lancée.
            $table->smallInteger('position')->default(-1);
            $table->timestamp('question_debut_le')->nullable();
            $table->timestamp('question_fin_le')->nullable();
            // Augmente à chaque changement : les écrans ne retéléchargent l'état que s'il a bougé.
            $table->unsignedInteger('version')->default(0);
            $table->timestamp('terminee_le')->nullable();
            $table->timestamps();
        });

        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('salle_id')->constrained('salles')->cascadeOnDelete();
            $table->string('pseudo', 20);
            $table->unsignedInteger('points')->default(0);
            $table->unsignedSmallInteger('bonnes')->default(0);
            $table->unsignedSmallInteger('serie')->default(0);
            // Retiré par l'animateur (pseudo déplacé) : n'apparaît plus nulle part.
            $table->timestamp('retire_le')->nullable();
            $table->timestamps();
        });

        Schema::create('reponses_direct', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('salle_id')->constrained('salles')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->foreignId('choix_id')->nullable()->constrained('choix')->nullOnDelete();
            $table->boolean('juste');
            $table->unsignedSmallInteger('points')->default(0);
            $table->unsignedInteger('duree_ms');
            $table->timestamp('created_at')->nullable();

            $table->unique(['participant_id', 'question_id']);
            $table->index(['salle_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reponses_direct');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('salles');
    }
};
