<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Authentification et autorisations du back-office (codex §23).
 */
class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function un_visiteur_est_redirige_vers_la_connexion(): void
    {
        $this->get('/administration')->assertRedirect(route('admin.login'));
    }

    #[Test]
    public function la_page_de_connexion_est_accessible(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Connexion à l’administration', escape: false);
    }

    #[Test]
    public function un_administrateur_peut_se_connecter(): void
    {
        $user = $this->userWithRole(UserRole::Admin);

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'MotDePasseTest2026!',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    #[Test]
    public function un_mot_de_passe_incorrect_est_refuse(): void
    {
        $user = $this->userWithRole(UserRole::Admin);

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'mauvais-mot-de-passe',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function un_compte_desactive_ne_peut_pas_se_connecter(): void
    {
        $user = $this->userWithRole(UserRole::Admin);
        $user->update(['is_active' => false]);

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'MotDePasseTest2026!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function une_session_ouverte_est_coupee_si_le_compte_est_desactive(): void
    {
        $user = $this->userWithRole(UserRole::Admin);

        $this->actingAs($user)->get('/administration')->assertOk();

        $user->update(['is_active' => false]);

        $this->actingAs($user->fresh())
            ->get('/administration')
            ->assertRedirect(route('admin.login'));
    }

    #[Test]
    public function le_tableau_de_bord_s_affiche_pour_un_administrateur(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Admin))
            ->get('/administration')
            ->assertOk()
            ->assertSee('Tableau de bord');
    }

    #[Test]
    public function un_lecteur_ne_peut_pas_acceder_aux_demandes_de_rendez_vous(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Viewer))
            ->get(route('admin.appointments.index'))
            ->assertForbidden();
    }

    #[Test]
    public function un_editeur_ne_peut_pas_acceder_aux_demandes_de_rendez_vous(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Editor))
            ->get(route('admin.appointments.index'))
            ->assertForbidden();
    }

    #[Test]
    public function un_conseiller_accede_aux_demandes_mais_pas_aux_comptes(): void
    {
        $adviser = $this->userWithRole(UserRole::Adviser);

        $this->actingAs($adviser)->get(route('admin.appointments.index'))->assertOk();
        $this->actingAs($adviser)->get(route('admin.users.index'))->assertForbidden();
    }

    #[Test]
    public function seul_le_super_administrateur_gere_les_comptes(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Admin))
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($this->userWithRole(UserRole::SuperAdmin))
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    #[Test]
    public function un_editeur_accede_aux_contenus(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Editor))
            ->get(route('admin.services.index'))
            ->assertOk();
    }

    #[Test]
    public function un_compte_ne_peut_pas_se_supprimer_lui_meme(): void
    {
        $superAdmin = $this->userWithRole(UserRole::SuperAdmin);

        $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $superAdmin))
            ->assertForbidden();

        $this->assertNotSoftDeleted($superAdmin);
    }

    #[Test]
    public function un_administrateur_ne_peut_pas_agir_sur_un_super_administrateur(): void
    {
        $superAdmin = $this->userWithRole(UserRole::SuperAdmin);

        $admin = User::create([
            'role_id' => $superAdmin->role_id,
            'name' => 'Administrateur secondaire',
            'email' => 'admin-secondaire@test.local',
            'password' => Hash::make('MotDePasseTest2026!'),
            'is_active' => true,
        ]);

        // Deux comptes de même niveau : aucun ne peut agir sur l'autre.
        $this->assertFalse($admin->canManage($superAdmin));
        $this->assertFalse($superAdmin->canManage($admin));
    }

    #[Test]
    public function la_deconnexion_ramene_au_site_public(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Admin))
            ->post(route('admin.logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    #[Test]
    public function les_pages_d_administration_ne_sont_pas_indexables(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Admin))
            ->get('/administration')
            ->assertSee('noindex, nofollow');
    }
}
