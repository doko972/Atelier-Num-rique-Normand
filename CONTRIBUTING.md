# Conventions de développement

---

## Avant de coder

1. Lire le point du codex concerné (`codex-conseiller-numerique.md`).
2. Vérifier ce qui existe déjà : un service, un enum ou un composant Blade
   couvre peut-être déjà le besoin.
3. Créer une branche : `git switch -c fonctionnalite/nom-court`.

---

## Règles structurantes

### Aucun texte métier dans le code

Tout libellé visible vit dans `lang/fr/`. Aucune chaîne en dur dans un
contrôleur, un service ou une vue.

```php
// Non
return back()->with('status', 'La demande a été mise à jour.');

// Oui
return back()->with('status', __('admin.appointments.updated'));
```

### La validation vit dans les Form Requests

Aucun `$request->validate()` dans un contrôleur pour les formulaires publics.
Les formulaires du back-office peuvent valider en ligne lorsqu'il s'agit d'un
seul champ, mais tout formulaire complet mérite un Form Request.

### Les autorisations passent par les Policies

Le middleware `permission:` protège la navigation. Chaque action individuelle
appelle `$this->authorize()`.

Ne rétablissez pas de contournement global pour le super administrateur : les
Policies contiennent des garde-fous qui doivent s'appliquer à tout le monde
(par exemple, un compte ne peut pas se supprimer lui-même).

### La logique métier vit dans les Services

Un contrôleur valide, délègue, redirige. Dès qu'une opération touche
plusieurs modèles ou déclenche une notification, elle appartient à un service.

Toute opération qui modifie le nombre de places d'un atelier doit passer par
`WorkshopRegistrationService`, dans une transaction avec verrouillage.

### Les statuts sont des enums

Jamais de chaîne libre en base pour un statut. Les transitions autorisées
sont déclarées dans l'enum, et vérifiées avant toute écriture.

---

## Accessibilité : les points non négociables

- Tout champ a un **label visible**. Le seul `placeholder` ne suffit pas.
- Toute image porte un `alt` ; si elle est décorative, `alt=""` **et**
  `aria-hidden="true"`.
- Aucune information n'est portée par la seule couleur. Un statut s'écrit en
  toutes lettres ; la pastille colorée ne fait que renforcer.
- Les cibles cliquables mesurent au moins 48 px.
- Le contraste texte/fond atteint 4,5:1. Vérifiez tout nouveau couple, et
  notez le ratio en commentaire dans `_variables.scss`.
- Toute fonctionnalité reste accessible sans JavaScript. Le JavaScript ajoute
  du confort, jamais une capacité indispensable.
- Un bouton qui ne fonctionne pas sans JavaScript doit être masqué sans
  JavaScript, pas affiché inerte.

Testez au clavier seul : `Tab`, `Entrée`, `Échap`. Si vous ne pouvez pas
accomplir la tâche, le code n'est pas fini.

---

## Données personnelles

Avant d'ajouter une colonne contenant une donnée personnelle :

1. Est-elle **vraiment** nécessaire ? La collecte doit être minimale.
2. L'ajouter à `anonymisedAttributes()` du modèle.
3. L'ajouter à `auditHiddenFields()` : le journal ne doit jamais la contenir.
4. Vérifier qu'une durée de conservation lui est associée.
5. Mettre à jour la page « Politique de confidentialité » (`PageSeeder`).

Ne stockez **jamais** : mot de passe d'usager, code bancaire, code reçu par
SMS, identifiant FranceConnect, copie de pièce d'identité.

Les adresses IP passent toujours par `Privacy::hashIp()`.

---

## Ton rédactionnel

- Vouvoiement, phrases courtes, vocabulaire courant.
- Les sigles sont explicités à leur première occurrence.
- On n'infantilise pas, on ne culpabilise pas.
- Un message d'erreur dit **quoi faire**, pas ce qui a échoué.

```text
Non  : « Le champ email est invalide. »
Oui  : « Vérifiez votre adresse électronique : il manque peut-être le @. »
```

---

## Tests

Tout ajout fonctionnel s'accompagne d'un test. Les noms de test sont en
français et décrivent le comportement attendu :

```php
#[Test]
public function une_personne_sans_adresse_electronique_peut_demander_un_rendez_vous(): void
```

Les tests s'exécutent sur MySQL : créez la base `conseillernumerique_test`.

```bash
php artisan test
./vendor/bin/pint
```

Les deux doivent passer avant toute demande de fusion.

---

## Commits

Messages en français, à l'impératif, décrivant l'effet :

```text
Ajoute la liste d'attente automatique sur les ateliers
Corrige le contraste du badge « complet » sur fond beige
Documente la procédure de restauration
```

Un commit par intention. Ne mélangez pas un correctif et un reformatage.

---

## Ce qu'on ne versionne jamais

`.env`, mots de passe, clés d'API, sauvegardes, données personnelles,
documents transmis par les usagers, contenu de `storage/app/public`.

En cas de doute, vérifiez `.gitignore` avant de valider.
