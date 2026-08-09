<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\OpeningHour;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Paramètres administrables et horaires d'appel.
 *
 * Les valeurs posées ici sont des exemples destinés à la démonstration : elles
 * ne contiennent aucune coordonnée réelle et doivent être remplacées lors de
 * la mise en service.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->definitions() as $position => $setting) {
            SiteSetting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    // La valeur existante n'est jamais écrasée : relancer le
                    // seeder après la mise en service ne doit pas effacer les
                    // vraies coordonnées.
                    'value' => SiteSetting::query()->where('key', $setting['key'])->value('value')
                        ?? $setting['value'],
                    'type' => $setting['type'] ?? 'string',
                    'group' => $setting['group'],
                    'label' => $setting['label'],
                    'help' => $setting['help'] ?? null,
                    'position' => $position,
                ],
            );
        }

        $this->seedOpeningHours();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function definitions(): array
    {
        return [
            // -- Identité ------------------------------------------------
            [
                'key' => 'site_name',
                'value' => 'L’Atelier Numérique Normand',
                'group' => 'general',
                'label' => 'Nom du service',
            ],
            [
                'key' => 'site_tagline',
                'value' => 'Conseiller numérique à Condé-en-Normandie',
                'group' => 'general',
                'label' => 'Phrase de présentation',
                'help' => 'Affichée sous le nom et reprise dans le titre de la page d’accueil. Mentionnez le métier et la commune : c’est ainsi qu’on vous cherchera.',
            ],
            [
                'key' => 'adviser_name',
                'value' => 'Votre conseiller numérique',
                'group' => 'general',
                'label' => 'Nom du conseiller',
            ],

            // -- Coordonnées ---------------------------------------------
            [
                'key' => 'phone',
                'value' => '0000000000',
                'group' => 'contact',
                'label' => 'Numéro de téléphone',
                'help' => 'Sans espaces. Utilisé pour le lien d’appel direct.',
            ],
            [
                'key' => 'phone_display',
                'value' => '00 00 00 00 00',
                'group' => 'contact',
                'label' => 'Numéro tel qu’il s’affiche',
                'help' => 'Avec des espaces, pour être lu et recopié facilement.',
            ],
            [
                'key' => 'email',
                'value' => 'contact@example.test',
                'group' => 'contact',
                'label' => 'Adresse électronique de contact',
            ],
            [
                'key' => 'address',
                'value' => '',
                'group' => 'contact',
                'label' => 'Adresse postale',
            ],
            [
                'key' => 'postal_code',
                'value' => '',
                'group' => 'contact',
                'label' => 'Code postal',
            ],
            [
                'key' => 'city',
                'value' => '',
                'group' => 'contact',
                'label' => 'Commune',
            ],
            [
                'key' => 'closed_message',
                'value' => 'Vous pouvez laisser un message avec votre nom et votre numéro. Je vous rappellerai dès que possible.',
                'type' => 'text',
                'group' => 'contact',
                'label' => 'Message affiché hors des horaires d’appel',
            ],

            // -- Page d'accueil -------------------------------------------
            [
                'key' => 'home_hero_title',
                'value' => 'Besoin d’aide avec un ordinateur, un smartphone ou une démarche en ligne ?',
                'type' => 'text',
                'group' => 'home',
                'label' => 'Titre principal de la page d’accueil',
            ],
            [
                'key' => 'home_hero_subtitle',
                'value' => 'Je vous accompagne pas à pas, avec patience et sans jargon, à domicile ou près de chez vous.',
                'type' => 'text',
                'group' => 'home',
                'label' => 'Sous-titre de la page d’accueil',
            ],
            [
                'key' => 'home_visit_area',
                'value' => 'Je me déplace dans un rayon de 20 kilomètres. Au-delà, des frais de déplacement peuvent s’appliquer : ils vous sont annoncés avant le rendez-vous.',
                'type' => 'text',
                'group' => 'home',
                'label' => 'Zone d’intervention à domicile',
            ],
            [
                'key' => 'meta_description',
                'value' => 'Aide informatique et démarches en ligne à Condé-en-Normandie et alentour. Je vous accompagne pas à pas, avec patience et sans jargon, à domicile ou près de chez vous.',
                'type' => 'text',
                'group' => 'home',
                'label' => 'Description pour les moteurs de recherche',
                'help' => 'Le texte affiché sous le titre dans les résultats de recherche. Environ 155 caractères : au-delà, la fin est coupée. Nommez le métier et la commune dès la première phrase.',
            ],
            [
                'key' => 'home_final_cta',
                'value' => 'Une question ? Appelez-moi simplement ou demandez un rendez-vous.',
                'type' => 'text',
                'group' => 'home',
                'label' => 'Dernier appel à l’action',
            ],

            // -- Mentions légales -----------------------------------------
            [
                'key' => 'legal_status',
                'value' => '',
                'group' => 'legal',
                'label' => 'Forme juridique',
                'help' => 'Exemple : entreprise individuelle, association loi 1901.',
            ],
            [
                'key' => 'siret',
                'value' => '',
                'group' => 'legal',
                'label' => 'Numéro SIRET',
            ],
            [
                'key' => 'publication_director',
                'value' => '',
                'group' => 'legal',
                'label' => 'Directeur de la publication',
            ],
            [
                'key' => 'hosting_provider',
                'value' => '',
                'type' => 'text',
                'group' => 'legal',
                'label' => 'Hébergeur du site',
                'help' => 'Nom, adresse et téléphone, comme l’exige la loi.',
            ],
        ];
    }

    /**
     * Horaires d'appel : exemple de permanence du lundi au vendredi.
     */
    protected function seedOpeningHours(): void
    {
        if (OpeningHour::query()->exists()) {
            return;
        }

        foreach (range(1, 7) as $weekday) {
            $closed = $weekday >= 6;

            OpeningHour::create([
                'weekday' => $weekday,
                'opens_at' => $closed ? null : '09:00',
                'closes_at' => $closed ? null : '17:00',
                'is_closed' => $closed,
                'position' => $weekday,
            ]);
        }
    }
}
