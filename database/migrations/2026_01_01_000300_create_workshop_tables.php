<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ateliers collectifs et inscriptions (codex §10).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 170)->unique();
            $table->string('summary', 255)->nullable();
            $table->string('icon', 60)->nullable();
            $table->string('status', 20)->default('published');
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'position']);
        });

        Schema::create('workshops', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->text('description');
            $table->json('objectives')->nullable();
            $table->text('prerequisites')->nullable();
            $table->string('level', 20)->default('everyone');

            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->date('registration_deadline')->nullable();

            $table->unsignedSmallInteger('capacity')->default(8);
            // Le nombre de places restantes est toujours recalculé à partir des
            // inscriptions : cette colonne n'existe pas, afin d'éviter toute
            // désynchronisation. Voir Workshop::remainingSeats().
            $table->boolean('waiting_list_enabled')->default(true);

            $table->boolean('is_accessible')->default(false);
            $table->boolean('equipment_provided')->default(true);
            $table->boolean('own_device_allowed')->default(true);
            $table->boolean('is_free')->default(true);
            $table->unsignedInteger('price_cents')->nullable();

            $table->string('instructor_name', 150)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->string('image_alt', 255)->nullable();

            $table->string('status', 20)->default('draft');
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->string('meta_title', 180)->nullable();
            $table->string('meta_description', 255)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'date']);
            $table->index(['date', 'start_time']);
        });

        Schema::create('workshop_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();

            // Référence courte et lisible, communicable par téléphone.
            $table->string('reference', 20)->unique();

            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 30);
            // L'adresse électronique est facultative : une personne sans email
            // doit pouvoir s'inscrire (critère d'acceptation du codex §46).
            $table->string('email', 180)->nullable();
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->string('municipality_name', 150)->nullable();
            $table->string('age_range', 20)->nullable();
            $table->string('device', 20)->nullable();
            $table->text('special_needs')->nullable();

            $table->string('status', 25)->default('pending');
            // Position dans la liste d'attente ; nulle si la place est acquise.
            $table->unsignedSmallInteger('waiting_position')->nullable();

            // Inscription enregistrée par téléphone au guichet.
            $table->boolean('registered_by_phone')->default(false);
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('consent_given')->default(false);
            $table->timestamp('consent_given_at')->nullable();
            $table->boolean('voice_message_allowed')->default(false);

            $table->timestamp('confirmation_sent_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('anonymised_at')->nullable();

            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workshop_id', 'status']);
            $table->index('phone');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_registrations');
        Schema::dropIfExists('workshops');
        Schema::dropIfExists('workshop_categories');
    }
};
