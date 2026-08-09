# Journal des versions

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) et le
[versionnage sémantique](https://semver.org/lang/fr/).

Les migrations destructives sont signalées explicitement.

---

## [1.0.0] — 2026-08-06

Première version. Couvre les phases 1 à 4 du cahier des charges.

### Socle

- Laravel 13, PHP 8.3, MySQL 8, Vite 8, SASS en architecture 7-1
- 33 tables, 5 rôles, 13 permissions élémentaires
- Authentification d'administration : connexion, mot de passe oublié,
  réinitialisation, vérification d'adresse, limitation des tentatives,
  révocation des sessions
- Journalisation séparée par domaine : sécurité, authentification, courriels,
  rendez-vous, ateliers, administration, RGPD

### Site public

- 17 pages, dont les 4 pages légales
- Demande de rendez-vous fonctionnelle **sans adresse électronique**
- Agenda des ateliers, inscription en ligne, liste d'attente automatique
- Formulaires de contact et de partenariat
- Centre de ressources : articles, fiches pratiques en étapes numérotées,
  version imprimable
- Plan du site XML, `robots.txt`, données structurées Schema.org
  (`ProfessionalService`, `Event`, `Article`, `FAQPage`, `BreadcrumbList`,
  `Service`, `Person`)

### Accessibilité

- Palette dont chaque couple texte/fond atteint au moins 4,5:1
- Contraste renforcé, agrandissement du texte sur 3 crans, réduction des
  animations — réglages conservés sur l'appareil
- Navigation clavier complète, liens d'évitement, fils d'Ariane
- Protection anti-spam sans captcha : champ leurre et délai minimal
- Barre d'appel fixe sur mobile

### Back-office

- Tableau de bord avec compteurs, alertes et statistiques mensuelles
- Suivi des demandes avec transitions de statut contrôlées et notes internes
- Gestion des ateliers, annulation avec information des inscrits
- Inscription manuelle pour les demandes reçues par téléphone
- 14 CRUD de contenu, corbeille et restauration
- Exports CSV protégés contre l'injection de formule
- Journal d'audit sans donnée personnelle d'usager
- Paramètres du site et horaires d'appel administrables

### Conformité

- Registre des consentements conservant le texte exact affiché
- Anonymisation plutôt que suppression, pour préserver les bilans agrégés
- Purge automatique hebdomadaire selon les durées configurées
- Écran de traitement des demandes RGPD avec délai légal et vérification
  d'identité obligatoire
- Adresses IP conservées uniquement sous forme de condensé salé

### Sécurité

- Politique de sécurité du contenu avec nonce par réponse
- En-têtes de sécurité sur toutes les réponses
- Limitation à 5 envois de formulaire par heure et par adresse IP
- Compte désactivé déconnecté immédiatement

### Qualité

- 102 tests, 265 assertions, exécutés sur MySQL
- Laravel Pint (PSR-12)
- Seeders idempotents, réexécutables après déploiement

---

## Écarts assumés par rapport au cahier des charges

| Point du codex | Choix retenu | Raison |
| --- | --- | --- |
| `app/Http/Controllers/Public/` | `Site/` | `public` est un mot réservé de PHP et ne peut pas être un segment d'espace de noms |
| `resources/lang/` | `lang/` | Emplacement standard depuis Laravel 9 |
| Laravel Breeze | Authentification écrite sur mesure | Breeze ne propose plus de variante Blade seule ; l'écrire permet des libellés français maîtrisés et une limitation des tentatives adaptée |
| Bootstrap 5.3 | CSS accessible sur mesure | Le codex laissait le choix. 41 ko de CSS au total, contre plus de 200 ko avec Bootstrap, et un contrôle total sur les contrastes et les cibles tactiles |
| PDF accessible des fiches | Version imprimable HTML | Un PDF réellement balisé demande une chaîne de production dédiée ; l'impression HTML est accessible, imprimable et toujours à jour |
| `email:rfc,dns` | `email:rfc` | La vérification DNS ajoute un appel réseau à chaque envoi et rejette des adresses valides quand la résolution échoue |

## Ce qui reste à faire

Fonctionnalités du codex non couvertes par cette version :

- **Téléversement de fichiers depuis le back-office** : la table
  `downloadable_files`, le modèle, la validation et le téléchargement public
  existent ; l'écran d'envoi reste à construire. Les images se renseignent
  aujourd'hui par leur chemin.
- **Double authentification** : les colonnes sont en place, le parcours
  d'activation reste à écrire.
- **Génération de l'archive d'export RGPD** : les données sont consultables
  au format JSON depuis le back-office ; la production d'un fichier
  téléchargeable et son expiration automatique restent à finaliser.
- **Rappels par SMS** et **agenda avec réservation directe de créneau** :
  prévus en phase 5 par le codex.
- **Audit d'accessibilité externe** : la déclaration publie honnêtement un
  audit interne partiel, comme le prévoit la réglementation.
