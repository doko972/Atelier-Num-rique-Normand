<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\ContactPreference;
use App\Enums\InterventionActivity;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Intervention;
use App\Models\SiteSetting;
use App\Services\SapComplianceService;
use App\Services\SettingsService;
use App\Support\RequestEraser;
use Database\Seeders\ServiceSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Distinction services à la personne / hors SAP.
 *
 * La dispense d'exclusivité SAP tient à deux conditions : une comptabilité
 * séparée, et un chiffre d'affaires hors SAP plafonné à 30 %. Une erreur ici
 * coûte la déclaration SAP — et le crédit d'impôt des clients.
 */
class SapComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected function record(InterventionActivity $activity, int $cents, string $date, string $client = 'Odette Durand'): Intervention
    {
        return Intervention::create([
            'performed_on' => $date,
            'activity' => $activity,
            'client_name' => $client,
            'duration_minutes' => 60,
            'amount_cents' => $cents,
        ]);
    }

    protected function appointment(): Appointment
    {
        return Appointment::create([
            'first_name' => 'Odette',
            'last_name' => 'Durand',
            'phone' => '0612345678',
            'type' => AppointmentType::Home,
            'need_description' => 'Installer mon nouvel ordinateur.',
            'contact_preference' => ContactPreference::Phone,
            'status' => AppointmentStatus::Confirmed,
            'scheduled_for' => now()->subDay(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Classement
    // -------------------------------------------------------------------------

    #[Test]
    public function seules_les_prestations_a_domicile_relevent_des_services_a_la_personne(): void
    {
        $this->assertSame(
            ['home_training', 'home_installation', 'home_maintenance', 'home_administrative'],
            array_map(fn (InterventionActivity $case): string => $case->value, InterventionActivity::sapCases()),
        );

        $this->assertFalse(InterventionActivity::Remote->isSap());
        $this->assertFalse(InterventionActivity::PartnerLocation->isSap());
        $this->assertFalse(InterventionActivity::Workshop->isSap());
        $this->assertFalse(InterventionActivity::Organisation->isSap());
    }

    #[Test]
    public function l_assistance_administrative_se_declare_a_part_de_l_assistance_informatique(): void
    {
        $this->assertSame(InterventionActivity::GROUP_IT, InterventionActivity::HomeInstallation->sapGroup());
        $this->assertSame(InterventionActivity::GROUP_ADMINISTRATIVE, InterventionActivity::HomeAdministrative->sapGroup());
    }

    // -------------------------------------------------------------------------
    // Saisie
    // -------------------------------------------------------------------------

    #[Test]
    public function une_prestation_se_saisit_en_euros(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Admin))
            ->post(route('admin.interventions.store'), [
                'performed_on' => today()->toDateString(),
                'activity' => InterventionActivity::HomeMaintenance->value,
                'client_name' => 'Odette Durand',
                'duration_minutes' => 90,
                'amount' => '52,5',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(5250, Intervention::query()->sole()->amount_cents);
    }

    #[Test]
    public function un_montant_illisible_est_refuse(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Admin))
            ->post(route('admin.interventions.store'), [
                'performed_on' => today()->toDateString(),
                'activity' => InterventionActivity::HomeTraining->value,
                'client_name' => 'Odette Durand',
                'duration_minutes' => 60,
                'amount' => 'trente-cinq',
            ])
            ->assertSessionHasErrors('amount');
    }

    #[Test]
    public function une_prestation_future_est_refusee(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Admin))
            ->post(route('admin.interventions.store'), [
                'performed_on' => today()->addWeek()->toDateString(),
                'activity' => InterventionActivity::HomeTraining->value,
                'client_name' => 'Odette Durand',
                'duration_minutes' => 60,
                'amount' => '35',
            ])
            ->assertSessionHasErrors('performed_on');
    }

    #[Test]
    public function un_conseiller_n_a_pas_acces_au_chiffre_d_affaires(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Adviser))
            ->get(route('admin.interventions.index'))
            ->assertForbidden();
    }

    #[Test]
    public function une_prestation_issue_d_un_rendez_vous_reprend_le_client(): void
    {
        $appointment = $this->appointment();

        $this->actingAs($this->userWithRole(UserRole::Admin))
            ->get(route('admin.interventions.create', ['rendez_vous' => $appointment->id]))
            ->assertOk()
            ->assertSee('Odette Durand')
            ->assertSee('value="'.$appointment->id.'"', escape: false);
    }

    #[Test]
    public function la_prestation_survit_a_l_effacement_du_rendez_vous(): void
    {
        // Une pièce comptable se garde dix ans, la demande de rendez-vous
        // beaucoup moins : effacer l'une ne doit pas emporter l'autre.
        $appointment = $this->appointment();

        $intervention = Intervention::create([
            'performed_on' => today(),
            'activity' => InterventionActivity::HomeInstallation,
            'client_name' => $appointment->fullName(),
            'appointment_id' => $appointment->id,
            'duration_minutes' => 120,
            'amount_cents' => 7000,
        ]);

        RequestEraser::erase($appointment);

        $intervention->refresh();
        $this->assertNull($intervention->appointment_id);
        $this->assertSame('Odette Durand', $intervention->client_name);
    }

    // -------------------------------------------------------------------------
    // Répartition 70 / 30
    // -------------------------------------------------------------------------

    #[Test]
    public function la_part_sap_est_calculee_sur_le_chiffre_d_affaires(): void
    {
        $year = (int) now()->year;

        $this->record(InterventionActivity::HomeTraining, 75000, "{$year}-01-15");
        $this->record(InterventionActivity::Workshop, 25000, "{$year}-02-15");

        $summary = app(SapComplianceService::class)->yearSummary($year);

        $this->assertSame(75.0, $summary->sapShare());
        // 25 % de hors SAP : sous la limite, mais assez près pour avertir.
        $this->assertSame('warning', $summary->status());
        $this->assertTrue($summary->isFirstYear());
    }

    #[Test]
    public function une_part_hors_sap_de_plus_de_30_pourcent_est_signalee(): void
    {
        $year = (int) now()->year;

        $this->record(InterventionActivity::HomeTraining, 60000, "{$year}-01-15");
        $this->record(InterventionActivity::Organisation, 40000, "{$year}-02-15");

        $this->assertSame('over', app(SapComplianceService::class)->yearSummary($year)->status());
    }

    #[Test]
    public function le_plafond_legal_se_calcule_sur_l_annee_precedente(): void
    {
        $year = (int) now()->year;

        // 10 000 € l'an dernier : plafond hors SAP de 3 000 € cette année.
        $this->record(InterventionActivity::HomeTraining, 1000000, ($year - 1).'-06-01');

        // Cette année, une seule grosse commande d'une commune dépasse le
        // plafond, alors même que la part de l'année reste sous 30 %.
        $this->record(InterventionActivity::HomeTraining, 1200000, "{$year}-01-10");
        $this->record(InterventionActivity::Organisation, 310000, "{$year}-03-10");

        $summary = app(SapComplianceService::class)->yearSummary($year);

        $this->assertSame(300000, $summary->legalCapCents());
        $this->assertSame(-10000, $summary->legalRemainingCents());
        $this->assertLessThan(30, $summary->otherShare());
        $this->assertSame('over', $summary->status());
    }

    #[Test]
    public function une_prestation_en_corbeille_sort_des_totaux(): void
    {
        $year = (int) now()->year;

        $this->record(InterventionActivity::HomeTraining, 10000, "{$year}-01-15");
        $this->record(InterventionActivity::Workshop, 50000, "{$year}-01-16")->delete();

        $summary = app(SapComplianceService::class)->yearSummary($year);

        $this->assertSame(0, $summary->otherCents);
        $this->assertSame('ok', $summary->status());
    }

    #[Test]
    public function le_tableau_de_bord_alerte_quand_le_hors_sap_deborde(): void
    {
        $year = (int) now()->year;

        $this->record(InterventionActivity::HomeTraining, 50000, "{$year}-01-15");
        $this->record(InterventionActivity::Organisation, 50000, "{$year}-01-16");

        $this->actingAs($this->userWithRole(UserRole::Admin))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Le hors SAP approche ou dépasse sa limite de 30 %', escape: false);
    }

    // -------------------------------------------------------------------------
    // États trimestriels
    // -------------------------------------------------------------------------

    #[Test]
    public function les_chiffres_trimestriels_separent_les_activites(): void
    {
        $year = (int) now()->year;

        $this->record(InterventionActivity::HomeTraining, 3500, "{$year}-01-10", 'Odette Durand');
        $this->record(InterventionActivity::HomeMaintenance, 3500, "{$year}-02-10", 'odette  durand');
        $this->record(InterventionActivity::HomeAdministrative, 3500, "{$year}-03-10", 'Marcel Martin');
        $this->record(InterventionActivity::Remote, 2000, "{$year}-03-11");
        $this->record(InterventionActivity::HomeTraining, 3500, "{$year}-04-10");

        $q1 = app(SapComplianceService::class)->quarterlyBreakdown($year)[1];

        $this->assertSame(7000, $q1['groups'][InterventionActivity::GROUP_IT]['cents']);
        // « Odette Durand » et « odette  durand » sont la même cliente.
        $this->assertSame(1, $q1['groups'][InterventionActivity::GROUP_IT]['clients']);
        $this->assertSame(3500, $q1['groups'][InterventionActivity::GROUP_ADMINISTRATIVE]['cents']);
        $this->assertSame(10500, $q1['sap_cents']);
        $this->assertSame(2, $q1['sap_clients']);
        $this->assertSame(2000, $q1['other_cents']);
    }

    #[Test]
    public function le_bilan_s_exporte_en_csv(): void
    {
        $year = (int) now()->year;
        $this->record(InterventionActivity::HomeTraining, 3500, "{$year}-01-10");

        $response = $this->actingAs($this->userWithRole(UserRole::Admin))
            ->get(route('admin.interventions.report.export', ['annee' => $year]));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString("T1 {$year}", $content);
        $this->assertStringContainsString('35,00', $content);
    }

    #[Test]
    public function le_bilan_et_la_liste_s_affichent(): void
    {
        $year = (int) now()->year;
        $this->record(InterventionActivity::HomeTraining, 3500, "{$year}-01-10");
        $this->record(InterventionActivity::Workshop, 1000, "{$year}-01-11");

        $admin = $this->userWithRole(UserRole::Admin);

        $this->actingAs($admin)->get(route('admin.interventions.index'))
            ->assertOk()
            ->assertSee('Formation informatique à domicile');

        $this->actingAs($admin)->get(route('admin.interventions.report'))
            ->assertOk()
            ->assertSee('Assistance administrative à domicile');
    }

    // -------------------------------------------------------------------------
    // Site public
    // -------------------------------------------------------------------------

    #[Test]
    public function aucun_credit_d_impot_n_est_annonce_avant_la_declaration_sap(): void
    {
        $this->seed([SettingSeeder::class, ServiceSeeder::class]);

        $this->get('/tarifs')
            ->assertOk()
            ->assertDontSee('crédit d’impôt', escape: false);
    }

    #[Test]
    public function une_fois_declare_le_credit_d_impot_s_affiche_sur_les_tarifs_eligibles(): void
    {
        $this->seed([SettingSeeder::class, ServiceSeeder::class]);

        SiteSetting::query()->where('key', 'sap_declaration_number')->update(['value' => 'SAP939474755']);
        app(SettingsService::class)->flush();

        $this->get('/tarifs')
            ->assertOk()
            ->assertSee('SAP939474755')
            ->assertSee('Crédit d’impôt de 50 %', escape: false)
            // 35 € de l'heure : 17,50 € après crédit d'impôt.
            ->assertSee('17,50', escape: false)
            ->assertSee('Les ateliers collectifs', escape: false);
    }
}
