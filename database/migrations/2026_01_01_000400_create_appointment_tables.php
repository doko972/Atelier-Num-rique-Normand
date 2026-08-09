<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demandes de rendez-vous et suivi administratif (codex §11).
 *
 * Aucune donnée sensible n'est stockée : ni mot de passe, ni identifiant de
 * service en ligne, ni pièce d'identité (codex §26).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique();

            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 30);
            $table->string('email', 180)->nullable();

            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->string('municipality_name', 150)->nullable();

            $table->string('type', 40)->default('individual');
            $table->text('need_description');
            $table->string('device', 20)->nullable();
            $table->text('availability')->nullable();
            $table->string('contact_preference', 20)->default('phone');
            $table->boolean('home_visit_requested')->default(false);
            $table->boolean('has_mobility_difficulty')->default(false);
            $table->boolean('voice_message_allowed')->default(false);

            $table->string('status', 30)->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('callback_on')->nullable();
            $table->dateTime('scheduled_for')->nullable();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();

            $table->boolean('consent_given')->default(false);
            $table->timestamp('consent_given_at')->nullable();

            $table->timestamp('confirmation_sent_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            // Une demande anonymisée conserve ses statistiques mais plus
            // aucune donnée permettant d'identifier la personne.
            $table->timestamp('anonymised_at')->nullable();

            $table->string('source', 30)->default('website');
            $table->string('ip_hash', 64)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index(['assigned_to', 'status']);
            $table->index('callback_on');
            $table->index('phone');
        });

        Schema::create('appointment_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            // Une note système retrace un changement de statut automatique.
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index(['appointment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_notes');
        Schema::dropIfExists('appointments');
    }
};
