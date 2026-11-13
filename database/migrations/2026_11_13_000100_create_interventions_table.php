<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Registre des prestations réalisées, et éligibilité SAP des tarifs.
 *
 * Le registre sert la comptabilité séparée qu'exige la dispense
 * d'exclusivité SAP : chiffre d'affaires SAP d'un côté, hors SAP de l'autre,
 * le second plafonné à 30 %. Voir docs/tarifs-et-services-a-la-personne.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interventions', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->date('performed_on');
            $table->string('activity', 40);

            // Le nom est recopié, pas seulement lu sur le rendez-vous : une
            // pièce comptable se conserve dix ans, bien après que la demande
            // de rendez-vous a été anonymisée ou effacée.
            $table->string('client_name', 150);
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedSmallInteger('duration_minutes');
            // Montant en centimes, comme les tarifs.
            $table->unsignedInteger('amount_cents');
            $table->string('invoice_number', 50)->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['performed_on', 'activity']);
        });

        Schema::table('pricings', function (Blueprint $table): void {
            $table->boolean('sap_eligible')->default(false)->after('is_highlighted');
        });

        // Les tarifs d'accompagnement individuel sont ceux qui se pratiquent
        // au domicile. Posé ici plutôt que par le semoir : celui-ci ne touche
        // plus aux tarifs déjà retouchés depuis le back-office.
        DB::table('pricings')
            ->whereIn('slug', ['accompagnement-individuel', 'forfait-decouverte', 'forfait-cinq-seances'])
            ->update(['sap_eligible' => true]);
    }

    public function down(): void
    {
        Schema::table('pricings', function (Blueprint $table): void {
            $table->dropColumn('sap_eligible');
        });

        Schema::dropIfExists('interventions');
    }
};
