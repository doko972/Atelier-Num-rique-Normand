<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\ContactPreference;
use App\Enums\ContentStatus;
use App\Enums\PartnerType;
use App\Enums\RegistrationStatus;
use App\Enums\SkillLevel;
use App\Enums\UserRole;
use App\Enums\WorkshopStatus;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ContactRequest;
use App\Models\Page;
use App\Models\PartnershipRequest;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use App\Notifications\WorkshopCancelledNotification;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Opérations quotidiennes du back-office (codex §24 et §46).
 */
class AdminOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        // Les ateliers d'exemple vivent dans le jeu de demonstration : le
        // socle n'en contient plus, pour qu'aucun atelier fictif ne puisse
        // se retrouver publie sur un site en service.
        $this->seed(DemoSeeder::class);
        Notification::fake();
    }

    protected function appointment(): Appointment
    {
        return Appointment::create([
            'first_name' => 'Odette',
            'last_name' => 'Durand',
            'phone' => '0612345678',
            'type' => AppointmentType::Individual,
            'need_description' => 'Difficulté avec ma tablette.',
            'contact_preference' => ContactPreference::Phone,
            'status' => AppointmentStatus::New,
        ]);
    }

    // -------------------------------------------------------------------------
    // Demandes de rendez-vous
    // -------------------------------------------------------------------------

    #[Test]
    public function un_conseiller_peut_faire_evoluer_une_demande(): void
    {
        $appointment = $this->appointment();

        $this->actingAs($this->userWithRole(UserRole::Adviser))
            ->put(route('admin.appointments.update', $appointment), [
                'status' => AppointmentStatus::ToCall->value,
                'callback_on' => today()->addDay()->toDateString(),
            ])
            ->assertRedirect();

        $appointment->refresh();

        $this->assertSame(AppointmentStatus::ToCall, $appointment->status);
        $this->assertNotNull($appointment->callback_on);

        // Un changement de statut laisse toujours une trace lisible.
        $this->assertDatabaseHas('appointment_notes', [
            'appointment_id' => $appointment->id,
            'is_system' => true,
        ]);
    }

    #[Test]
    public function une_transition_interdite_est_refusee(): void
    {
        $appointment = $this->appointment();

        $this->actingAs($this->userWithRole(UserRole::Adviser))
            ->put(route('admin.appointments.update', $appointment), [
                'status' => AppointmentStatus::Done->value,
            ])
            ->assertSessionHas('status_variant', 'danger');

        $this->assertSame(AppointmentStatus::New, $appointment->fresh()->status);
    }

    #[Test]
    public function un_conseiller_peut_ajouter_une_note_interne(): void
    {
        $appointment = $this->appointment();

        $this->actingAs($this->userWithRole(UserRole::Adviser))
            ->post(route('admin.appointments.note', $appointment), [
                'body' => 'Rappelée le 12, absente. À rappeler en fin de semaine.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('appointment_notes', [
            'appointment_id' => $appointment->id,
            'is_system' => false,
        ]);
    }

    // -------------------------------------------------------------------------
    // Inscription par téléphone
    // -------------------------------------------------------------------------

    #[Test]
    public function une_inscription_recue_par_telephone_peut_etre_enregistree(): void
    {
        $workshop = Workshop::query()
            ->where('status', WorkshopStatus::Published)
            ->whereDate('date', '>=', today())
            ->firstOrFail();

        $this->actingAs($this->userWithRole(UserRole::Adviser))
            ->post(route('admin.registrations.store', $workshop), [
                'first_name' => 'Ambroise',
                'last_name' => 'Testeur',
                'phone' => '06 55 44 33 22',
                'consent_confirmed' => '1',
            ])
            ->assertRedirect(route('admin.workshops.participants', $workshop));

        $registration = WorkshopRegistration::query()
            ->where('first_name', 'Ambroise')
            ->firstOrFail();

        $this->assertTrue($registration->registered_by_phone);
        $this->assertNull($registration->email);
        $this->assertSame(RegistrationStatus::Pending, $registration->status);
    }

    #[Test]
    public function l_inscription_manuelle_exige_la_confirmation_du_consentement(): void
    {
        $workshop = Workshop::query()->whereDate('date', '>=', today())->firstOrFail();

        $this->actingAs($this->userWithRole(UserRole::Adviser))
            ->post(route('admin.registrations.store', $workshop), [
                'first_name' => 'Ambroise',
                'last_name' => 'Testeur',
                'phone' => '06 55 44 33 22',
            ])
            ->assertSessionHasErrors('consent_confirmed');
    }

    // -------------------------------------------------------------------------
    // Annulation d'atelier
    // -------------------------------------------------------------------------

    #[Test]
    public function l_annulation_d_un_atelier_previent_les_personnes_inscrites(): void
    {
        $workshop = Workshop::query()
            ->whereDate('date', '>=', today())
            ->whereHas('registrations')
            ->firstOrFail();

        $this->actingAs($this->userWithRole(UserRole::Adviser))
            ->post(route('admin.workshops.cancel', $workshop), [
                'cancellation_reason' => 'La salle est indisponible ce jour-là.',
            ])
            ->assertRedirect();

        $this->assertSame(WorkshopStatus::Cancelled, $workshop->fresh()->status);

        Notification::assertSentOnDemand(WorkshopCancelledNotification::class);
    }

    // -------------------------------------------------------------------------
    // Exports
    // -------------------------------------------------------------------------

    #[Test]
    public function les_demandes_sont_exportables_en_csv(): void
    {
        $this->appointment();

        $response = $this->actingAs($this->userWithRole(UserRole::Adviser))
            ->get(route('admin.appointments.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        // Marque d'ordre des octets : sans elle, un tableur français affiche
        // les accents de travers.
        $this->assertStringStartsWith("\u{FEFF}", $content);
        $this->assertStringContainsString('Référence', $content);
        $this->assertStringContainsString('Odette', $content);
    }

    #[Test]
    public function un_editeur_ne_peut_pas_exporter_les_donnees(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Editor))
            ->get(route('admin.appointments.export'))
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Contenus
    // -------------------------------------------------------------------------

    #[Test]
    public function un_editeur_peut_creer_un_service(): void
    {
        $category = ServiceCategory::query()->firstOrFail();

        $this->actingAs($this->userWithRole(UserRole::Editor))
            ->post(route('admin.services.store'), [
                'service_category_id' => $category->id,
                'title' => 'Apprendre à scanner un document',
                'summary' => 'Transformer une feuille de papier en fichier, avec votre téléphone.',
                'level' => SkillLevel::Beginner->value,
                'status' => ContentStatus::Published->value,
            ])
            ->assertRedirect(route('admin.services.index'));

        $service = Service::query()->where('title', 'Apprendre à scanner un document')->firstOrFail();

        // L'identifiant d'URL est déduit du titre.
        $this->assertSame('apprendre-a-scanner-un-document', $service->slug);
    }

    #[Test]
    public function la_creation_d_un_contenu_est_journalisee(): void
    {
        $category = ServiceCategory::query()->firstOrFail();
        $editor = $this->userWithRole(UserRole::Editor);

        $this->actingAs($editor)->post(route('admin.services.store'), [
            'service_category_id' => $category->id,
            'title' => 'Service journalisé',
            'summary' => 'Vérifie que le journal d’audit enregistre bien la création.',
            'level' => SkillLevel::Beginner->value,
            'status' => ContentStatus::Published->value,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'user_id' => $editor->id,
            'subject_label' => 'Service journalisé',
        ]);
    }

    #[Test]
    public function une_page_systeme_ne_peut_pas_etre_supprimee(): void
    {
        $page = Page::query()->where('is_system', true)->firstOrFail();

        $this->actingAs($this->userWithRole(UserRole::SuperAdmin))
            ->delete(route('admin.pages.destroy', $page))
            ->assertForbidden();

        $this->assertNotSoftDeleted($page);
    }

    #[Test]
    public function le_journal_d_audit_ne_contient_aucune_donnee_personnelle(): void
    {
        $appointment = $this->appointment();

        $this->actingAs($this->userWithRole(UserRole::Adviser))
            ->put(route('admin.appointments.update', $appointment), [
                'status' => AppointmentStatus::ToCall->value,
            ]);

        $logs = AuditLog::query()
            ->where('auditable_type', $appointment->getMorphClass())
            ->get();

        $this->assertNotEmpty($logs);

        foreach ($logs as $log) {
            $payload = json_encode([$log->old_values, $log->new_values], JSON_UNESCAPED_UNICODE);

            $this->assertStringNotContainsString('Odette', $payload);
            $this->assertStringNotContainsString('0612345678', $payload);
            $this->assertStringNotContainsString('tablette', $payload);
        }
    }

    // -------------------------------------------------------------------------
    // Formulaires publics de contact et de partenariat
    // -------------------------------------------------------------------------

    #[Test]
    public function un_message_de_contact_est_enregistre(): void
    {
        $this->post('/contact', [
            ...$this->antiSpamFields(),
            'first_name' => 'Ambroise',
            'phone' => '06 55 44 33 22',
            'subject' => 'Question sur les ateliers',
            'message' => 'Bonjour, je voudrais savoir si les ateliers sont adaptés aux débutants.',
            'contact_preference' => ContactPreference::Phone->value,
            'consent' => '1',
        ])->assertRedirect(route('contact.create'));

        $this->assertDatabaseHas('contact_requests', ['first_name' => 'Ambroise']);
    }

    #[Test]
    public function un_message_sans_moyen_de_reponse_est_refuse(): void
    {
        $this->post('/contact', [
            ...$this->antiSpamFields(),
            'first_name' => 'Ambroise',
            'subject' => 'Question',
            'message' => 'Un message sans téléphone ni adresse électronique.',
            'contact_preference' => ContactPreference::Phone->value,
            'consent' => '1',
        ])->assertSessionHasErrors('phone');

        $this->assertSame(0, ContactRequest::query()->count());
    }

    #[Test]
    public function une_demande_de_partenariat_est_enregistree(): void
    {
        $this->post('/partenariats', [
            ...$this->antiSpamFields(),
            'organisation_name' => 'Mairie de Testville',
            'organisation_type' => PartnerType::CityHall->value,
            'contact_name' => 'Ambroise Testeur',
            'email' => 'mairie@example.test',
            'needs' => ['permanence', 'workshops'],
            'consent' => '1',
        ])->assertRedirect(route('partnership.create'));

        $request = PartnershipRequest::query()->firstOrFail();

        $this->assertSame('Mairie de Testville', $request->organisation_name);
        $this->assertSame(['permanence', 'workshops'], $request->needs);
    }

    #[Test]
    public function une_demande_de_partenariat_sans_besoin_est_refusee(): void
    {
        $this->post('/partenariats', [
            ...$this->antiSpamFields(),
            'organisation_name' => 'Mairie de Testville',
            'organisation_type' => PartnerType::CityHall->value,
            'contact_name' => 'Ambroise Testeur',
            'email' => 'mairie@example.test',
            'consent' => '1',
        ])->assertSessionHasErrors('needs');
    }
}
