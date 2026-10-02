<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * L'atelier : les quiz écrits par les joueurs, avec l'aide de l'IA.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz', function (Blueprint $table) {
            // Au catalogue public (intercalaires). Un quiz d'atelier publié se joue
            // par son lien ; seul un administrateur le met au catalogue.
            $table->boolean('au_catalogue')->default(false)->after('publie');
        });
        // Le contenu existant est celui du catalogue.
        DB::table('quiz')->whereNull('auteur_id')->update(['au_catalogue' => true]);

        Schema::table('questions', function (Blueprint $table) {
            // Faux = encore « au crayon » : proposée (par l'IA) et pas encore relue.
            // Un quiz ne se publie que quand toutes ses questions sont à l'encre.
            $table->boolean('a_l_encre')->default(true)->after('indice');
        });

        Schema::create('generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->nullable()->constrained('quiz')->nullOnDelete();
            $table->string('modele', 60);
            // reussie | refusee | echouee
            $table->string('statut', 12);
            $table->unsignedSmallInteger('questions')->default(0);
            $table->unsignedInteger('jetons_entree')->default(0);
            $table->unsignedInteger('jetons_sortie')->default(0);
            $table->string('erreur', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generations');
        Schema::table('questions', fn (Blueprint $t) => $t->dropColumn('a_l_encre'));
        Schema::table('quiz', fn (Blueprint $t) => $t->dropColumn('au_catalogue'));
    }
};
