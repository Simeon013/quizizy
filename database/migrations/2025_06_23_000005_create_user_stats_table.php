<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->integer('total_questions_answered')->default(0);
            $table->integer('correct_answers')->default(0);
            $table->integer('xp')->default(0);
            $table->integer('level')->default(1);
            $table->timestamps();
            
            // Un utilisateur ne peut avoir qu'une seule entrée par catégorie
            $table->unique(['user_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_stats');
    }
};
