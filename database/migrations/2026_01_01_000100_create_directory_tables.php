<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Territoire d'intervention : communes, lieux d'accueil et partenaires.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipalities', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 170)->unique();
            $table->string('postal_code', 10);
            // Code officiel géographique de l'INSEE, utile pour les bilans.
            $table->string('insee_code', 10)->nullable()->unique();
            $table->string('department', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            // Distance depuis la commune de rattachement, en kilomètres :
            // sert à déterminer d'éventuels frais de déplacement.
            $table->unsignedSmallInteger('distance_km')->nullable();
            $table->boolean('is_covered')->default(true);
            $table->boolean('home_visits_available')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_covered', 'position']);
            $table->index('postal_code');
        });

        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 180);
            $table->string('slug', 200)->unique();
            $table->string('address_line', 255)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            // Accessibilité aux personnes à mobilité réduite (question fréquente).
            $table->boolean('is_accessible')->default(false);
            $table->text('accessibility_details')->nullable();
            $table->text('access_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'municipality_id']);
        });

        Schema::create('partners', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 180);
            $table->string('slug', 200)->unique();
            $table->string('type', 40)->default('other');
            $table->string('logo_path', 255)->nullable();
            // Texte alternatif du logo : obligatoire pour l'accessibilité.
            $table->string('logo_alt', 255)->nullable();
            $table->string('website', 255)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->date('partnership_started_on')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_published', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('municipalities');
    }
};
