<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messages reçus depuis le site et demandes de partenariat (codex §15).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 180)->nullable();
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject', 200);
            $table->text('message');
            $table->string('contact_preference', 20)->default('phone');
            $table->boolean('voice_message_allowed')->default(false);

            $table->string('status', 30)->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('anonymised_at')->nullable();

            $table->boolean('consent_given')->default(false);
            $table->timestamp('consent_given_at')->nullable();
            $table->string('ip_hash', 64)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });

        Schema::create('partnership_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique();

            $table->string('organisation_name', 200);
            $table->string('organisation_type', 40)->default('other');
            $table->string('contact_name', 150);
            $table->string('contact_role', 150)->nullable();
            $table->string('email', 180);
            $table->string('phone', 30)->nullable();
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->string('municipality_name', 150)->nullable();

            $table->json('needs')->nullable();
            $table->string('audience', 200)->nullable();
            $table->unsignedSmallInteger('estimated_participants')->nullable();
            $table->string('desired_period', 150)->nullable();
            $table->text('message')->nullable();
            $table->boolean('quote_requested')->default(false);

            $table->string('status', 30)->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('anonymised_at')->nullable();

            $table->boolean('consent_given')->default(false);
            $table->timestamp('consent_given_at')->nullable();
            $table->string('ip_hash', 64)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partnership_requests');
        Schema::dropIfExists('contact_requests');
    }
};
