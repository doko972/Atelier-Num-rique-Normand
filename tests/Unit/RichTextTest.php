<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\RichText;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Rendu des textes éditoriaux saisis depuis le back-office.
 *
 * Le point critique est l'ordre des opérations : échapper d'abord, décorer
 * ensuite. L'inverse laisserait une personne saisir du HTML depuis
 * l'administration — et un compte d'administration compromis pourrait alors
 * injecter un script dans une page publique.
 */
class RichTextTest extends TestCase
{
    private function render(?string $text): string
    {
        return RichText::render($text)->toHtml();
    }

    #[Test]
    public function un_paragraphe_reste_un_paragraphe(): void
    {
        $this->assertSame('<p>Bonjour.</p>', $this->render('Bonjour.'));
    }

    #[Test]
    public function les_lignes_vides_separent_les_paragraphes(): void
    {
        $this->assertSame(
            "<p>Premier.</p>\n<p>Second.</p>",
            $this->render("Premier.\n\nSecond."),
        );
    }

    #[Test]
    public function un_retour_a_la_ligne_simple_reste_dans_le_paragraphe(): void
    {
        $this->assertStringContainsString('<br />', $this->render("Une ligne.\nLa suivante."));
    }

    #[Test]
    public function les_titres_deviennent_des_intertitres(): void
    {
        // Ce ne sont pas des ornements : les lecteurs d'écran s'en servent
        // pour naviguer dans la page sans tout écouter.
        $this->assertSame('<h2>Mon parcours</h2>', $this->render('## Mon parcours'));
        $this->assertSame('<h3>Un détail</h3>', $this->render('### Un détail'));
    }

    #[Test]
    public function la_mise_en_gras_est_reconnue(): void
    {
        $this->assertSame(
            '<p>Avec <strong>L’Atelier Numérique Normand</strong>.</p>',
            $this->render('Avec **L’Atelier Numérique Normand**.'),
        );
    }

    #[Test]
    public function les_tirets_forment_une_liste(): void
    {
        $this->assertSame(
            '<ul><li>Premier</li><li>Second</li></ul>',
            $this->render("- Premier\n- Second"),
        );
    }

    #[Test]
    public function le_html_saisi_est_neutralise(): void
    {
        $rendu = $this->render('<script>alert(1)</script>');

        $this->assertStringNotContainsString('<script>', $rendu);
        $this->assertStringContainsString('&lt;script&gt;', $rendu);
    }

    #[Test]
    public function le_html_dans_un_titre_est_neutralise(): void
    {
        // L'échappement doit précéder la décoration, sur tous les chemins.
        $this->assertStringNotContainsString('<img', $this->render('## <img src=x onerror=alert(1)>'));
    }

    #[Test]
    public function le_html_dans_du_gras_est_neutralise(): void
    {
        $this->assertStringNotContainsString('<b>', $this->render('**<b>gras</b>**'));
    }

    #[Test]
    public function aucun_lien_n_est_fabrique(): void
    {
        // Une adresse tapée dans un champ de texte est le vecteur
        // d'hameçonnage le plus simple qui soit — et ce site prévient
        // justement ses visiteurs contre cela.
        $this->assertStringNotContainsString('<a ', $this->render('[Cliquez ici](https://exemple.test)'));
    }

    #[Test]
    public function un_texte_vide_ne_produit_rien(): void
    {
        $this->assertSame('', $this->render(null));
        $this->assertSame('', $this->render('   '));
    }

    #[Test]
    public function les_retours_windows_sont_acceptes(): void
    {
        // Un texte recopié depuis un traitement de texte arrive souvent
        // avec des retours chariot.
        $this->assertSame(
            "<p>Premier.</p>\n<p>Second.</p>",
            $this->render("Premier.\r\n\r\nSecond."),
        );
    }
}
