<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\ConsentPurpose;
use App\Enums\ContactPreference;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\ConsentLog;
use App\Models\ContactRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Suppression définitive d'une demande.
 *
 * Elle sert aux essais de mise en service et aux doublons évidents. Pour une
 * demande réelle, l'anonymisation reste la bonne réponse — mais un essai fait
 * par le conseiller lui-même n'a aucune valeur de registre.
 */
class RequestDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function contact(): ContactRequest
    {
        return ContactRequest::create([
            'first_name' => 'Essai',
            'last_name' => 'Essai',
            'phone' => '0612345678',
            'subject' => 'Test d’envoi',
            'message' => 'Message de test envoyé pendant la mise en service.',
            'contact_preference' => ContactPreference::Phone,
            'status' => RequestStatus::New,
            'consent_given' => true,
            'consent_given_at' => now(),
        ]);
    }

    private function appointment(): Appointment
    {
        return Appointment::create([
            'first_name' => 'Essai',
            'last_name' => 'Essai',
            'phone' => '0612345678',
            'type' => AppointmentType::Individual,
            'need_description' => 'Demande de test.',
            'contact_preference' => ContactPreference::Phone,
            'status' => AppointmentStatus::New,
        ]);
    }

    #[Test]
    public function un_administrateur_supprime_une_demande_de_rendez_vous(): void
    {
        $appointment = $this->appointment();

        $this->actingAs($this->userWithRole(UserRole::SuperAdmin))
            ->delete(route('admin.appointments.destroy', $appointment))
            ->assertRedirect(route('admin.appointments.index'));

        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
    }

    #[Test]
    public function la_suppression_est_definitive_et_non_reversible(): void
    {
        // Le modèle utilise la suppression douce : sans forceDelete, la ligne
        // resterait en base et reviendrait dans un export.
        $appointment = $this->appointment();

        $this->actingAs($this->userWithRole(UserRole::SuperAdmin))
            ->delete(route('admin.appointments.destroy', $appointment));

        $this->assertNull(Appointment::withTrashed()->find($appointment->id));
    }

    #[Test]
    public function elle_emporte_les_enregistrements_de_consentement(): void
    {
        // Les laisser derrière produirait un registre pointant vers une
        // demande qui n'existe plus.
        $contact = $this->contact();

        ConsentLog::create([
            'consentable_type' => $contact->getMorphClass(),
            'consentable_id' => $contact->id,
            'purpose' => ConsentPurpose::ContactRequest,
            'statement' => 'J’accepte que mes données soient utilisées.',
            'granted' => true,
            'granted_at' => now(),
        ]);

        $this->actingAs($this->userWithRole(UserRole::SuperAdmin))
            ->delete(route('admin.contact-requests.destroy', $contact));

        $this->assertDatabaseCount('consent_logs', 0);
    }

    #[Test]
    public function elle_laisse_une_trace_dans_le_journal_d_audit(): void
    {
        // C'est la seule trace qui subsiste : elle doit être écrite avant
        // l'effacement, puisqu'il ne restera rien à décrire ensuite.
        $appointment = $this->appointment();

        $this->actingAs($this->userWithRole(UserRole::SuperAdmin))
            ->delete(route('admin.appointments.destroy', $appointment));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'delete',
            'auditable_id' => $appointment->id,
        ]);
    }

    #[Test]
    public function un_conseiller_ne_peut_pas_supprimer(): void
    {
        // Le conseiller traite les demandes au quotidien ; l'effacement
        // définitif reste hors de sa portée.
        $appointment = $this->appointment();

        $this->actingAs($this->userWithRole(UserRole::Adviser))
            ->delete(route('admin.appointments.destroy', $appointment))
            ->assertForbidden();

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
    }

    #[Test]
    public function un_visiteur_ne_peut_pas_supprimer(): void
    {
        $appointment = $this->appointment();

        $this->delete(route('admin.appointments.destroy', $appointment))
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
    }

    #[Test]
    public function la_fiche_annonce_a_quoi_sert_la_suppression(): void
    {
        // Sans cet avertissement, elle deviendrait le geste par défaut, au
        // détriment de l'anonymisation.
        $appointment = $this->appointment();

        $this->actingAs($this->userWithRole(UserRole::SuperAdmin))
            ->get(route('admin.appointments.show', $appointment))
            ->assertSee('Rendre anonyme', escape: false)
            ->assertSee('doublons évidents', escape: false);
    }
}
