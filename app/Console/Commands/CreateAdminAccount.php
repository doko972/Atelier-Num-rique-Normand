<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Crée un compte de l'espace d'administration.
 *
 * Cette commande existe parce que la méthode habituelle — `php artisan tinker`
 * — est inutilisable sur un hébergement mutualisé : `shell_exec`, `exec` et
 * `proc_open` y sont désactivés, et Psy Shell en dépend.
 *
 * Elle est volontairement non interactive : les invites de saisie de Laravel
 * reposent elles aussi sur `stty`, donc sur `exec`.
 *
 * Le mot de passe est engendré par défaut plutôt que passé en argument : une
 * ligne de commande atterrit dans `~/.bash_history`, lisible par quiconque
 * obtient ensuite l'accès au compte SSH.
 */
class CreateAdminAccount extends Command
{
    protected $signature = 'compte:creer
                            {--nom= : Nom affiché du compte}
                            {--email= : Adresse de connexion}
                            {--role=super_admin : Rôle attribué}
                            {--mot-de-passe= : Mot de passe imposé (déconseillé : il reste dans l’historique du shell)}';

    protected $description = 'Crée un compte d’administration, sans passer par Tinker.';

    public function handle(): int
    {
        $role = Role::query()->where('slug', $this->stringOption('role'))->first();

        if ($role === null) {
            $this->components->error(sprintf(
                'Rôle « %s » introuvable. Rôles disponibles : %s.',
                $this->stringOption('role'),
                implode(', ', array_column(UserRole::cases(), 'value')),
            ));

            $this->line('Si aucun rôle n’existe, lancez d’abord : php artisan db:seed --force');

            return self::FAILURE;
        }

        $password = $this->stringOption('mot-de-passe') ?: Str::password(20);

        $validator = Validator::make([
            'name' => $this->stringOption('nom'),
            'email' => $this->stringOption('email'),
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:180', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        // Créer le premier compte n'est pas une action d'administration
        // tracée : il n'existe encore aucun auteur à qui l'imputer.
        $user = AuditLog::withoutRecording(function () use ($role, $password): User {
            $user = User::create([
                'role_id' => $role->id,
                'name' => $this->stringOption('nom'),
                'email' => $this->stringOption('email'),
                'password' => $password,
                'is_active' => true,
            ]);

            // Sans cela, le compte serait bloqué sur l'écran de vérification,
            // dont le courriel ne part pas tant que la messagerie n'est pas
            // réglée. L'enregistrement se fait dans la même parenthèse, sinon
            // il produirait à lui seul une entrée d'audit sans auteur.
            $user->markEmailAsVerified();

            return $user;
        });

        $this->components->info('Compte créé.');

        $this->components->twoColumnDetail('Nom', $user->name);
        $this->components->twoColumnDetail('Adresse', $user->email);
        $this->components->twoColumnDetail('Rôle', $role->name);

        if (! $this->stringOption('mot-de-passe')) {
            $this->newLine();
            $this->components->twoColumnDetail('<fg=yellow>Mot de passe</>', "<fg=yellow>{$password}</>");
            $this->line('  Notez-le maintenant : il n’est pas stocké en clair et ne sera plus affiché.');
        }

        $this->newLine();
        $this->line('  Connexion : '.route('admin.login'));

        return self::SUCCESS;
    }

    private function stringOption(string $name): string
    {
        $value = $this->option($name);

        return is_string($value) ? trim($value) : '';
    }
}
