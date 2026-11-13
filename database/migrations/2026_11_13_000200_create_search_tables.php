<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recherche du site public : dictionnaire de synonymes et relevé des
 * recherches restées sans réponse.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Les visiteurs écrivent « mail », « ordi », « arnaque » ; les
        // contenus disent « courriel », « ordinateur », « hameçonnage ».
        // Chaque ligne regroupe des mots tenus pour équivalents.
        Schema::create('search_synonyms', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 100);
            $table->json('words');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Agrégé par texte recherché, sans adresse IP ni lien avec une
        // personne : seul compte le mot qui n'a rien donné.
        Schema::create('missed_searches', function (Blueprint $table): void {
            $table->id();
            $table->string('query', 100)->unique();
            $table->unsignedInteger('hits')->default(1);
            $table->timestamp('last_searched_at');
            $table->timestamps();

            $table->index('last_searched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missed_searches');
        Schema::dropIfExists('search_synonyms');
    }
};
