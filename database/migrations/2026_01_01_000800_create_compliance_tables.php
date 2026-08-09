<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conformité : registre des consentements, journal d'audit, accessibilité et
 * demandes RGPD (codex §26, §27, §43).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_logs', function (Blueprint $table): void {
            $table->id();
            // Enregistrement rattaché à la demande concernée, quel que soit
            // son type (rendez-vous, inscription, contact, partenariat).
            $table->nullableMorphs('consentable');
            $table->string('purpose', 60);
            // Texte exact de la case cochée, conservé comme preuve.
            $table->text('statement');
            $table->string('version', 20)->default('1.0');
            $table->boolean('granted')->default(true);
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();
            // L'adresse IP n'est jamais stockée en clair : seul un condensé
            // salé permet de détecter des envois répétés.
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['purpose', 'granted_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_label', 150)->nullable();
            $table->string('action', 80);
            $table->nullableMorphs('auditable');
            $table->string('subject_label', 200)->nullable();
            // Valeurs avant / après, sans jamais inclure de mot de passe ni de
            // champ marqué comme sensible (voir Auditable::auditExcluded()).
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('channel', 40)->default('admin');
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['action', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('accessibility_reports', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 200);
            $table->date('audited_on');
            $table->string('referential', 60)->default('RGAA 4.1');
            // Taux de conformité en pourcentage, avec une décimale.
            $table->decimal('compliance_rate', 5, 2)->nullable();
            $table->string('level', 20)->default('partial');
            $table->text('summary')->nullable();
            $table->json('non_conformities')->nullable();
            $table->text('improvement_plan')->nullable();
            $table->string('auditor', 150)->nullable();
            $table->string('report_path', 255)->nullable();
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('audited_on');
        });

        Schema::create('data_export_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('type', 40)->default('access');
            $table->string('requester_name', 150);
            $table->string('requester_email', 180)->nullable();
            $table->string('requester_phone', 30)->nullable();
            $table->text('details')->nullable();
            $table->string('status', 30)->default('received');
            // Vérification d'identité obligatoire avant toute communication.
            $table->boolean('identity_verified')->default(false);
            $table->timestamp('identity_verified_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('export_path', 255)->nullable();
            // L'archive produite est automatiquement supprimée à cette date.
            $table->timestamp('export_expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            // Délai légal de réponse : un mois à compter de la réception.
            $table->date('due_on')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_on']);
        });

        Schema::create('data_deletion_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('requester_name', 150);
            $table->string('requester_email', 180)->nullable();
            $table->string('requester_phone', 30)->nullable();
            $table->text('details')->nullable();
            // Périmètre : all | appointments | registrations | contacts
            $table->string('scope', 40)->default('all');
            $table->string('status', 30)->default('received');
            $table->boolean('identity_verified')->default(false);
            $table->timestamp('identity_verified_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('records_anonymised')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->date('due_on')->nullable();
            $table->text('refusal_reason')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_deletion_requests');
        Schema::dropIfExists('data_export_requests');
        Schema::dropIfExists('accessibility_reports');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('consent_logs');
    }
};
