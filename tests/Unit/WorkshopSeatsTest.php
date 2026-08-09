<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\AppointmentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\WorkshopStatus;
use App\Support\CsvExporter;
use App\Support\Privacy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Règles métier : transitions de statut, minimisation des données, exports.
 *
 * Ces vérifications ne touchent pas la base de données. L'application est
 * néanmoins amorcée, car le condensé des adresses IP est salé par la clé
 * d'application.
 */
class WorkshopSeatsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Occupation des places
    // -------------------------------------------------------------------------

    #[Test]
    public function seules_certaines_inscriptions_occupent_une_place(): void
    {
        $this->assertTrue(RegistrationStatus::Pending->occupiesSeat());
        $this->assertTrue(RegistrationStatus::Confirmed->occupiesSeat());
        $this->assertTrue(RegistrationStatus::Attended->occupiesSeat());

        // Une personne en attente n'a pas de place ; une annulation la libère.
        $this->assertFalse(RegistrationStatus::WaitingList->occupiesSeat());
        $this->assertFalse(RegistrationStatus::Cancelled->occupiesSeat());
        $this->assertFalse(RegistrationStatus::Absent->occupiesSeat());
    }

    // -------------------------------------------------------------------------
    // Transitions de statut
    // -------------------------------------------------------------------------

    #[Test]
    public function les_transitions_d_inscription_respectent_le_cycle_de_vie(): void
    {
        $this->assertTrue(RegistrationStatus::Pending->canTransitionTo(RegistrationStatus::Confirmed));
        $this->assertTrue(RegistrationStatus::WaitingList->canTransitionTo(RegistrationStatus::Confirmed));
        $this->assertTrue(RegistrationStatus::Confirmed->canTransitionTo(RegistrationStatus::Attended));

        // Une personne notée présente ne peut plus changer d'état.
        $this->assertSame([], RegistrationStatus::Attended->allowedTransitions());
        $this->assertFalse(RegistrationStatus::Attended->canTransitionTo(RegistrationStatus::Cancelled));
    }

    #[Test]
    public function une_demande_ne_peut_pas_sauter_d_etape(): void
    {
        // Une demande toute neuve ne peut pas être « réalisée » directement.
        $this->assertFalse(AppointmentStatus::New->canTransitionTo(AppointmentStatus::Done));

        // Le chemin normal passe par une proposition puis une confirmation.
        $this->assertTrue(AppointmentStatus::New->canTransitionTo(AppointmentStatus::Proposed));
        $this->assertTrue(AppointmentStatus::Proposed->canTransitionTo(AppointmentStatus::Confirmed));
        $this->assertTrue(AppointmentStatus::Confirmed->canTransitionTo(AppointmentStatus::Done));
    }

    #[Test]
    public function une_demande_archivee_est_definitive(): void
    {
        $this->assertSame([], AppointmentStatus::Archived->allowedTransitions());
    }

    #[Test]
    public function les_statuts_ouverts_et_clos_sont_complementaires(): void
    {
        foreach (AppointmentStatus::cases() as $status) {
            $this->assertNotSame(
                $status->isOpen(),
                $status->isClosed(),
                "Le statut {$status->value} doit être soit ouvert, soit clos.",
            );
        }
    }

    #[Test]
    public function un_atelier_annule_reste_visible_mais_n_accepte_plus_d_inscription(): void
    {
        $this->assertTrue(WorkshopStatus::Cancelled->isPublic());
        $this->assertFalse(WorkshopStatus::Cancelled->acceptsRegistrations());

        $this->assertFalse(WorkshopStatus::Draft->isPublic());
        $this->assertTrue(WorkshopStatus::Full->acceptsRegistrations());
    }

    // -------------------------------------------------------------------------
    // Minimisation des données
    // -------------------------------------------------------------------------

    #[Test]
    public function les_adresses_ip_sont_condensees_de_maniere_irreversible(): void
    {
        $hash = Privacy::hashIp('192.168.1.42');

        $this->assertNotNull($hash);
        $this->assertSame(64, strlen($hash));
        $this->assertStringNotContainsString('192.168', $hash);

        // Même entrée, même condensé : le recoupement de doublons reste possible.
        $this->assertSame($hash, Privacy::hashIp('192.168.1.42'));
        $this->assertNotSame($hash, Privacy::hashIp('192.168.1.43'));
    }

    #[Test]
    public function une_valeur_vide_ne_produit_aucun_condense(): void
    {
        $this->assertNull(Privacy::hashIp(null));
        $this->assertNull(Privacy::hashIp(''));
    }

    #[Test]
    public function les_coordonnees_peuvent_etre_masquees(): void
    {
        $this->assertSame('06 •• •• •• 78', Privacy::maskPhone('06 12 34 56 78'));
        $this->assertSame('j•••@example.fr', Privacy::maskEmail('jean.dupont@example.fr'));
        $this->assertSame('•••', Privacy::maskEmail(null));
    }

    // -------------------------------------------------------------------------
    // Exports
    // -------------------------------------------------------------------------

    #[Test]
    public function les_exports_neutralisent_les_formules_de_tableur(): void
    {
        // Une valeur commençant par « = » serait interprétée comme une formule
        // à l'ouverture du fichier : elle doit être préfixée.
        $this->assertSame("'=1+1", CsvExporter::sanitise('=1+1'));
        $this->assertSame("'+33612345678", CsvExporter::sanitise('+33612345678'));
        $this->assertSame("'@import", CsvExporter::sanitise('@import'));

        // Les valeurs ordinaires passent sans modification.
        $this->assertSame('Odette Durand', CsvExporter::sanitise('Odette Durand'));
        $this->assertSame('', CsvExporter::sanitise(null));
        $this->assertSame('oui', CsvExporter::sanitise(true));
    }
}
