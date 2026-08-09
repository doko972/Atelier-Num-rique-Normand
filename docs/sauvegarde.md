# Sauvegarde et restauration

Les sauvegardes contiennent des données personnelles : noms, numéros de
téléphone, besoins exprimés. Elles doivent être **chiffrées** et protégées
au même titre que la base elle-même.

---

## Ce qu'il faut sauvegarder

| Élément | Chemin | Fréquence |
| --- | --- | --- |
| Base de données | MySQL / MariaDB | quotidienne |
| Fichiers téléversés | `storage/app/public` | quotidienne |
| Configuration | `.env` | à chaque modification |
| Journaux | `storage/logs` | hebdomadaire |

Le code source n'a pas besoin d'être sauvegardé : il vit dans le dépôt Git.
Le fichier `.env`, lui, n'est **jamais** versionné : il doit être sauvegardé
séparément, et chiffré.

---

## Script de sauvegarde

`/usr/local/bin/sauvegarde-conseillernumerique.sh` :

```bash
#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/conseillernumerique"
BACKUP_DIR="/var/backups/conseillernumerique"
RETENTION_DAYS=30
DATE=$(date +%Y-%m-%d-%H%M)
GPG_RECIPIENT="sauvegardes@votre-domaine.fr"

DB_NAME=$(grep '^DB_DATABASE=' "$APP_DIR/.env" | cut -d '=' -f2-)
DB_USER=$(grep '^DB_USERNAME=' "$APP_DIR/.env" | cut -d '=' -f2-)
DB_PASS=$(grep '^DB_PASSWORD=' "$APP_DIR/.env" | cut -d '=' -f2-)

mkdir -p "$BACKUP_DIR"

# --- Base de données ---------------------------------------------------------
# --single-transaction évite de verrouiller les tables pendant l'export : le
# site reste utilisable durant la sauvegarde.
mysqldump \
    --single-transaction \
    --quick \
    --default-character-set=utf8mb4 \
    --user="$DB_USER" \
    --password="$DB_PASS" \
    "$DB_NAME" \
    | gzip \
    | gpg --encrypt --recipient "$GPG_RECIPIENT" --trust-model always \
    > "$BACKUP_DIR/base-$DATE.sql.gz.gpg"

# --- Fichiers téléversés -----------------------------------------------------
tar -czf - -C "$APP_DIR/storage/app" public \
    | gpg --encrypt --recipient "$GPG_RECIPIENT" --trust-model always \
    > "$BACKUP_DIR/fichiers-$DATE.tar.gz.gpg"

# --- Configuration -----------------------------------------------------------
gpg --encrypt --recipient "$GPG_RECIPIENT" --trust-model always \
    --output "$BACKUP_DIR/env-$DATE.gpg" "$APP_DIR/.env"

# --- Purge des sauvegardes trop anciennes ------------------------------------
find "$BACKUP_DIR" -type f -name '*.gpg' -mtime "+$RETENTION_DAYS" -delete

# --- Copie externe -----------------------------------------------------------
# Une sauvegarde restée sur le serveur ne protège de rien : un incendie, un
# rançongiciel ou une erreur d'exploitation emporterait les deux.
rsync -az --delete "$BACKUP_DIR/" sauvegarde@serveur-distant:/sauvegardes/conseillernumerique/

echo "Sauvegarde $DATE terminée."
```

```bash
sudo chmod 700 /usr/local/bin/sauvegarde-conseillernumerique.sh
```

Le script est en mode 700 : il contient de quoi lire les identifiants de la
base.

### Clé de chiffrement

```bash
gpg --full-generate-key       # sur un poste sûr, PAS sur le serveur
gpg --export --armor sauvegardes@votre-domaine.fr > cle-publique.asc

# Sur le serveur, seule la clé PUBLIQUE est importée :
gpg --import cle-publique.asc
```

La clé privée ne doit jamais se trouver sur le serveur : sinon, quiconque
compromet le serveur déchiffre aussi toutes les sauvegardes.

**Conservez la clé privée hors ligne, en deux exemplaires, dans deux lieux
distincts.** Une sauvegarde chiffrée dont la clé est perdue ne vaut rien.

---

## Planification et notification d'échec

```cron
30 2 * * * /usr/local/bin/sauvegarde-conseillernumerique.sh >> /var/log/sauvegarde-conseillernumerique.log 2>&1 || echo "ÉCHEC de la sauvegarde du site Conseiller Numérique le $(date)" | mail -s "ALERTE sauvegarde" admin@votre-domaine.fr
```

Une sauvegarde qui échoue en silence est pire que pas de sauvegarde : elle
donne un faux sentiment de sécurité. La notification d'échec n'est pas
facultative.

---

## Restauration

### Base de données

```bash
sudo -u conseiller php artisan down

gpg --decrypt /var/backups/conseillernumerique/base-2026-08-06-0230.sql.gz.gpg \
    | gunzip \
    | mysql --user=conseiller --password conseillernumerique

sudo -u conseiller php artisan optimize:clear
sudo -u conseiller php artisan optimize
sudo -u conseiller php artisan up
```

### Fichiers téléversés

```bash
gpg --decrypt /var/backups/conseillernumerique/fichiers-2026-08-06-0230.tar.gz.gpg \
    | tar -xzf - -C /var/www/conseillernumerique/storage/app

sudo chown -R conseiller:www-data /var/www/conseillernumerique/storage/app
```

### Configuration

```bash
gpg --decrypt /var/backups/conseillernumerique/env-2026-08-06-0230.gpg \
    > /var/www/conseillernumerique/.env

sudo chown conseiller:conseiller /var/www/conseillernumerique/.env
sudo chmod 640 /var/www/conseillernumerique/.env
```

`APP_KEY` doit être identique à celle en vigueur au moment de la sauvegarde :
les secrets de double authentification sont chiffrés avec elle, et les
condensés d'adresses IP en dépendent.

---

## Test périodique de restauration

**Une sauvegarde n'existe vraiment que si sa restauration a été testée.**
Prévoyez ce test tous les trimestres, sur un serveur distinct.

```bash
# Sur un serveur de test, base vierge
gpg --decrypt base-du-jour.sql.gz.gpg | gunzip | mysql --user=test --password test_restauration
```

Vérifications à mener ensuite :

- [ ] Le nombre d'ateliers, d'inscriptions et de demandes correspond
- [ ] La page d'accueil s'affiche correctement
- [ ] Une connexion à l'administration fonctionne
- [ ] Les fichiers téléversés sont accessibles
- [ ] La date de la sauvegarde restaurée est bien celle attendue

Consignez la date de chaque test et son résultat : c'est un élément attendu
en cas de contrôle.

---

## Sauvegardes et RGPD

Les sauvegardes contiennent des données personnelles. En conséquence :

- elles sont chiffrées, sur le serveur comme sur la copie externe ;
- leur durée de conservation est limitée (30 jours par défaut) ;
- l'accès aux sauvegardes est réservé aux personnes habilitées ;
- la copie externe est hébergée dans l'Union européenne.

Une demande d'effacement s'applique à la base en production. Les sauvegardes
antérieures ne sont pas modifiées — c'est admis — mais leur rotation les fait
disparaître dans le délai de conservation. Mentionnez ce point dans le
registre des traitements.
