<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SearchSynonym;
use Illuminate\Database\Seeder;

/**
 * Premier dictionnaire de synonymes de la recherche publique.
 *
 * Il rapproche les mots du quotidien (« mail », « ordi », « arnaque ») du
 * vocabulaire des contenus. Le semoir ne pose un groupe que si son mot
 * principal n'existe pas encore, y compris dans la corbeille : un groupe
 * modifié ou supprimé depuis le back-office n'est jamais rétabli.
 */
class SearchSynonymSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->groups() as $label => $words) {
            if (SearchSynonym::withTrashed()->where('label', $label)->exists()) {
                continue;
            }

            SearchSynonym::create(['label' => $label, 'words' => $words]);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function groups(): array
    {
        return [
            'courriel' => ['mail', 'email', 'e-mail', 'messagerie', 'boîte mail', 'boîte aux lettres électronique', 'adresse électronique', 'gmail', 'outlook'],
            'ordinateur' => ['ordi', 'PC', 'portable', 'ordinateur portable', 'laptop'],
            'smartphone' => ['téléphone portable', 'mobile', 'iPhone', 'Android', 'portable'],
            'tablette' => ['iPad'],
            'arnaque' => ['escroquerie', 'fraude', 'hameçonnage', 'phishing', 'piratage', 'piraté', 'arnaqueur', 'faux conseiller', 'faux message'],
            'impôts' => ['impot', 'impots.gouv', 'déclaration de revenus', 'fisc', 'taxe'],
            'photo' => ['image', 'selfie', 'album'],
            'appel vidéo' => ['visio', 'visioconférence', 'WhatsApp', 'Skype', 'FaceTime', 'vidéo'],
            'mot de passe' => ['mdp', 'code secret', 'identifiant', 'code oublié'],
            'espace' => ['lent', 'lenteur', 'rame', 'ralenti', 'mémoire pleine', 'stockage', 'plein'],
            'virus' => ['antivirus', 'logiciel malveillant', 'malware'],
            'internet' => ['web', 'wifi', 'wi-fi', 'box', 'connexion', 'réseau'],
            'SMS' => ['texto', 'message'],
            'FranceConnect' => ['France Connect'],
            'carte vitale' => ['ameli', 'sécurité sociale', 'assurance maladie', 'remboursement de soins'],
            'retraite' => ['info retraite', 'pension', 'caisse de retraite'],
            'rendez-vous médical' => ['Doctolib', 'médecin', 'docteur', 'prendre rendez-vous chez le médecin'],
            'imprimer' => ['imprimante', 'impression', 'scanner', 'numériser', 'photocopie'],
            'clé USB' => ['USB', 'clef USB'],
            'sauvegarder' => ['sauvegarde', 'copie de sécurité', 'perdre mes photos', 'cloud'],
            'tarifs' => ['prix', 'coût', 'combien', 'payer'],
        ];
    }
}
