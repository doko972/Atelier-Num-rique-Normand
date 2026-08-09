<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\ConsentPurpose;
use App\Enums\ContactPreference;
use App\Http\Requests\Site\PublicFormRequest;
use App\Models\Appointment;
use App\Models\ConsentLog;
use App\Notifications\AppointmentReceivedNotification;
use App\Notifications\NewAppointmentForAdminNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Demande de rendez-vous depuis le site public (codex §11 et §46).
 */
class AppointmentRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Notification::fake();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function validPayload(array $overrides = []): array
    {
        return [
            ...$this->antiSpamFields(),
            'first_name' => 'Odette',
            'last_name' => 'Durand',
            'phone' => '06 12 34 56 78',
            'type' => AppointmentType::Individual->value,
            'need_description' => 'Je n’arrive pas à ouvrir mes courriels sur ma tablette.',
            'contact_preference' => ContactPreference::Phone->value,
            'consent' => '1',
            ...$overrides,
        ];
    }

    #[Test]
    public function une_personne_sans_adresse_electronique_peut_demander_un_rendez_vous(): void
    {
        $response = $this->post('/prendre-rendez-vous', $this->validPayload());

        $response->assertRedirect(route('appointments.confirmation'));

        $appointment = Appointment::query()->firstOrFail();

        $this->assertNull($appointment->email);
        $this->assertSame('Odette', $appointment->first_name);
        $this->assertSame(AppointmentStatus::New, $appointment->status);
        $this->assertNotEmpty($appointment->reference);
    }

    #[Test]
    public function la_reference_est_lisible_et_communicable_par_telephone(): void
    {
        $this->post('/prendre-rendez-vous', $this->validPayload());

        $reference = Appointment::query()->value('reference');

        // Format RDV-ANNÉE-XXXX, sans caractère ambigu à l'oral (0/O, 1/I, 8/B).
        $this->assertMatchesRegularExpression(
            '/^RDV-\d{4}-[23456789ACDEFGHJKLMNPQRTUVWXYZ]{4}$/',
            $reference,
        );
    }

    #[Test]
    public function le_consentement_est_enregistre_avec_le_texte_exact_affiche(): void
    {
        $this->post('/prendre-rendez-vous', $this->validPayload());

        $consent = ConsentLog::query()
            ->where('purpose', ConsentPurpose::AppointmentRequest)
            ->firstOrFail();

        $this->assertTrue($consent->granted);
        $this->assertSame(
            __('consent.statements.appointment_request'),
            $consent->statement,
        );
        // L'adresse IP n'est jamais conservée en clair.
        $this->assertNotNull($consent->ip_hash);
        $this->assertSame(64, strlen($consent->ip_hash));
    }

    #[Test]
    public function l_adresse_ip_n_est_jamais_stockee_en_clair(): void
    {
        $this->post('/prendre-rendez-vous', $this->validPayload());

        $appointment = Appointment::query()->firstOrFail();

        $this->assertNotNull($appointment->ip_hash);
        $this->assertStringNotContainsString('127.0.0.1', $appointment->ip_hash);
    }

    #[Test]
    public function un_accuse_de_reception_est_envoye_si_une_adresse_est_fournie(): void
    {
        $this->post('/prendre-rendez-vous', $this->validPayload([
            'email' => 'odette.durand@example.test',
        ]));

        Notification::assertSentOnDemand(AppointmentReceivedNotification::class);
        Notification::assertSentOnDemand(NewAppointmentForAdminNotification::class);
    }

    #[Test]
    public function aucun_accuse_de_reception_n_est_envoye_sans_adresse(): void
    {
        $this->post('/prendre-rendez-vous', $this->validPayload());

        Notification::assertNotSentTo(
            Notification::route('mail', 'inexistant@example.test'),
            AppointmentReceivedNotification::class,
        );

        // L'administration reste prévenue, elle.
        Notification::assertSentOnDemand(NewAppointmentForAdminNotification::class);
    }

    #[Test]
    public function le_consentement_est_obligatoire(): void
    {
        $payload = $this->validPayload();
        unset($payload['consent']);

        $this->post('/prendre-rendez-vous', $payload)
            ->assertSessionHasErrors('consent');

        $this->assertDatabaseCount('appointments', 0);
    }

    #[Test]
    public function un_numero_de_telephone_invalide_est_refuse(): void
    {
        $this->post('/prendre-rendez-vous', $this->validPayload(['phone' => 'appelez-moi']))
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('appointments', 0);
    }

    #[Test]
    public function choisir_le_courriel_sans_adresse_est_refuse(): void
    {
        $this->post('/prendre-rendez-vous', $this->validPayload([
            'contact_preference' => ContactPreference::Email->value,
        ]))->assertSessionHasErrors('email');
    }

    #[Test]
    public function le_champ_leurre_bloque_les_automates(): void
    {
        $this->post('/prendre-rendez-vous', $this->validPayload([
            PublicFormRequest::HONEYPOT_FIELD => 'https://exemple-de-spam.test',
        ]))->assertSessionHasErrors(PublicFormRequest::HONEYPOT_FIELD);

        $this->assertDatabaseCount('appointments', 0);
    }

    #[Test]
    public function un_envoi_instantane_est_bloque(): void
    {
        $this->post('/prendre-rendez-vous', $this->validPayload([
            PublicFormRequest::TIMESTAMP_FIELD => (string) time(),
        ]))->assertSessionHasErrors(PublicFormRequest::HONEYPOT_FIELD);

        $this->assertDatabaseCount('appointments', 0);
    }

    #[Test]
    public function la_reference_transite_par_la_session_et_non_par_l_url(): void
    {
        $response = $this->post('/prendre-rendez-vous', $this->validPayload());

        $response->assertSessionHas('appointment_reference');

        $this->followingRedirects()
            ->post('/prendre-rendez-vous', $this->validPayload())
            ->assertOk()
            ->assertSee('Votre demande est bien arrivée');
    }

    #[Test]
    public function la_page_de_confirmation_redirige_sans_demande_en_session(): void
    {
        $this->get('/prendre-rendez-vous/confirmation')
            ->assertRedirect(route('appointments.create'));
    }
}
