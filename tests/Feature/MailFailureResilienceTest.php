<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AppointmentType;
use App\Enums\ContactPreference;
use App\Models\ContactRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Une panne de messagerie ne doit jamais coûter la demande.
 *
 * Les notifications partent de façon synchrone sur hébergement mutualisé,
 * faute d'exécutant de file d'attente. Sans protection, une coupure du
 * serveur SMTP remonterait jusqu'à la page : le visiteur verrait une erreur
 * alors que sa demande est enregistrée, et il la resoumettrait.
 */
class MailFailureResilienceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Simule un serveur de messagerie injoignable.
     */
    private function breakMailServer(): void
    {
        Notification::shouldReceive('route')
            ->andThrow(new RuntimeException('Connection could not be established'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validMessage(): array
    {
        return [
            ...$this->antiSpamFields(),
            'first_name' => 'Odette',
            'phone' => '06 55 44 33 22',
            'email' => 'odette@exemple.test',
            'subject' => 'Une question',
            'message' => 'Je n’arrive pas à ouvrir ma boîte aux lettres électronique.',
            'contact_preference' => ContactPreference::Phone->value,
            'consent' => '1',
        ];
    }

    #[Test]
    public function le_formulaire_de_contact_aboutit_malgre_la_panne(): void
    {
        $this->breakMailServer();

        $this->post('/contact', $this->validMessage())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('contact.create'));

        $this->assertDatabaseCount('contact_requests', 1);
    }

    #[Test]
    public function la_reference_est_bien_remise_au_visiteur(): void
    {
        // C'est elle qui permet d'être reconnu au téléphone. La perdre
        // reviendrait à demander à la personne de tout recommencer.
        $this->breakMailServer();

        $this->post('/contact', $this->validMessage());

        $reference = ContactRequest::query()->value('reference');

        $this->assertNotNull($reference);
        $this->assertStringContainsString((string) $reference, (string) session('status'));
    }

    #[Test]
    public function la_demande_de_rendez_vous_aboutit_malgre_la_panne(): void
    {
        $this->breakMailServer();

        $this->post('/prendre-rendez-vous', [
            ...$this->antiSpamFields(),
            'first_name' => 'Odette',
            'last_name' => 'Lemarchand',
            'phone' => '06 55 44 33 22',
            'type' => AppointmentType::Home->value,
            'need_description' => 'Je dois faire ma déclaration de revenus en ligne.',
            'contact_preference' => ContactPreference::Phone->value,
            'consent' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('appointments', 1);
    }

    #[Test]
    public function aucun_courriel_ne_part_quand_le_serveur_est_coupe(): void
    {
        // Vérifie que le test simule bien une panne, et non un envoi silencieux.
        $this->breakMailServer();

        $this->post('/contact', $this->validMessage());

        $this->assertEmpty(app('mailer')->getSymfonyTransport()->messages());
    }
}
