# Guide de déploiement

Deux hébergements sont décrits :

- les sections **1 à 10** couvrent un serveur dédié ou virtuel (Debian 12 ou
  13, Nginx, PHP-FPM, MariaDB) ;
- la section **11** couvre un hébergement mutualisé de type Hostinger, où
  l'accès est restreint et plusieurs commandes habituelles sont indisponibles.

C'est cette dernière qui correspond à la mise en ligne actuelle.

---

## 1. Préparation du serveur

```bash
sudo apt update && sudo apt upgrade -y

sudo apt install -y nginx mariadb-server supervisor git unzip \
    php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
    php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath
```

Composer :

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

Node.js 20 :

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### Utilisateur dédié

Le site ne tourne jamais sous `root` :

```bash
sudo adduser --system --group --home /var/www/conseillernumerique conseiller
```

---

## 2. Base de données

```bash
sudo mysql_secure_installation
sudo mysql
```

```sql
CREATE DATABASE conseillernumerique CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'conseiller'@'localhost' IDENTIFIED BY 'un-mot-de-passe-long-et-aleatoire';
GRANT ALL PRIVILEGES ON conseillernumerique.* TO 'conseiller'@'localhost';
FLUSH PRIVILEGES;
```

Le compte applicatif n'a de droits que sur sa propre base : jamais
`GRANT ALL ON *.*`.

---

## 3. Déploiement du code

```bash
sudo -u conseiller git clone <url-du-depot> /var/www/conseillernumerique
cd /var/www/conseillernumerique

sudo -u conseiller composer install --no-dev --optimize-autoloader
sudo -u conseiller npm ci
sudo -u conseiller npm run build

sudo -u conseiller cp .env.example .env
sudo -u conseiller php artisan key:generate
```

### Réglages de production dans `.env`

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.fr

DB_DATABASE=conseillernumerique
DB_USERNAME=conseiller
DB_PASSWORD=un-mot-de-passe-long-et-aleatoire

SESSION_SECURE_COOKIE=true
SECURITY_FORCE_HTTPS=true
SECURITY_CSP_ENABLED=true

MAIL_MAILER=smtp
MAIL_HOST=…
MAIL_PORT=587
MAIL_USERNAME=…
MAIL_PASSWORD=…
MAIL_FROM_ADDRESS=contact@votre-domaine.fr

ADMIN_NOTIFICATION_EMAIL=contact@votre-domaine.fr
RGPD_CONTACT_EMAIL=rgpd@votre-domaine.fr

QUEUE_CONNECTION=database
CACHE_STORE=database
LOG_LEVEL=warning
```

`APP_DEBUG=false` est impératif : en `true`, une page d'erreur exposerait la
configuration, les requêtes SQL et des extraits de code.

### Migrations et amorçage

```bash
sudo -u conseiller php artisan migrate --force
sudo -u conseiller php artisan db:seed --force
```

`db:seed` sans classe est sûr : `DatabaseSeeder` ne pose que ce qui est vrai —
rôles, paramètres, pages légales, communes, catalogue de services et
ressources — et chaque seeder est idempotent.

Les contenus inventés — ateliers d'exemple, comptes de démonstration — vivent
dans `DemoSeeder`, appelé séparément et qui refuse de s'exécuter lorsque
`APP_ENV=production`.

### Premier compte d'administration

```bash
sudo -u conseiller php artisan compte:creer \
    --nom="Prénom Nom" --email="vous@votre-domaine.fr"
```

Le mot de passe est engendré et affiché une seule fois : notez-le avant de
fermer le terminal. L'option `--mot-de-passe=` permet de l'imposer, au prix
de le laisser dans l'historique du shell.

`--role=` accepte `super_admin`, `admin`, `adviser`, `editor` ou `viewer`.
Sans elle, le compte est créé en super administrateur.

La commande est non interactive à dessein : sur un hébergement mutualisé,
`exec` et `shell_exec` sont désactivés, ce qui rend inutilisables aussi bien
Tinker que les invites de saisie de Laravel.

### Optimisations et permissions

```bash
sudo -u conseiller php artisan storage:link
sudo -u conseiller php artisan optimize

sudo chown -R conseiller:www-data /var/www/conseillernumerique
sudo find /var/www/conseillernumerique -type f -exec chmod 644 {} \;
sudo find /var/www/conseillernumerique -type d -exec chmod 755 {} \;
sudo chmod -R 775 storage bootstrap/cache
sudo chmod 640 .env
```

---

## 4. Nginx

`/etc/nginx/sites-available/conseillernumerique` :

```nginx
server {
    listen 80;
    server_name votre-domaine.fr www.votre-domaine.fr;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name votre-domaine.fr www.votre-domaine.fr;

    root /var/www/conseillernumerique/public;
    index index.php;

    charset utf-8;

    ssl_certificate     /etc/letsencrypt/live/votre-domaine.fr/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/votre-domaine.fr/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers off;

    # Les autres en-têtes de sécurité sont posés par l'application, afin de
    # rester cohérents entre les environnements.
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    client_max_body_size 12M;

    gzip on;
    gzip_types text/css application/javascript application/json image/svg+xml;
    gzip_min_length 512;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Les fichiers construits par Vite portent une empreinte dans leur nom :
    # ils peuvent être mis en cache très longtemps sans risque.
    location /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { try_files $uri /index.php?$query_string; }

    error_page 404 /index.php;
}
```

```bash
sudo ln -s /etc/nginx/sites-available/conseillernumerique /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

---

## 5. HTTPS

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d votre-domaine.fr -d www.votre-domaine.fr
```

Le renouvellement automatique est installé par Certbot. Vérifiez-le :

```bash
sudo certbot renew --dry-run
```

---

## 6. Files d'attente (Supervisor)

Les courriels partent en file d'attente : sans processus d'exécution, aucune
confirmation ne serait envoyée.

`/etc/supervisor/conf.d/conseillernumerique-worker.conf` :

```ini
[program:conseillernumerique-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/conseillernumerique/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=conseiller
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/conseillernumerique/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start conseillernumerique-worker:*
```

---

## 7. Tâches planifiées

Une seule ligne de cron suffit :

```bash
sudo crontab -u conseiller -e
```

```cron
* * * * * cd /var/www/conseillernumerique && php artisan schedule:run >> /dev/null 2>&1
```

Elle déclenche les rappels d'ateliers, la clôture des ateliers passés, la
purge RGPD hebdomadaire et le nettoyage des jetons expirés.

---

## 8. Rotation des journaux

`/etc/logrotate.d/conseillernumerique` :

```
/var/www/conseillernumerique/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 0640 conseiller www-data
    su conseiller www-data
}
```

Les canaux `daily` de Laravel gèrent déjà leur propre rétention (voir
`config/logging.php`) ; logrotate sert de filet pour les journaux restants.

---

## 9. Mise à jour

```bash
cd /var/www/conseillernumerique

php artisan down --render="errors::503"

sudo -u conseiller git pull
sudo -u conseiller composer install --no-dev --optimize-autoloader
sudo -u conseiller npm ci && sudo -u conseiller npm run build
sudo -u conseiller php artisan migrate --force
sudo -u conseiller php artisan db:seed --class=RoleSeeder --force

sudo -u conseiller php artisan optimize
sudo supervisorctl restart conseillernumerique-worker:*

php artisan up
```

`RoleSeeder` est rejoué à chaque mise à jour : il est idempotent et met à
jour la matrice des permissions si de nouvelles capacités sont apparues.

**Prenez une sauvegarde avant toute migration** (voir
[`sauvegarde.md`](sauvegarde.md)). Une migration destructive doit être
signalée dans le `CHANGELOG.md`.

---

## 10. Vérifications après mise en ligne

- [ ] `APP_DEBUG=false` et `APP_ENV=production`
- [ ] `https://votre-domaine.fr` répond, redirection depuis HTTP active
- [ ] `/administration/connexion` accessible, connexion fonctionnelle
- [ ] Les comptes de démonstration n'existent pas
- [ ] Un formulaire de rendez-vous aboutit et le courriel arrive
- [ ] `php artisan queue:work` tourne (`supervisorctl status`)
- [ ] `sudo -u conseiller php artisan schedule:list` affiche les tâches
- [ ] `/sitemap.xml` et `/robots.txt` répondent
- [ ] Numéro de téléphone, adresse et mentions légales renseignés dans
      **Paramètres du site**
- [ ] Une sauvegarde a été prise et **une restauration a été testée**

---

## 11. Variante : hébergement mutualisé (Hostinger)

Un mutualisé n'est pas un petit VPS : c'est un environnement bridé. Ni
Supervisor, ni Certbot, ni Nginx à configurer — mais aussi pas de Node, et
plusieurs fonctions PHP désactivées. Les quatre premiers points ci-dessous
sont ceux qui font perdre du temps ; les autres suivent le déroulé habituel.

### 11.1 La racine du domaine doit pointer sur `public/`

C'est la cause d'une **erreur 403** — et non 404, ce qui égare. Le serveur
arrive dans un dossier sans `index.php`, et le `Options -Indexes` de
`public/.htaccess` lui interdit d'en lister le contenu : il refuse l'accès.

Deux dispositions possibles :

- régler le dossier racine du domaine sur `.../public` depuis hPanel ;
- sinon, placer le projet dans un dossier frère de `public_html` et copier le
  *contenu* de `public/` dans `public_html`, en corrigeant les chemins de
  `vendor/autoload.php`, `bootstrap/app.php` et `maintenance.php` dans
  `index.php`.

La première est préférable : le code reste hors de l'espace web.

### 11.2 Deux versions de PHP coexistent

Le `php` du SSH n'est pas celui qui sert le site. Les deux doivent être en
**8.3 minimum** (`"php": "^8.3"` dans `composer.json`).

```bash
php -v                      # souvent une version plus ancienne
ls -d /opt/alt/php* 2>/dev/null
echo 'export PATH=/opt/alt/php83/usr/bin:$PATH' >> ~/.bashrc
source ~/.bashrc
```

Passer par le `PATH` plutôt que par un alias : Composer est un script lancé
via `#!/usr/bin/env php`, il suivra le bon binaire. Avec un alias,
`composer install` continuerait de tourner sur l'ancienne version.

La version PHP **du site** se règle séparément, dans hPanel.

### 11.3 Tinker ne fonctionne pas

Psy Shell repose sur `shell_exec`, désactivé sur ce type d'hébergement :

```
Error  Call to undefined function shell_exec().
```

D'où la commande dédiée, non interactive — les invites de saisie de Laravel
reposent elles aussi sur `exec`, via `stty` :

```bash
php artisan compte:creer --nom="Prénom Nom" --email="vous@votre-domaine.fr"
```

Le mot de passe est engendré et affiché une seule fois. L'option
`--mot-de-passe=` existe, mais laisse le mot de passe dans
`~/.bash_history` : à réserver aux cas où c'est indispensable.

### 11.4 Les assets doivent être construits ailleurs

`public/build` est ignoré par Git (`.gitignore`) et npm n'est pas disponible.
Sans ce dossier, le site s'affiche sans aucun style.

```bash
npm run build          # sur votre machine
```

puis téléverser `public/build` par FTP ou depuis le gestionnaire de fichiers.
Il faut le refaire à chaque modification des feuilles de style.

### 11.5 Déroulé complet

```bash
git clone <url-du-depot> ~/domains/votre-domaine.fr/laravel
cd ~/domains/votre-domaine.fr/laravel

cp .env.example .env      # puis renseigner les valeurs de production
composer install --no-dev --optimize-autoloader
php artisan key:generate

php artisan migrate --force
php artisan db:seed --force

php artisan storage:link
php artisan optimize

find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 775 storage bootstrap/cache
chmod 600 .env
```

`db:seed` sans classe est sûr : `DatabaseSeeder` ne pose que du réel. Les
contenus de démonstration vivent dans `DemoSeeder`, qu'on n'appelle jamais
ici.

**Jamais de `chmod 777`.** Le serveur tourne en suEXEC et refuse tout dossier
accessible en écriture au groupe ou à tous : un `777` sur `storage` provoque
lui aussi une erreur 403.

### 11.6 Réglages `.env` propres au mutualisé

```dotenv
QUEUE_CONNECTION=sync
CACHE_STORE=database
```

`sync` est impératif : sans Supervisor, une file d'attente `database` ne
serait jamais dépilée et **aucun courriel ne partirait**. Les envois se font
donc pendant la requête, ce qui allonge un peu la soumission des formulaires.

### 11.7 Tâches planifiées

Le cron se déclare dans hPanel, avec le chemin complet du binaire PHP — un
cron n'hérite pas du `PATH` du `.bashrc` :

```
/opt/alt/php83/usr/bin/php ~/domains/votre-domaine.fr/laravel/artisan schedule:run
```

La plupart des plans mutualisés n'acceptent pas la minute comme fréquence.
Toutes les cinq minutes convient : les rappels d'ateliers et la purge RGPD
n'ont pas besoin d'une précision plus fine.
