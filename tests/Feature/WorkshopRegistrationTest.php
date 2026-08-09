<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Enums\WorkshopStatus;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use App\Notifications\RegistrationConfirmedNotification;
use App\Notifications\SeatAvailableNotification;
use App\Notifications\WaitingListNotification;
use App\Services\WorkshopRegistrationService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Inscriptions aux ateliers, places et liste d'attente (codex §10).
 */
class WorkshopRegistrationTest extends TestCase
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

    protected function openWorkshop(): Workshop
    {
        return Workshop::query()
            ->where('status', WorkshopStatus::Published)
            ->whereDate('date', '>=', today())
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function validPayload(array $overrides = []): array
    {
        return [
            ...$this->antiSpamFields(),
            // Prénom absent du jeu de démonstration, pour identifier sans
            // ambiguïté l'inscription créée par ce test.
            'first_name' => 'Ambroise',
            'last_name' => 'Testeur',
            'phone' => '06 98 76 54 32',
            'consent' => '1',
            ...$overrides,
        ];
    }

    #[Test]
    public function une_personne_peut_s_inscrire_a_un_atelier(): void
    {
        $workshop = $this->openWorkshop();
        $before = $workshop->remainingSeats();

        $this->post(route('workshops.register.store', $workshop), $this->validPayload())
            ->assertRedirect(route('workshops.show', $workshop));

        $registration = WorkshopRegistration::query()
            ->where('first_name', 'Ambroise')
            ->firstOrFail();

        $this->assertSame(RegistrationStatus::Pending, $registration->status);
        $this->assertSame($before - 1, $workshop->fresh()->remainingSeats());
    }

    #[Test]
    public function une_inscription_sans_adresse_electronique_est_acceptee(): void
    {
        $workshop = $this->openWorkshop();

        $this->post(route('workshops.register.store', $workshop), $this->validPayload());

        $registration = WorkshopRegistration::query()->where('first_name', 'Ambroise')->firstOrFail();

        $this->assertNull($registration->email);
        $this->assertFalse($registration->canReceiveEmail());
    }

    #[Test]
    public function une_confirmation_est_envoyee_lorsque_l_adresse_est_fournie(): void
    {
        $workshop = $this->openWorkshop();

        $this->post(route('workshops.register.store', $workshop), $this->validPayload([
            'email' => 'ambroise.testeur@example.test',
        ]));

        Notification::assertSentOnDemand(RegistrationConfirmedNotification::class);
    }

    #[Test]
    public function une_inscription_sur_un_atelier_complet_bascule_en_liste_d_attente(): void
    {
        $workshop = $this->openWorkshop();

        // Remplit l'atelier jusqu'à la dernière place.
        $service = app(WorkshopRegistrationService::class);

        while ($workshop->fresh()->remainingSeats() > 0) {
            $service->register($workshop, [
                'first_name' => 'Participant',
                'last_name' => 'Complet',
                'phone' => '06 00 00 00 00',
            ]);
        }

        $outcome = $service->register($workshop->fresh(), [
            'first_name' => 'Yvette',
            'last_name' => 'Morel',
            'phone' => '06 11 11 11 11',
            'email' => 'yvette.morel@example.test',
        ]);

        $this->assertTrue($outcome->onWaitingList);
        $this->assertSame(RegistrationStatus::WaitingList, $outcome->registration->status);
        $this->assertSame(1, $outcome->registration->waiting_position);

        Notification::assertSentOnDemand(WaitingListNotification::class);
    }

    #[Test]
    public function l_atelier_passe_automatiquement_en_complet(): void
    {
        $workshop = $this->openWorkshop();
        $service = app(WorkshopRegistrationService::class);

        while ($workshop->fresh()->remainingSeats() > 0) {
            $service->register($workshop, [
                'first_name' => 'Participant',
                'last_name' => 'Complet',
                'phone' => '06 00 00 00 00',
            ]);
        }

        $this->assertSame(WorkshopStatus::Full, $workshop->fresh()->status);
    }

    #[Test]
    public function une_annulation_libere_une_place_pour_la_liste_d_attente(): void
    {
        $workshop = $this->openWorkshop();
        $service = app(WorkshopRegistrationService::class);

        $first = null;

        while ($workshop->fresh()->remainingSeats() > 0) {
            $outcome = $service->register($workshop, [
                'first_name' => 'Participant',
                'last_name' => 'Complet',
                'phone' => '06 00 00 00 00',
            ]);

            $first ??= $outcome->registration;
        }

        $waiting = $service->register($workshop->fresh(), [
            'first_name' => 'Yvette',
            'last_name' => 'Morel',
            'phone' => '06 11 11 11 11',
            'email' => 'yvette.morel@example.test',
        ])->registration;

        $this->assertSame(RegistrationStatus::WaitingList, $waiting->status);

        // La première personne annule : la place doit profiter à la suivante.
        $service->changeStatus($first, RegistrationStatus::Cancelled);

        $this->assertSame(RegistrationStatus::Pending, $waiting->fresh()->status);
        $this->assertNull($waiting->fresh()->waiting_position);

        Notification::assertSentOnDemand(SeatAvailableNotification::class);
    }

    #[Test]
    public function l_inscription_est_refusee_apres_la_date_limite(): void
    {
        $workshop = $this->openWorkshop();
        $workshop->update(['registration_deadline' => today()->subDay()]);

        $this->get(route('workshops.register', $workshop))->assertStatus(410);

        $this->post(route('workshops.register.store', $workshop), $this->validPayload())
            ->assertStatus(410);
    }

    #[Test]
    public function un_atelier_brouillon_n_est_pas_visible_publiquement(): void
    {
        $workshop = $this->openWorkshop();
        $workshop->update(['status' => WorkshopStatus::Draft]);

        $this->get(route('workshops.show', $workshop))->assertNotFound();
    }

    #[Test]
    public function le_consentement_est_obligatoire_pour_s_inscrire(): void
    {
        $workshop = $this->openWorkshop();

        $payload = $this->validPayload();
        unset($payload['consent']);

        $this->post(route('workshops.register.store', $workshop), $payload)
            ->assertSessionHasErrors('consent');
    }
}
