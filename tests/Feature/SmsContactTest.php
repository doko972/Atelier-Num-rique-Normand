<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\SettingsService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Le SMS est proposé à côté de l'appel.
 *
 * Ce n'est pas un confort : la presbyacousie touche une large part des plus
 * de 65 ans, et pour ces personnes le téléphone est le canal difficile, pas
 * l'écrit. Sans alternative écrite, elles n'ont aucun moyen de joindre le
 * service en dehors du formulaire.
 */
class SmsContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
    }

    #[Test]
    public function le_lien_sms_reprend_le_numero_sans_separateur(): void
    {
        $settings = app(SettingsService::class);

        $this->assertSame('sms:0616602898', $settings->smsLink());
        $this->assertSame('tel:0616602898', $settings->phoneLink());
    }

    #[Test]
    public function il_est_propose_a_cote_du_formulaire_de_contact(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertSee('sms:0616602898', escape: false);
    }

    #[Test]
    public function il_est_propose_a_cote_de_la_demande_de_rendez_vous(): void
    {
        $this->get('/prendre-rendez-vous')
            ->assertOk()
            ->assertSee('sms:0616602898', escape: false);
    }

    #[Test]
    public function le_numero_reste_lisible_en_toutes_lettres(): void
    {
        // Un lien `sms:` n'ouvre rien sur un ordinateur de bureau. Le numéro
        // doit rester recopiable à la main.
        $this->get('/contact')->assertSee('06 16 60 28 98');
    }

    #[Test]
    public function l_appel_reste_propose_en_premier(): void
    {
        // Le SMS ne doit pas prendre la place du téléphone : la plupart des
        // personnes accompagnées le trouvent plus simple que l'écrit.
        $contenu = $this->get('/contact')->getContent();

        $this->assertLessThan(
            strpos($contenu, 'sms:0616602898'),
            strpos($contenu, 'tel:0616602898'),
            'Le lien d’appel doit précéder le lien SMS.',
        );
    }
}
