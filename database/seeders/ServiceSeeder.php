<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Enums\PricingModel;
use App\Enums\SkillLevel;
use App\Models\Pricing;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

/**
 * Catalogue des services et grille tarifaire de démonstration (codex §9 et §16).
 */
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->categories() as $position => $category) {
            $model = ServiceCategory::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'summary' => $category['summary'],
                    'icon' => $category['icon'],
                    'status' => ContentStatus::Published,
                    'position' => $position,
                ],
            );

            foreach ($category['services'] as $index => $service) {
                Service::updateOrCreate(
                    ['slug' => $service['slug']],
                    [
                        'service_category_id' => $model->id,
                        'title' => $service['title'],
                        'summary' => $service['summary'],
                        'description' => $service['description'] ?? null,
                        'learning_points' => $service['learning_points'] ?? null,
                        'icon' => $category['icon'],
                        'level' => $service['level'] ?? SkillLevel::Beginner,
                        'estimated_duration_minutes' => $service['duration'] ?? 60,
                        'status' => ContentStatus::Published,
                        'is_featured' => $service['featured'] ?? false,
                        'position' => $index,
                    ],
                );
            }
        }

        $this->seedPricings();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function categories(): array
    {
        return [
            [
                'name' => 'Premiers pas avec l’informatique',
                'slug' => 'premiers-pas-informatique',
                'summary' => 'Apprivoiser l’ordinateur, sans se presser.',
                'icon' => 'ordinateur',
                'services' => [
                    [
                        'title' => 'Utiliser la souris et le clavier',
                        'slug' => 'souris-et-clavier',
                        'summary' => 'Prendre en main la souris, comprendre le double-clic, écrire sans se crisper.',
                        'featured' => true,
                        'learning_points' => [
                            'Déplacer le pointeur sans perdre la souris de vue',
                            'Faire la différence entre un clic et un double-clic',
                            'Écrire un accent, une majuscule, un arobase',
                        ],
                        'description' => "Beaucoup de personnes se découragent dès cette étape. C'est pourtant la plus facile à surmonter, avec un peu d'entraînement et quelqu'un qui ne s'impatiente pas.\n\nNous partons de zéro, et nous prenons le temps qu'il faut.",
                    ],
                    [
                        'title' => 'Organiser ses dossiers et retrouver un document',
                        'slug' => 'organiser-ses-dossiers',
                        'summary' => 'Ranger vos documents pour ne plus jamais les chercher.',
                        'learning_points' => [
                            'Créer un dossier et lui donner un nom clair',
                            'Enregistrer un document au bon endroit',
                            'Retrouver un fichier téléchargé',
                        ],
                    ],
                    [
                        'title' => 'Imprimer et numériser un document',
                        'slug' => 'imprimer-et-numeriser',
                        'summary' => 'Sortir un document sur papier, ou transformer un papier en fichier.',
                    ],
                    [
                        'title' => 'Utiliser une clé USB',
                        'slug' => 'utiliser-une-cle-usb',
                        'summary' => 'Copier vos documents, et retirer la clé sans rien perdre.',
                    ],
                ],
            ],

            [
                'name' => 'Smartphone et tablette',
                'slug' => 'smartphone-et-tablette',
                'summary' => 'Tirer parti de l’appareil que vous avez déjà dans la poche.',
                'icon' => 'telephone',
                'services' => [
                    [
                        'title' => 'Utiliser un smartphone ou une tablette',
                        'slug' => 'utiliser-smartphone-tablette',
                        'summary' => 'Comprendre l’écran d’accueil, installer une application, régler le volume.',
                        'featured' => true,
                        'learning_points' => [
                            'Reconnaître les icônes principales',
                            'Installer et supprimer une application',
                            'Agrandir le texte pour mieux lire',
                        ],
                    ],
                    [
                        'title' => 'Prendre et envoyer une photo',
                        'slug' => 'prendre-et-envoyer-une-photo',
                        'summary' => 'Photographier vos petits-enfants, et leur envoyer la photo.',
                        'featured' => true,
                    ],
                    [
                        'title' => 'Libérer de l’espace sur son téléphone',
                        'slug' => 'liberer-de-l-espace',
                        'summary' => 'Quand le téléphone dit « mémoire pleine », voici quoi faire.',
                        'level' => SkillLevel::Intermediate,
                    ],
                ],
            ],

            [
                'name' => 'Internet et courrier électronique',
                'slug' => 'internet-et-courriel',
                'summary' => 'Chercher, lire, écrire, sans se faire piéger.',
                'icon' => 'courriel',
                'services' => [
                    [
                        'title' => 'Créer et utiliser une adresse électronique',
                        'slug' => 'creer-une-adresse-email',
                        'summary' => 'Créer votre boîte aux lettres électronique, et vous en servir tranquillement.',
                        'featured' => true,
                        'learning_points' => [
                            'Créer une adresse et retenir son mot de passe en sécurité',
                            'Envoyer un message et y joindre une photo',
                            'Reconnaître un message frauduleux',
                        ],
                    ],
                    [
                        'title' => 'Faire une recherche sur Internet',
                        'slug' => 'faire-une-recherche',
                        'summary' => 'Trouver une information, et savoir si le résultat est fiable.',
                        'featured' => true,
                    ],
                    [
                        'title' => 'Enregistrer un site en favori',
                        'slug' => 'enregistrer-un-favori',
                        'summary' => 'Retrouver un site sans avoir à retaper son adresse.',
                    ],
                ],
            ],

            [
                'name' => 'Démarches administratives',
                'slug' => 'demarches-administratives',
                'summary' => 'Impôts, retraite, santé, carte grise : avancer sans stress.',
                'icon' => 'administration',
                'services' => [
                    [
                        'title' => 'Effectuer une démarche administrative en ligne',
                        'slug' => 'demarche-administrative-en-ligne',
                        'summary' => 'Créer votre espace personnel, retrouver une attestation, suivre un dossier.',
                        'featured' => true,
                        'duration' => 90,
                        'description' => "Je vous accompagne, mais vous restez aux commandes : c'est vous qui saisissez, moi qui explique.\n\nCertaines démarches ne peuvent être faites que par vous : je vous le dis clairement le cas échéant.",
                    ],
                    [
                        'title' => 'Utiliser FranceConnect',
                        'slug' => 'utiliser-franceconnect',
                        'summary' => 'Un seul identifiant pour la plupart des sites de l’administration.',
                        'duration' => 45,
                    ],
                    [
                        'title' => 'Prendre un rendez-vous médical en ligne',
                        'slug' => 'rendez-vous-medical-en-ligne',
                        'summary' => 'Trouver un praticien et réserver un créneau, sans passer par le téléphone.',
                    ],
                ],
            ],

            [
                'name' => 'Rester en lien avec ses proches',
                'slug' => 'communiquer-avec-ses-proches',
                'summary' => 'Voir et entendre sa famille, même loin.',
                'icon' => 'famille',
                'services' => [
                    [
                        'title' => 'Faire un appel vidéo',
                        'slug' => 'faire-un-appel-video',
                        'summary' => 'Voir vos enfants et petits-enfants, où qu’ils soient.',
                        'featured' => true,
                    ],
                    [
                        'title' => 'Partager des photos avec sa famille',
                        'slug' => 'partager-des-photos',
                        'summary' => 'Envoyer vos photos, ou recevoir celles des autres.',
                    ],
                ],
            ],

            [
                'name' => 'Sécurité numérique',
                'slug' => 'securite-numerique',
                'summary' => 'Se protéger des arnaques, sans devenir méfiant de tout.',
                'icon' => 'securite',
                'services' => [
                    [
                        'title' => 'Reconnaître les arnaques',
                        'slug' => 'reconnaitre-les-arnaques',
                        'summary' => 'Faux courriels, faux SMS, faux conseiller bancaire : apprendre à les repérer.',
                        'featured' => true,
                        'learning_points' => [
                            'Repérer les signes d’un message frauduleux',
                            'Savoir quoi faire en cas de doute',
                            'Savoir où signaler une arnaque',
                        ],
                    ],
                    [
                        'title' => 'Créer et retenir des mots de passe solides',
                        'slug' => 'mots-de-passe-solides',
                        'summary' => 'Des mots de passe sûrs, sans avoir à tout mémoriser.',
                        'level' => SkillLevel::Intermediate,
                    ],
                    [
                        'title' => 'Sauvegarder ses données',
                        'slug' => 'sauvegarder-ses-donnees',
                        'summary' => 'Ne plus rien perdre en cas de panne ou de vol.',
                        'level' => SkillLevel::Intermediate,
                    ],
                ],
            ],

            [
                'name' => 'Photos et souvenirs',
                'slug' => 'photos-et-souvenirs',
                'summary' => 'Classer, sauvegarder et partager vos souvenirs.',
                'icon' => 'photo',
                'services' => [
                    [
                        'title' => 'Transférer et classer ses photos',
                        'slug' => 'transferer-et-classer-ses-photos',
                        'summary' => 'Sortir les photos du téléphone et les ranger sur l’ordinateur.',
                    ],
                    [
                        'title' => 'Numériser d’anciennes photos',
                        'slug' => 'numeriser-anciennes-photos',
                        'summary' => 'Donner une seconde vie aux photos papier de vos albums.',
                        'level' => SkillLevel::Intermediate,
                        'duration' => 90,
                    ],
                ],
            ],
        ];
    }

    protected function seedPricings(): void
    {
        $pricings = [
            [
                'label' => 'Accompagnement individuel',
                'slug' => 'accompagnement-individuel',
                'model' => PricingModel::Hourly,
                'amount_cents' => 3500,
                'unit' => 'par heure',
                'duration_minutes' => 60,
                'is_highlighted' => true,
                'includes' => [
                    'Une heure d’accompagnement, chez vous ou dans un lieu partenaire',
                    'Une fiche écrite reprenant ce que nous avons vu',
                    'Une question de suivi par téléphone, sans supplément',
                ],
                'payment_methods' => 'Espèces, chèque ou virement, à l’issue de la séance.',
                'cancellation_policy' => 'Annulation sans frais jusqu’à 24 heures avant le rendez-vous. Un imprévu reste un imprévu : appelez-moi, nous en discuterons.',
                'travel_costs' => 'Déplacement gratuit jusqu’à 20 kilomètres. Au-delà, 0,40 € par kilomètre, annoncé avant le rendez-vous.',
            ],
            [
                'label' => 'Forfait découverte',
                'slug' => 'forfait-decouverte',
                'model' => PricingModel::Discovery,
                'amount_cents' => 2000,
                'unit' => 'la première séance',
                'duration_minutes' => 60,
                'description' => 'Pour une première prise de contact, si vous hésitez encore.',
                'includes' => ['Une heure pour faire le point sur vos besoins', 'Aucune obligation de poursuivre'],
            ],
            [
                // « les cinq heures » laissait hésiter entre cinq rendez-vous
                // d'une heure et cinq heures à découper librement.
                'label' => 'Forfait de cinq séances',
                'slug' => 'forfait-cinq-seances',
                'model' => PricingModel::Package,
                'amount_cents' => 15000,
                'unit' => 'les cinq séances d’une heure',
                'duration_minutes' => 60,
                'description' => 'Une progression construite, à votre rythme, sur plusieurs semaines. Cinq rendez-vous d’une heure, soit 30 € la séance au lieu de 35 €.',
                'includes' => ['Cinq séances d’une heure', 'Un programme adapté à vos besoins', 'Les fiches écrites correspondantes'],
            ],
            [
                // « la séance » laissait croire à un forfait pour le groupe
                // entier. L'unité doit lever le doute à elle seule : elle est
                // lue juste après le montant, souvent sans lire la suite.
                'label' => 'Atelier collectif',
                'slug' => 'atelier-collectif',
                'model' => PricingModel::Workshop,
                'amount_cents' => 1000,
                'unit' => 'par personne et par séance',
                'duration_minutes' => 120,
                'description' => 'En petit groupe, sur un thème précis. Chaque participant règle 10 €, quel que soit le nombre d’inscrits. L’atelier est confirmé à partir de quatre inscrits.',
                'includes' => ['Deux heures en petit groupe', 'Le matériel est fourni', 'Une fiche pratique à emporter', 'À partir de quatre participants'],
            ],
            [
                'label' => 'Atelier financé par un partenaire',
                'slug' => 'atelier-finance',
                'model' => PricingModel::FundedWorkshop,
                'amount_cents' => 0,
                'unit' => 'gratuit pour les participants',
                'description' => 'Certains ateliers sont pris en charge par une commune ou une association : ils sont alors gratuits.',
            ],
            [
                'label' => 'Intervention pour une collectivité ou une association',
                'slug' => 'intervention-collectivite',
                'model' => PricingModel::Quote,
                'is_quote_only' => true,
                'description' => 'Permanences, ateliers, sensibilisation des agents : un devis est établi selon vos besoins.',
                'payment_methods' => 'Sur facture, après service fait.',
            ],
        ];

        foreach ($pricings as $position => $pricing) {
            $existing = Pricing::query()->where('slug', $pricing['slug'])->first();

            // Un tarif retouché depuis le back-office porte un auteur de
            // modification ; le semoir, qui tourne sans utilisateur connecté,
            // n'en pose jamais. Réécrire une telle ligne à chaque déploiement
            // ramènerait silencieusement l'ancien prix.
            if ($existing !== null && $existing->updated_by !== null) {
                $existing->update(['position' => $position]);

                continue;
            }

            Pricing::updateOrCreate(
                ['slug' => $pricing['slug']],
                [
                    ...$pricing,
                    'status' => ContentStatus::Published,
                    'position' => $position,
                ],
            );
        }
    }
}
