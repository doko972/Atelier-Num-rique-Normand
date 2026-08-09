<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\ContactPreference;
use App\Enums\DataRequestStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\DataDeletionRequest;
use App\Services\GdprService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Exercice des droits RGPD (codex §27 et §46).
 */
class GdprTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    protected function createAppointment(string $email = 'odette@example.test'): Appointment
    {
        $appointment = Appointment::create([
            'first_name' => 'Odette',
            'last_name' => 'Durand',
            'phone' => '0612345678',
            'email' => $email,
            'type' => AppointmentType::Individual,
            'need_description' => 'Difficulté avec ma boîte aux lettres électronique.',
            'contact_preference' => ContactPreference::Phone,
            'status' => AppointmentStatus::Done,
        ]);

        $appointment->forceFill(['closed_at' => now()->subYears(4)])->save();

        return $appointment->refresh();
    }

    #[Test]
    public function l_anonymisation_efface_les_donnees_personnelles_sans_supprimer_la_ligne(): void
    {
        $appointment = $this->createAppointment();

        $this->assertTrue($appointment->anonymise());

        $appointment->refresh();

        $this->assertNotNull($appointment->anonymised_at);
        $this->assertSame(__('rgpd.anonymised_first_name'), $appointment->first_name);
        $this->assertSame('', $appointment->last_name);
        $this->assertSame('', $appointment->phone);
        $this->assertNull($appointment->email);
        $this->assertSame(__('rgpd.anonymised_content'), $appointment->need_description);

        // La ligne demeure : les statistiques agrégées restent exactes.
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
        $this->assertSame(AppointmentStatus::Done, $appointment->status);
    }

    #[Test]
    public function l_anonymisation_est_idempotente(): void
    {
        $appointment = $this->createAppointment();

        $this->assertTrue($appointment->anonymise());
        $this->assertFalse($appointment->anonymise());
    }

    #[Test]
    public function une_recherche_sans_critere_ne_renvoie_rien(): void
    {
        $this->createAppointment();

        $records = app(GdprService::class)->findRecords(null, null);

        $this->assertCount(0, $records['appointments']);
        $this->assertCount(0, $records['registrations']);
        $this->assertCount(0, $records['contacts']);
    }

    #[Test]
    public function la_recherche_retrouve_les_enregistrements_par_adresse(): void
    {
        $this->createAppointment('cherchee@example.test');

        $records = app(GdprService::class)->findRecords('cherchee@example.test', null);

        $this->assertCount(1, $records['appointments']);
    }

    #[Test]
    public function une_demande_d_effacement_anonymise_les_enregistrements(): void
    {
        $this->createAppointment('a-effacer@example.test');

        $request = DataDeletionRequest::create([
            'requester_name' => 'Odette Durand',
            'requester_email' => 'a-effacer@example.test',
            'scope' => DataDeletionRequest::SCOPE_ALL,
            'status' => DataRequestStatus::InProgress,
        ]);

        $count = app(GdprService::class)->anonymise($request);

        $this->assertSame(1, $count);
        $this->assertSame(
            __('rgpd.anonymised_first_name'),
            Appointment::query()->where('email', 'a-effacer@example.test')->count() === 0
                ? Appointment::query()->latest('id')->value('first_name')
                : 'non anonymisé',
        );
    }

    #[Test]
    public function la_purge_automatique_traite_les_demandes_hors_delai(): void
    {
        $this->createAppointment('expiree@example.test');

        $summary = app(GdprService::class)->purgeExpiredRecords();

        $this->assertGreaterThanOrEqual(1, $summary['appointments']);
        $this->assertNull(
            Appointment::query()->where('email', 'expiree@example.test')->first(),
            'La demande expirée ne doit plus être retrouvable par son adresse.',
        );
    }

    #[Test]
    public function une_demande_recente_n_est_pas_purgee(): void
    {
        $appointment = Appointment::create([
            'first_name' => 'Récente',
            'last_name' => 'Demande',
            'phone' => '0612345679',
            'email' => 'recente@example.test',
            'type' => AppointmentType::Individual,
            'need_description' => 'Demande close hier seulement.',
            'contact_preference' => ContactPreference::Phone,
            'status' => AppointmentStatus::Done,
        ]);

        $appointment->forceFill(['closed_at' => now()->subDay()])->save();

        app(GdprService::class)->purgeExpiredRecords();

        $this->assertNull($appointment->fresh()->anonymised_at);
    }

    #[Test]
    public function la_reference_d_une_demande_rgpd_porte_une_echeance_d_un_mois(): void
    {
        $request = DataDeletionRequest::create([
            'requester_name' => 'Odette Durand',
            'requester_phone' => '0612345678',
            'scope' => DataDeletionRequest::SCOPE_ALL,
        ]);

        $this->assertNotNull($request->due_on);
        $this->assertSame(
            now()->addDays(DataDeletionRequest::RESPONSE_DEADLINE_DAYS)->toDateString(),
            $request->due_on->toDateString(),
        );
    }

    #[Test]
    public function l_execution_est_refusee_sans_verification_d_identite(): void
    {
        $request = DataDeletionRequest::create([
            'requester_name' => 'Odette Durand',
            'requester_email' => 'odette@example.test',
            'scope' => DataDeletionRequest::SCOPE_ALL,
        ]);

        $this->actingAs($this->userWithRole(UserRole::SuperAdmin))
            ->post(route('admin.gdpr.deletions.execute', $request))
            ->assertForbidden();
    }

    #[Test]
    public function l_execution_est_possible_apres_verification_d_identite(): void
    {
        $this->createAppointment('a-effacer@example.test');

        $request = DataDeletionRequest::create([
            'requester_name' => 'Odette Durand',
            'requester_email' => 'a-effacer@example.test',
            'scope' => DataDeletionRequest::SCOPE_ALL,
        ]);

        $request->forceFill([
            'identity_verified' => true,
            'identity_verified_at' => now(),
        ])->save();

        $this->actingAs($this->userWithRole(UserRole::SuperAdmin))
            ->post(route('admin.gdpr.deletions.execute', $request))
            ->assertRedirect();

        $this->assertSame(DataRequestStatus::Completed, $request->fresh()->status);
        $this->assertSame(1, $request->fresh()->records_anonymised);
    }

    #[Test]
    public function un_conseiller_ne_peut_pas_traiter_les_demandes_rgpd(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Adviser))
            ->get(route('admin.gdpr.index'))
            ->assertForbidden();
    }
}
