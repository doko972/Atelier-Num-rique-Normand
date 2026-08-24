<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\ContactPreference;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\ContactRequest;
use App\Models\PartnershipRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Une demande traitée puis archivée quitte la liste de travail, sans quitter
 * la base.
 *
 * La suppression n'est volontairement pas offerte : une demande porte la
 * preuve du consentement recueilli, et sa conservation est bornée par les
 * durées de rétention (purge automatique). L'archivage répond au besoin réel
 * — voir clair dans ce qui reste à faire — sans détruire ce registre.
 */
class ArchivedRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->userWithRole(UserRole::SuperAdmin));
    }

    private function contact(RequestStatus $status): ContactRequest
    {
        return ContactRequest::create([
            'first_name' => 'Odette',
            'last_name' => 'Durand',
            'phone' => '0612345678',
            'subject' => 'Une question',
            'message' => 'Je n’arrive pas à ouvrir ma boîte aux lettres électronique.',
            'contact_preference' => ContactPreference::Phone,
            'status' => $status,
            'consent_given' => true,
            'consent_given_at' => now(),
        ]);
    }

    private function appointment(AppointmentStatus $status): Appointment
    {
        return Appointment::create([
            'first_name' => 'Odette',
            'last_name' => 'Durand',
            'phone' => '0612345678',
            'type' => AppointmentType::Individual,
            'need_description' => 'Difficulté avec ma tablette.',
            'contact_preference' => ContactPreference::Phone,
            'status' => $status,
        ]);
    }

    private function partnership(RequestStatus $status): PartnershipRequest
    {
        return PartnershipRequest::create([
            'organisation_name' => 'Mairie de Condé-en-Normandie',
            'contact_name' => 'Camille Ferrand',
            'email' => 'mairie@exemple.test',
            'phone' => '0231000000',
            'message' => 'Nous souhaitons organiser une permanence.',
            'status' => $status,
            'consent_given' => true,
            'consent_given_at' => now(),
        ]);
    }

    #[Test]
    public function un_message_archive_sort_de_la_liste(): void
    {
        $traite = $this->contact(RequestStatus::Archived);
        $enCours = $this->contact(RequestStatus::New);

        $this->get('/administration/messages')
            ->assertOk()
            ->assertSee($enCours->reference)
            ->assertDontSee($traite->reference);
    }

    #[Test]
    public function il_reste_consultable_en_choisissant_son_statut(): void
    {
        // Archiver n'est pas supprimer : la demande doit rester retrouvable.
        $traite = $this->contact(RequestStatus::Archived);

        $this->get('/administration/messages?statut='.RequestStatus::Archived->value)
            ->assertOk()
            ->assertSee($traite->reference);
    }

    #[Test]
    public function il_reste_en_base(): void
    {
        $traite = $this->contact(RequestStatus::Archived);

        $this->get('/administration/messages');

        $this->assertDatabaseHas('contact_requests', ['id' => $traite->id]);
    }

    #[Test]
    public function une_demande_de_rendez_vous_archivee_sort_de_la_liste(): void
    {
        $traite = $this->appointment(AppointmentStatus::Archived);
        $enCours = $this->appointment(AppointmentStatus::New);

        $this->get('/administration/rendez-vous')
            ->assertOk()
            ->assertSee($enCours->reference)
            ->assertDontSee($traite->reference);
    }

    #[Test]
    public function une_demande_de_partenariat_archivee_sort_de_la_liste(): void
    {
        $traite = $this->partnership(RequestStatus::Archived);
        $enCours = $this->partnership(RequestStatus::New);

        $this->get('/administration/demandes-partenariat')
            ->assertOk()
            ->assertSee($enCours->reference)
            ->assertDontSee($traite->reference);
    }

    #[Test]
    public function le_filtre_annonce_qu_il_ecarte_les_archives(): void
    {
        // Sans ce libellé, la liste paraîtrait incomplète sans explication.
        $this->get('/administration/messages')
            ->assertSee('Tous, sauf les archivés');
    }
}
