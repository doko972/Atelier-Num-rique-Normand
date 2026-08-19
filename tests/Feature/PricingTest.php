<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Pricing;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Les tarifs sont lus par des personnes qui hésitent à décrocher leur
 * téléphone. Une unité ambiguë leur coûte un appel pour demander ce que le
 * prix recouvre — ou, pire, les fait renoncer.
 */
class PricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ServiceSeeder::class);
    }

    #[Test]
    public function le_tarif_d_atelier_precise_qu_il_est_par_personne(): void
    {
        // « la séance » laissait croire à un forfait pour le groupe entier.
        $atelier = Pricing::query()->where('slug', 'atelier-collectif')->firstOrFail();

        $this->assertStringContainsString('par personne', (string) $atelier->unit);
    }

    #[Test]
    public function chaque_tarif_chiffre_annonce_son_unite(): void
    {
        $sansUnite = Pricing::query()
            ->published()
            ->where('is_quote_only', false)
            ->whereNull('unit')
            ->pluck('label');

        $this->assertTrue(
            $sansUnite->isEmpty(),
            'Tarifs sans unité : '.$sansUnite->implode(', '),
        );
    }

    #[Test]
    public function l_unite_est_visible_sur_la_page_des_tarifs(): void
    {
        $this->get('/tarifs')
            ->assertOk()
            ->assertSee('par personne et par séance', escape: false);
    }

    #[Test]
    public function un_tarif_modifie_depuis_le_back_office_survit_au_deploiement(): void
    {
        // Sans cela, un prix corrigé en ligne reviendrait à son ancienne
        // valeur au prochain `db:seed`, sans que personne s'en aperçoive.
        $atelier = Pricing::query()->where('slug', 'atelier-collectif')->firstOrFail();
        $editor = $this->userWithRole(UserRole::Admin);

        $atelier->forceFill(['amount_cents' => 1200, 'updated_by' => $editor->id])->save();

        $this->seed(ServiceSeeder::class);

        $this->assertSame(1200, Pricing::query()->where('slug', 'atelier-collectif')->value('amount_cents'));
    }

    #[Test]
    public function un_tarif_jamais_touche_suit_le_catalogue(): void
    {
        $atelier = Pricing::query()->where('slug', 'atelier-collectif')->firstOrFail();
        $atelier->forceFill(['amount_cents' => 1200])->save();

        $this->seed(ServiceSeeder::class);

        $this->assertSame(1000, Pricing::query()->where('slug', 'atelier-collectif')->value('amount_cents'));
    }
}
