<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La création du premier compte se fait en production, sur un hébergement où
 * Tinker ne fonctionne pas. Elle doit donc rester fiable sans intervention.
 */
class CreateAdminAccountCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    #[Test]
    public function elle_cree_un_compte_super_administrateur_actif(): void
    {
        $this->artisan('compte:creer', [
            '--nom' => 'David Grougi',
            '--email' => 'david@exemple.test',
        ])->assertSuccessful();

        $user = User::where('email', 'david@exemple.test')->firstOrFail();

        $this->assertSame('David Grougi', $user->name);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->isSuperAdmin());
    }

    #[Test]
    public function le_courriel_est_marque_verifie(): void
    {
        // Sans cela, le compte resterait bloqué sur l'écran de vérification,
        // dont le courriel ne part pas tant que la messagerie n'est pas réglée.
        $this->artisan('compte:creer', [
            '--nom' => 'David Grougi',
            '--email' => 'david@exemple.test',
        ])->assertSuccessful();

        $this->assertNotNull(User::where('email', 'david@exemple.test')->firstOrFail()->email_verified_at);
    }

    #[Test]
    public function le_mot_de_passe_engendre_est_affiche_une_fois(): void
    {
        $this->artisan('compte:creer', [
            '--nom' => 'David Grougi',
            '--email' => 'david@exemple.test',
        ])
            ->expectsOutputToContain('Mot de passe')
            ->assertSuccessful();
    }

    #[Test]
    public function le_mot_de_passe_impose_n_est_jamais_reaffiche(): void
    {
        // Le réafficher le recopierait dans le journal du terminal, alors
        // qu'il figure déjà dans l'historique du shell.
        $this->artisan('compte:creer', [
            '--nom' => 'David Grougi',
            '--email' => 'david@exemple.test',
            '--mot-de-passe' => 'MotDePasseTresLong2026',
        ])
            ->doesntExpectOutputToContain('MotDePasseTresLong2026')
            ->assertSuccessful();
    }

    #[Test]
    public function le_mot_de_passe_est_hache(): void
    {
        $this->artisan('compte:creer', [
            '--nom' => 'David Grougi',
            '--email' => 'david@exemple.test',
            '--mot-de-passe' => 'MotDePasseTresLong2026',
        ])->assertSuccessful();

        $this->assertNotSame(
            'MotDePasseTresLong2026',
            User::where('email', 'david@exemple.test')->firstOrFail()->password,
        );
    }

    #[Test]
    public function elle_accepte_un_autre_role(): void
    {
        $this->artisan('compte:creer', [
            '--nom' => 'Camille',
            '--email' => 'camille@exemple.test',
            '--role' => 'editor',
        ])->assertSuccessful();

        $this->assertFalse(User::where('email', 'camille@exemple.test')->firstOrFail()->isSuperAdmin());
    }

    #[Test]
    public function elle_refuse_un_role_inconnu(): void
    {
        $this->artisan('compte:creer', [
            '--nom' => 'David Grougi',
            '--email' => 'david@exemple.test',
            '--role' => 'chef',
        ])->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function elle_refuse_une_adresse_deja_utilisee(): void
    {
        $this->artisan('compte:creer', [
            '--nom' => 'David Grougi',
            '--email' => 'david@exemple.test',
        ])->assertSuccessful();

        $this->artisan('compte:creer', [
            '--nom' => 'Un autre',
            '--email' => 'david@exemple.test',
        ])->assertFailed();

        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function elle_refuse_un_mot_de_passe_trop_court(): void
    {
        $this->artisan('compte:creer', [
            '--nom' => 'David Grougi',
            '--email' => 'david@exemple.test',
            '--mot-de-passe' => 'court',
        ])->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function elle_refuse_un_nom_ou_une_adresse_manquants(): void
    {
        $this->artisan('compte:creer', ['--email' => 'david@exemple.test'])->assertFailed();
        $this->artisan('compte:creer', ['--nom' => 'David Grougi'])->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function la_creation_n_est_pas_journalisee(): void
    {
        // Le premier compte n'a pas d'auteur à qui être imputé : une entrée
        // d'audit anonyme brouillerait la piste plutôt que de l'établir.
        $this->artisan('compte:creer', [
            '--nom' => 'David Grougi',
            '--email' => 'david@exemple.test',
        ])->assertSuccessful();

        $this->assertDatabaseCount('audit_logs', 0);
    }

    #[Test]
    public function elle_indique_quoi_faire_si_les_roles_manquent(): void
    {
        Role::query()->delete();

        $this->artisan('compte:creer', [
            '--nom' => 'David Grougi',
            '--email' => 'david@exemple.test',
        ])
            ->expectsOutputToContain('db:seed')
            ->assertFailed();
    }
}
