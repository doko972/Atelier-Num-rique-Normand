# Conseiller Numérique

Site web d'un service d'accompagnement numérique de proximité, destiné en
priorité aux personnes retraitées et aux habitants éloignés du numérique.

Le site présente les services, publie l'agenda des ateliers collectifs, permet
de demander un rendez-vous **sans posséder d'adresse électronique**, et fournit
un back-office complet pour administrer l'ensemble.

---

## Sommaire

- [Ce que fait le site](#ce-que-fait-le-site)
- [Stack technique](#stack-technique)
- [Installation](#installation)
- [Comptes de démonstration](#comptes-de-démonstration)
- [Commandes utiles](#commandes-utiles)
- [Accessibilité](#accessibilité)
- [Sécurité](#sécurité)
- [RGPD](#rgpd)
- [Architecture](#architecture)
- [Tests](#tests)
- [Documentation complémentaire](#documentation-complémentaire)

---

## Ce que fait le site

### Espace public

| Page | Adresse |
| --- | --- |
| Accueil | `/` |
| Mes services | `/mes-services` |
| Ateliers numériques | `/ateliers` |
| Prendre rendez-vous | `/prendre-rendez-vous` |
| Démarches en ligne | `/demarches-en-ligne` |
| Sécurité et arnaques | `/securite-et-arnaques` |
| Conseils pratiques | `/conseils-pratiques` |
| Tarifs | `/tarifs` |
| Communes et associations | `/partenariats` |
| À propos | `/a-propos` |
| Contact | `/contact` |
| Questions fréquentes | `/questions-frequentes` |
| Mentions légales | `/mentions-legales` |
| Politique de confidentialité | `/politique-de-confidentialite` |
| Gestion des cookies | `/gestion-des-cookies` |
| Déclaration d'accessibilité | `/declaration-accessibilite` |

### Espace d'administration — `/administration`

Tableau de bord, demandes de rendez-vous, ateliers, inscriptions, messages
reçus, demandes de partenariat, services, articles, fiches pratiques, pages,
questions fréquentes, témoignages, tarifs, liens officiels, communes, lieux,
partenaires, comptes, paramètres du site, journal des actions, demandes RGPD.

---

## Stack technique

| Composant | Version |
| --- | --- |
| Laravel | 13.x |
| PHP | 8.3 minimum |
| MySQL | 8 (MariaDB 10.11 également compatible) |
| Blade | — |
| Vite | 8 |
| SASS | architecture 7-1, `sass-embedded` |
| Police | Atkinson Hyperlegible, auto-hébergée |
| Tests | PHPUnit 12 |
| Formatage | Laravel Pint (PSR-12) |

Aucun framework JavaScript côté client. Le JavaScript se limite à
l'amélioration progressive : menu repliable, préférences d'affichage,
confirmations de suppression (4 ko compressés).

Aucun appel à un service externe : les polices sont auto-hébergées, ce qui
sert à la fois les performances, la politique de sécurité du contenu et la
vie privée des visiteurs.

---

## Installation

### Prérequis

- PHP 8.3 ou supérieur, avec les extensions `pdo_mysql`, `mbstring`,
  `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `curl`, `fileinfo`, `zip`,
  `gd`, `intl`, `bcmath`
- Composer 2
- Node.js 20 ou supérieur
- MySQL 8 ou MariaDB 10.11

### Procédure

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Créez la base de données, puis renseignez les paramètres `DB_*` du fichier
`.env` :

```sql
CREATE DATABASE conseillernumerique CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Puis :

```bash
php artisan migrate --seed
npm run build
php artisan storage:link
php artisan optimize
```

Le site est accessible sur `http://localhost:8000` après `php artisan serve`.

### Développement

```bash
composer dev
```

Cette commande lance simultanément le serveur, l'écoute des files d'attente,
les journaux en direct et Vite en mode développement.

---

## Comptes de démonstration

Créés uniquement hors production, par `UserSeeder`. **Supprimez-les ou
changez leur mot de passe avant toute mise en ligne.**

| Rôle | Adresse | Mot de passe |
| --- | --- | --- |
| Super administrateur | `super-admin@example.test` | `MotDePasseDemo2026!` |
| Administrateur | `admin@example.test` | `MotDePasseDemo2026!` |
| Conseiller | `conseiller@example.test` | `MotDePasseDemo2026!` |
| Éditeur | `editeur@example.test` | `MotDePasseDemo2026!` |
| Lecteur | `lecteur@example.test` | `MotDePasseDemo2026!` |

### Rôles et permissions

| Rôle | Portée |
| --- | --- |
| Super administrateur | Tout, y compris la gestion des comptes |
| Administrateur | Tout sauf la gestion des comptes |
| Conseiller | Demandes, ateliers, inscriptions, messages, exports |
| Éditeur | Contenus éditoriaux, communes, lieux, partenaires, ateliers |
| Lecteur | Tableau de bord seulement |

La matrice complète est amorcée par `RoleSeeder` et reste modifiable en base
sans redéploiement.

---

## Commandes utiles

```bash
# Rappels aux personnes inscrites aux ateliers (2 jours avant par défaut)
php artisan ateliers:rappels
php artisan ateliers:rappels --simulation   # affiche sans envoyer

# Clôture des ateliers dont la date est passée
php artisan ateliers:cloturer

# Purge RGPD : anonymise ce qui dépasse la durée de conservation
php artisan rgpd:purger
php artisan rgpd:purger --simulation

# Qualité
./vendor/bin/pint
php artisan test
```

Une seule ligne de cron suffit en production pour l'ensemble des tâches
planifiées (voir [`docs/deploiement.md`](docs/deploiement.md)).

---

## Accessibilité

Le site vise le **RGAA 4.1** et les recommandations **WCAG 2.2 niveau AA**.

- Contrastes vérifiés couple par couple (ratios documentés dans
  `resources/sass/abstracts/_variables.scss`)
- Texte de 18 px sur ordinateur, 17 px sur mobile, interligne 1,65
- Trois réglages persistants : contraste renforcé, taille du texte (3 crans),
  réduction des animations
- Navigation complète au clavier, anneau de focus de 3 px très visible
- Liens toujours soulignés ; aucune information portée par la seule couleur
- Cibles tactiles de 48 px minimum
- Formulaires à labels visibles, erreurs affichées près du champ **et**
  récapitulées en tête avec des liens d'ancrage
- Fils d'Ariane, liens d'évitement, landmarks explicites
- Aucun carrousel, aucune lecture automatique, aucun captcha
- Fiches pratiques imprimables, avec le numéro de téléphone en pied de page

La déclaration d'accessibilité est alimentée par la table
`accessibility_reports`, administrable depuis le back-office.

### Protection anti-spam sans captcha

Un captcha visuel ou sonore exclurait précisément le public visé. La
protection combine trois mécanismes invisibles :

1. un champ leurre, masqué et retiré de l'arbre d'accessibilité ;
2. un délai minimal entre l'affichage et l'envoi du formulaire ;
3. une limitation à 5 envois par heure et par adresse IP.

---

## Sécurité

- CSRF sur tous les formulaires, échappement Blade systématique
- Politique de sécurité du contenu stricte, avec nonce par réponse
- En-têtes : `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`,
  `Permissions-Policy`, `Strict-Transport-Security` en production
- Limitation des tentatives de connexion (5 essais / 15 minutes)
- Compte désactivé déconnecté immédiatement, même session en cours
- Policies sur chaque action ; aucun contournement global pour le super
  administrateur, afin de préserver les garde-fous (par exemple :
  impossible de se supprimer soi-même)
- Journaux séparés par domaine : `securite`, `auth`, `mails`, `rdv`,
  `ateliers`, `admin`, `rgpd`
- Exports CSV protégés contre l'injection de formule de tableur

### Ce qui n'est jamais stocké

Mots de passe des bénéficiaires, codes bancaires, codes reçus par SMS,
identifiants FranceConnect, pièces d'identité. Les adresses IP ne sont
conservées que sous forme de condensé salé, jamais en clair.

---

## RGPD

- Registre des consentements conservant le **texte exact** affiché au moment
  de la case cochée, avec numéro de version
- Durées de conservation configurables (`config/site.php`, variables
  `RGPD_RETENTION_*`)
- Purge automatique hebdomadaire : les enregistrements expirés sont
  **anonymisés**, pas supprimés — les bilans agrégés remis aux communes
  restent exacts, mais plus rien ne permet d'identifier une personne
- Écran de traitement des demandes d'accès, de rectification, d'effacement,
  d'opposition et de portabilité, avec délai légal d'un mois affiché
- Vérification d'identité obligatoire avant toute communication ou effacement
- Journal d'audit ne contenant jamais de donnée personnelle d'usager :
  uniquement la référence et le statut

---

## Architecture

```text
app/
├── Admin/              Description des champs des formulaires du back-office
├── Console/Commands/   Rappels d'ateliers, purge RGPD, clôture
├── Enums/              Statuts, rôles, permissions, niveaux, types
├── Http/
│   ├── Controllers/
│   │   ├── Admin/      Back-office
│   │   ├── Auth/       Connexion, mot de passe, vérification d'adresse
│   │   └── Site/       Site public
│   ├── Middleware/     En-têtes de sécurité, compte actif, permissions
│   └── Requests/       Validation (Form Requests)
├── Models/
│   └── Concerns/       Slug, référence, audit, anonymisation, auteur
├── Notifications/      Courriels
├── Policies/           Autorisations
├── Services/           Logique métier
└── Support/            Confidentialité, export CSV

resources/
├── js/modules/         Accessibilité, menu, cookies, formulaires
├── sass/               abstracts, base, layout, components, pages
└── views/
    ├── admin/          Back-office
    ├── auth/           Connexion
    ├── components/     Composants Blade accessibles
    ├── layouts/        Site, administration, authentification, impression
    ├── partials/       En-tête, pied de page, menus
    └── site/           Site public

lang/fr/                Tous les textes de l'application
```

### Deux écarts assumés par rapport au codex

**`app/Http/Controllers/Site/` au lieu de `Public/`.** `public` est un mot
réservé de PHP : il ne peut pas servir de segment d'espace de noms.

**`lang/fr/` au lieu de `resources/lang/fr/`.** C'est l'emplacement standard
depuis Laravel 9.

Les traductions ont été installées avec `laravel-lang/common` puis complétées
par des fichiers propres au projet (`site`, `admin`, `mail`, `consent`,
`rgpd`, `enums`, `validation_custom`). Aucun texte métier n'est écrit dans un
contrôleur ni dans une vue.

---

## Tests

```bash
php artisan test
```

102 tests, 265 assertions. Ils s'exécutent sur MySQL — comme la production —
car certaines statistiques utilisent `DATE_FORMAT`, absent de SQLite.

Créez au préalable la base de test :

```sql
CREATE DATABASE conseillernumerique_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Ce que les tests vérifient, entre autres :

- toutes les pages publiques répondent, y compris le plan du site XML
- une personne **sans adresse électronique** peut demander un rendez-vous et
  s'inscrire à un atelier
- la liste d'attente se remplit et se vide correctement quand une place se
  libère, y compris l'envoi de la notification
- l'atelier bascule automatiquement en « complet »
- les transitions de statut interdites sont refusées
- le champ leurre et le délai minimal bloquent les envois automatisés
- chaque rôle n'accède qu'à ce qui le concerne
- un compte ne peut ni se supprimer, ni modifier son propre rôle
- l'anonymisation RGPD vide les données sans supprimer la ligne
- le journal d'audit ne contient aucune donnée personnelle d'usager
- les exports CSV neutralisent les formules de tableur

---

## Documentation complémentaire

- [`docs/deploiement.md`](docs/deploiement.md) — mise en production sur
  Debian, Nginx, PHP-FPM, Supervisor, HTTPS
- [`docs/sauvegarde.md`](docs/sauvegarde.md) — sauvegarde, chiffrement et
  procédure de restauration
- [`docs/administration.md`](docs/administration.md) — guide d'usage du
  back-office, écrit pour l'administrateur du site
- [`CHANGELOG.md`](CHANGELOG.md) — journal des versions
- [`CONTRIBUTING.md`](CONTRIBUTING.md) — conventions de développement
- [`codex-conseiller-numerique.md`](codex-conseiller-numerique.md) — cahier
  des charges d'origine

---

## Licence

Code sous licence MIT. Les contenus rédactionnels et les fiches pratiques
appartiennent à l'éditeur du site.
