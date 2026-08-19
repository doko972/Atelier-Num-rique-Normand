<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SiteSetting;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Le semoir de paramètres tourne à chaque déploiement. Il doit poser les
 * coordonnées réelles sur une installation neuve, remplacer celles restées à
 * l'exemple, et ne jamais toucher à ce qui a été saisi depuis le back-office.
 */
class SettingSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function il_pose_les_coordonnees_reelles(): void
    {
        $this->seed(SettingSeeder::class);

        $this->assertSame('0616602898', $this->value('phone'));
        $this->assertSame('06 16 60 28 98', $this->value('phone_display'));
        $this->assertSame('14110', $this->value('postal_code'));
        $this->assertSame('Condé-en-Normandie', $this->value('city'));
    }

    #[Test]
    public function la_commune_alimente_les_donnees_structurees(): void
    {
        // C'est elle qui rattache le service à son territoire pour les
        // moteurs de recherche : la laisser vide priverait le site de toute
        // visibilité locale.
        $this->seed(SettingSeeder::class);

        $this->get('/')->assertSee('"addressLocality":"Condé-en-Normandie"', escape: false);
    }

    #[Test]
    public function il_remplace_une_valeur_restee_a_l_exemple(): void
    {
        SiteSetting::create([
            'key' => 'phone',
            'value' => '0000000000',
            'type' => 'string',
            'group' => 'contact',
            'label' => 'Numéro de téléphone',
        ]);

        $this->seed(SettingSeeder::class);

        $this->assertSame('0616602898', $this->value('phone'));
    }

    #[Test]
    public function il_preserve_une_valeur_saisie_depuis_le_back_office(): void
    {
        SiteSetting::create([
            'key' => 'phone',
            'value' => '0700000001',
            'type' => 'string',
            'group' => 'contact',
            'label' => 'Numéro de téléphone',
        ]);

        $this->seed(SettingSeeder::class);

        $this->assertSame('0700000001', $this->value('phone'));
    }

    #[Test]
    public function il_est_idempotent(): void
    {
        $this->seed(SettingSeeder::class);
        $count = SiteSetting::query()->count();

        $this->seed(SettingSeeder::class);

        $this->assertSame($count, SiteSetting::query()->count());
        $this->assertSame('0616602898', $this->value('phone'));
    }

    #[Test]
    public function le_numero_affiche_correspond_au_numero_appele(): void
    {
        // Un écart entre les deux enverrait l'appel vers un autre numéro que
        // celui lu à l'écran — une faute invisible et difficile à diagnostiquer.
        $this->seed(SettingSeeder::class);

        $this->assertSame(
            $this->value('phone'),
            str_replace(' ', '', (string) $this->value('phone_display')),
        );
    }

    private function value(string $key): ?string
    {
        return SiteSetting::query()->where('key', $key)->value('value');
    }
}
