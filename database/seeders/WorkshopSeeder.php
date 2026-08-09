<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AgeRange;
use App\Enums\ContentStatus;
use App\Enums\DeviceType;
use App\Enums\RegistrationStatus;
use App\Enums\SkillLevel;
use App\Enums\WorkshopStatus;
use App\Models\Municipality;
use App\Models\Workshop;
use App\Models\WorkshopCategory;
use App\Models\WorkshopRegistration;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Ateliers de démonstration, avec quelques inscriptions fictives.
 *
 * Les personnes inscrites sont inventées : aucune donnée réelle n'apparaît
 * dans les jeux de démonstration (codex §40).
 */
class WorkshopSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            ['name' => 'Premiers pas', 'slug' => 'premiers-pas', 'icon' => 'ordinateur'],
            ['name' => 'Smartphone', 'slug' => 'smartphone', 'icon' => 'telephone'],
            ['name' => 'Démarches en ligne', 'slug' => 'demarches', 'icon' => 'administration'],
            ['name' => 'Sécurité', 'slug' => 'securite', 'icon' => 'securite'],
        ])->mapWithKeys(fn (array $category): array => [
            $category['slug'] => WorkshopCategory::updateOrCreate(
                ['slug' => $category['slug']],
                [...$category, 'status' => ContentStatus::Published],
            ),
        ]);

        // Aucun lieu n'est associé : les lieux d'accueil désignent des
        // adresses réelles et se saisissent depuis le back-office, une fois
        // les accords obtenus. Les communes, elles, sont des données publiques.
        $mediatheque = null;
        $salle = null;
        $verson = Municipality::query()->ordered()->first();
        $louvigny = Municipality::query()->ordered()->skip(1)->first() ?? $verson;

        $today = CarbonImmutable::today();

        $workshops = [
            [
                'title' => 'Bien démarrer avec son ordinateur',
                'slug' => 'bien-demarrer-avec-son-ordinateur',
                'category' => 'premiers-pas',
                'date' => $today->addDays(9),
                'start_time' => '14:00',
                'end_time' => '16:00',
                'capacity' => 6,
                'location' => $mediatheque,
                'municipality' => $verson,
                'level' => SkillLevel::Beginner,
                'registrations' => 4,
                'description' => "Une première séance pour apprivoiser l'ordinateur : l'allumer, utiliser la souris, ouvrir une application, l'éteindre correctement.\n\nAucune connaissance préalable n'est demandée. Le matériel est fourni : vous n'avez rien à apporter.",
                'objectives' => [
                    'Allumer et éteindre l’ordinateur sans crainte',
                    'Utiliser la souris avec aisance',
                    'Ouvrir et fermer une application',
                ],
            ],
            [
                'title' => 'Votre smartphone au quotidien',
                'slug' => 'votre-smartphone-au-quotidien',
                'category' => 'smartphone',
                'date' => $today->addDays(16),
                'start_time' => '10:00',
                'end_time' => '12:00',
                'capacity' => 8,
                'location' => $mediatheque,
                'municipality' => $verson,
                'level' => SkillLevel::Beginner,
                'registrations' => 8,
                'own_device_allowed' => true,
                'equipment_provided' => false,
                'description' => "Apportez votre téléphone : nous travaillons sur votre propre appareil, celui que vous utiliserez en rentrant chez vous.\n\nAu programme : l'écran d'accueil, les applications, les contacts, les photos et les messages.",
                'objectives' => [
                    'Reconnaître les icônes de l’écran d’accueil',
                    'Installer une application',
                    'Envoyer une photo à un proche',
                ],
            ],
            [
                'title' => 'Reconnaître les arnaques par courriel et par SMS',
                'slug' => 'reconnaitre-les-arnaques',
                'category' => 'securite',
                'date' => $today->addDays(23),
                'start_time' => '14:30',
                'end_time' => '16:30',
                'capacity' => 10,
                'location' => $salle,
                'municipality' => $louvigny,
                'level' => SkillLevel::Everyone,
                'registrations' => 3,
                'is_free' => true,
                'description' => "Nous examinons ensemble de vrais exemples d'arnaques, pour apprendre à repérer les signes qui ne trompent pas.\n\nCet atelier est gratuit, financé par la commune.",
                'objectives' => [
                    'Repérer un faux courriel en trois secondes',
                    'Savoir quoi faire en cas de doute',
                    'Connaître les sites officiels de signalement',
                ],
            ],
            [
                'title' => 'Vos démarches en ligne, pas à pas',
                'slug' => 'vos-demarches-en-ligne-pas-a-pas',
                'category' => 'demarches',
                'date' => $today->addDays(30),
                'start_time' => '09:30',
                'end_time' => '11:30',
                'capacity' => 6,
                'location' => $mediatheque,
                'municipality' => $verson,
                'level' => SkillLevel::Intermediate,
                'registrations' => 2,
                'description' => "Impôts, retraite, assurance maladie : nous voyons comment créer un espace personnel et retrouver une attestation.\n\nApportez vos identifiants si vous en avez déjà, ainsi que votre téléphone : certains sites envoient un code par SMS.",
                'prerequisites' => 'Savoir utiliser une souris et un clavier.',
            ],
            [
                'title' => 'Trier et sauvegarder ses photos',
                'slug' => 'trier-et-sauvegarder-ses-photos',
                'category' => 'premiers-pas',
                'date' => $today->subDays(14),
                'start_time' => '14:00',
                'end_time' => '16:00',
                'capacity' => 8,
                'location' => $mediatheque,
                'municipality' => $verson,
                'level' => SkillLevel::Beginner,
                'registrations' => 7,
                'status' => WorkshopStatus::Finished,
                'description' => 'Atelier passé, conservé comme exemple de séance terminée.',
            ],
        ];

        $prenoms = ['Marie', 'Jean', 'Odette', 'André', 'Simone', 'Robert', 'Paulette', 'Michel', 'Yvette', 'Roger'];
        $noms = ['Durand', 'Lefèvre', 'Marchand', 'Bertrand', 'Gauthier', 'Renard', 'Leroy', 'Colin', 'Perrin', 'Morel'];

        foreach ($workshops as $index => $data) {
            $workshop = Workshop::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'title' => $data['title'],
                    'workshop_category_id' => $categories[$data['category']]->id,
                    'location_id' => $data['location']?->id,
                    'municipality_id' => $data['municipality']?->id,
                    'description' => $data['description'],
                    'objectives' => $data['objectives'] ?? null,
                    'prerequisites' => $data['prerequisites'] ?? null,
                    'level' => $data['level'],
                    'date' => $data['date'],
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'registration_deadline' => $data['date']->subDays(2),
                    'capacity' => $data['capacity'],
                    'waiting_list_enabled' => true,
                    'is_accessible' => false,
                    'equipment_provided' => $data['equipment_provided'] ?? true,
                    'own_device_allowed' => $data['own_device_allowed'] ?? true,
                    'is_free' => $data['is_free'] ?? false,
                    'price_cents' => ($data['is_free'] ?? false) ? null : 500,
                    'status' => $data['status'] ?? WorkshopStatus::Published,
                    'published_at' => now(),
                ],
            );

            // Les inscriptions ne sont générées qu'une fois, pour que relancer
            // le seeder ne gonfle pas artificiellement le remplissage.
            if ($workshop->registrations()->exists()) {
                continue;
            }

            for ($i = 0; $i < $data['registrations']; $i++) {
                $hasEmail = $i % 3 !== 0; // Une personne sur trois n'a pas d'adresse.

                WorkshopRegistration::create([
                    'workshop_id' => $workshop->id,
                    'first_name' => $prenoms[($index * 3 + $i) % count($prenoms)],
                    'last_name' => $noms[($index * 5 + $i) % count($noms)],
                    'phone' => sprintf('06 %02d %02d %02d %02d', 10 + $i, 20 + $i, 30 + $i, 40 + $i),
                    'email' => $hasEmail
                        ? sprintf('participant%d-%d@example.test', $index, $i)
                        : null,
                    'municipality_id' => $data['municipality']?->id,
                    'age_range' => [AgeRange::From60To69, AgeRange::From70To79, AgeRange::Over80][$i % 3],
                    'device' => [DeviceType::Laptop, DeviceType::Smartphone, DeviceType::Tablet][$i % 3],
                    'status' => ($data['status'] ?? WorkshopStatus::Published) === WorkshopStatus::Finished
                        ? RegistrationStatus::Attended
                        : RegistrationStatus::Confirmed,
                    'registered_by_phone' => ! $hasEmail,
                    'consent_given' => true,
                    'consent_given_at' => now(),
                ]);
            }

            // Aligne le statut sur le remplissage réel.
            $workshop->refresh();

            if ($workshop->status === WorkshopStatus::Published && $workshop->isFull()) {
                $workshop->update(['status' => WorkshopStatus::Full]);
            }
        }
    }
}
