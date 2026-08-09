<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\OpeningHour;
use App\Support\FrenchFormat;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Mise en forme des heures en français.
 *
 * Ces tests verrouillent un piège d'échappement : dans un format de date PHP,
 * `'H\\ h i'` place la barre oblique devant l'espace et non devant le `h`. Le
 * `h` est alors compris comme l'heure sur douze heures, et 17 h 00 s'affichait
 * « 17 05 00 » dans le pied de page.
 */
class FrenchFormatTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function heures(): array
    {
        return [
            'matin' => ['09:00:00', '09h00'],
            'midi' => ['12:30:00', '12h30'],
            // Le cas qui révélait le bug : sur douze heures, 17 h devient 5 h.
            'après-midi' => ['17:00:00', '17h00'],
            'soir' => ['18:45:00', '18h45'],
            'minuit' => ['00:00:00', '00h00'],
            'fin de journée' => ['23:59:00', '23h59'],
        ];
    }

    #[Test]
    #[DataProvider('heures')]
    public function les_heures_sont_formatees_a_la_francaise(string $input, string $expected): void
    {
        $this->assertSame($expected, FrenchFormat::time($input));
    }

    #[Test]
    public function une_heure_de_l_apres_midi_n_est_jamais_ramenee_sur_douze_heures(): void
    {
        // Vérification explicite : c'est exactement ce que produisait le
        // format erroné.
        $this->assertStringStartsWith('17', FrenchFormat::time('17:00:00'));
        $this->assertStringNotContainsString('05', FrenchFormat::time('17:00:00'));
    }

    #[Test]
    public function le_format_accepte_un_objet_date(): void
    {
        $this->assertSame(
            '14h05',
            FrenchFormat::time(CarbonImmutable::parse('2026-08-06 14:05:00')),
        );
    }

    #[Test]
    public function une_valeur_vide_ne_produit_rien(): void
    {
        $this->assertSame('', FrenchFormat::time(null));
        $this->assertSame('', FrenchFormat::time(''));
    }

    #[Test]
    public function une_plage_horaire_est_lisible(): void
    {
        $this->assertSame('de 09h00 à 17h00', FrenchFormat::range('09:00:00', '17:00:00'));
    }

    #[Test]
    public function une_date_est_ecrite_en_toutes_lettres(): void
    {
        $this->assertSame(
            'jeudi 6 août 2026',
            FrenchFormat::date('2026-08-06'),
        );
    }

    #[Test]
    public function une_date_avec_heure_reste_lisible(): void
    {
        $this->assertSame(
            'jeudi 6 août 2026 à 09h30',
            FrenchFormat::dateTime('2026-08-06 09:30:00'),
        );
    }

    #[Test]
    public function un_creneau_d_ouverture_affiche_sa_plage(): void
    {
        $hour = new OpeningHour([
            'weekday' => 1,
            'opens_at' => '09:00:00',
            'closes_at' => '17:00:00',
            'is_closed' => false,
        ]);

        $this->assertSame('de 09h00 à 17h00', $hour->range());
    }

    #[Test]
    public function un_jour_ferme_le_dit_clairement(): void
    {
        $hour = new OpeningHour([
            'weekday' => 7,
            'opens_at' => null,
            'closes_at' => null,
            'is_closed' => true,
        ]);

        $this->assertSame(__('site.contact.closed'), $hour->range());
    }

    #[Test]
    public function un_creneau_sans_horaire_est_considere_ferme(): void
    {
        // Une plage incomplète ne doit pas afficher « de  à  ».
        $hour = new OpeningHour([
            'weekday' => 3,
            'opens_at' => '09:00:00',
            'closes_at' => null,
            'is_closed' => false,
        ]);

        $this->assertSame(__('site.contact.closed'), $hour->range());
    }

    #[Test]
    public function le_nom_du_jour_est_en_francais(): void
    {
        $this->assertSame('lundi', (new OpeningHour(['weekday' => 1]))->weekdayName());
        $this->assertSame('dimanche', (new OpeningHour(['weekday' => 7]))->weekdayName());
    }
}
