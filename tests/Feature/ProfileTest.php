<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use CreatesUsers;

    public function test_profile_requires_authentication(): void
    {
        $this->getJson('/api/profile')->assertUnauthorized();
    }

    public function test_show_returns_authenticated_user(): void
    {
        $user = $this->actingAsUser();

        $this->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_update_profile_names(): void
    {
        $user = $this->actingAsUser();

        $this->putJson('/api/profile', [
            'prenom' => 'Alice',
            'nom' => 'Martin',
        ])->assertOk()
            ->assertJsonPath('message', 'Profil mis à jour.')
            ->assertJsonPath('data.prenom', 'Alice')
            ->assertJsonPath('data.nom', 'Martin');

        $this->assertDatabaseHas('utilisateurs', [
            'id' => $user->id,
            'prenom' => 'Alice',
            'nom' => 'Martin',
        ]);
    }

    public function test_update_password_requires_current_password(): void
    {
        $this->actingAsUser();

        $this->putJson('/api/profile', [
            'current_password' => 'wrong-password',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Mot de passe actuel incorrect.');
    }

    public function test_update_password_changes_password_with_current_password(): void
    {
        $user = $this->createUser([
            'mot_de_passe' => Hash::make('old-password'),
        ]);
        $this->actingAsUser($user);

        $this->putJson('/api/profile', [
            'current_password' => 'old-password',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->mot_de_passe));
    }
}
