<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Services proposés, regroupés par catégorie (codex §9).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 170)->unique();
            $table->string('summary', 255)->nullable();
            $table->text('description')->nullable();
            // Nom d'une icône du jeu interne (aucune police d'icônes externe).
            $table->string('icon', 60)->nullable();
            $table->string('status', 20)->default('published');
            $table->unsignedInteger('position')->default(0);
            $table->string('meta_title', 180)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'position']);
        });

        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_category_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('slug', 200)->unique();
            // Résumé en langage simple, affiché sur les cartes de la page d'accueil.
            $table->string('summary', 255);
            $table->text('description')->nullable();
            $table->json('learning_points')->nullable();
            $table->string('icon', 60)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->string('image_alt', 255)->nullable();
            $table->string('status', 20)->default('published');
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->unsignedSmallInteger('estimated_duration_minutes')->nullable();
            $table->string('level', 20)->default('everyone');
            $table->string('meta_title', 180)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'position']);
            $table->index(['is_featured', 'status']);
        });

        Schema::create('pricings', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 180);
            $table->string('slug', 200)->unique();
            $table->string('model', 40)->default('hourly');
            // Montant en centimes pour éviter toute imprécision décimale.
            $table->unsignedInteger('amount_cents')->nullable();
            $table->string('unit', 80)->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->text('description')->nullable();
            $table->json('includes')->nullable();
            $table->text('travel_costs')->nullable();
            $table->text('payment_methods')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->boolean('is_quote_only')->default(false);
            $table->boolean('is_highlighted')->default(false);
            $table->string('status', 20)->default('published');
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricings');
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_categories');
    }
};
