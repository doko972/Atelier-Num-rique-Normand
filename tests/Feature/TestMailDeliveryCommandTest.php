<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La commande de test d'envoi est un outil de diagnostic de production, sur
 * un hébergement où Tinker ne fonctionne pas. Elle doit dire la vérité même
 * quand rien ne marche.
 */
class TestMailDeliveryCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function elle_envoie_vers_l_adresse_demandee(): void
    {
        // Pas de `Mail::fake()` ici : on veut vérifier qu'un message part
        // réellement par le transport, et non qu'un envoi a été enregistré.
        // La suite utilise le transport « array », qui ne sort pas du test.
        $this->artisan('courriel:tester', ['destinataire' => 'essai@exemple.test'])
            ->assertSuccessful();

        $messages = Mail::mailer()->getSymfonyTransport()->messages();

        $this->assertCount(1, $messages);
        $this->assertStringContainsString(
            'essai@exemple.test',
            $messages[0]->getOriginalMessage()->toString(),
        );
    }

    #[Test]
    public function elle_alerte_quand_le_port_465_n_a_pas_de_chiffrement(): void
    {
        // Sans MAIL_SCHEME=smtps, le port 465 échoue sans message clair :
        // c'est la panne SMTP la plus fréquente sur ce type d'hébergement.
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.port' => 465,
            'mail.mailers.smtp.scheme' => null,
        ]);

        Mail::fake();

        $this->artisan('courriel:tester', ['destinataire' => 'essai@exemple.test'])
            ->expectsOutputToContain('MAIL_SCHEME=smtps');
    }

    #[Test]
    public function elle_alerte_quand_la_file_retient_les_courriels(): void
    {
        // Les notifications du site sont toutes en file : une connexion autre
        // que `sync` sans exécutant les retient indéfiniment, pendant que les
        // formulaires annoncent « bien envoyé ».
        config(['queue.default' => 'database']);

        Mail::fake();

        $this->artisan('courriel:tester', ['destinataire' => 'essai@exemple.test'])
            ->expectsOutputToContain('queue:work');
    }

    #[Test]
    public function elle_se_tait_sur_la_file_quand_l_envoi_est_immediat(): void
    {
        config(['queue.default' => 'sync']);

        Mail::fake();

        $this->artisan('courriel:tester', ['destinataire' => 'essai@exemple.test'])
            ->doesntExpectOutputToContain('queue:work')
            ->assertSuccessful();
    }

    #[Test]
    public function elle_ne_divulgue_jamais_le_mot_de_passe(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.password' => 'MotDePasseSecret2026',
        ]);

        Mail::fake();

        $this->artisan('courriel:tester', ['destinataire' => 'essai@exemple.test'])
            ->doesntExpectOutputToContain('MotDePasseSecret2026');
    }

    #[Test]
    public function elle_echoue_sans_destinataire_utilisable(): void
    {
        config(['site.contact.admin_email' => null]);

        $this->artisan('courriel:tester')->assertFailed();
    }
}
