# Guide de déploiement

Mise en production sur un serveur Debian 12 ou 13, avec Nginx, PHP-FPM et
MariaDB. Les chemins et noms d'utilisateur sont à adapter.

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
sudo -u conseiller php artisan db:seed --class=RoleSeeder --force
sudo -u conseiller php artisan db:seed --class=SettingSeeder --force
sudo -u conseiller php artisan db:seed --class=PageSeeder --force
```

N'exécutez **pas** `db:seed` sans classe en production : cela installerait les
contenus de démonstration. `UserSeeder` refuse d'ailleurs de s'exécuter
lorsque `APP_ENV=production`.

### Premier compte d'administration

```bash
sudo -u conseiller php artisan tinker
```

```php
$role = App\Models\Role::where('slug', 'super_admin')->firstOrFail();

$user = App\Models\User::create([
    'role_id'  => $role->id,
    'name'     => 'Prénom Nom',
    'email'    => 'vous@votre-domaine.fr',
    'password' => Hash::make('un-mot-de-passe-long-et-unique'),
    'is_active'=> true,
]);

$user->markEmailAsVerified();
```

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
