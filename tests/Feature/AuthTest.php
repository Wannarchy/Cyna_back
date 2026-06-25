<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    private const PASSWORD = 'Password1!';

    public function test_register_creates_unverified_user_and_returns_token(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/register', [
            'prenom' => 'Jean',
            'nom' => 'Dupont',
            'email' => 'jean@example.com',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.email', 'jean@example.com');

        $this->assertDatabaseHas('utilisateurs', [
            'email' => 'jean@example.com',
            'est_confirme' => false,
        ]);
    }

    public function test_login_returns_token_for_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'mot_de_passe' => Hash::make(self::PASSWORD),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => self::PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'mot_de_passe' => Hash::make(self::PASSWORD),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'WrongPass1!',
        ])->assertUnauthorized();
    }

    public function test_login_rejects_blocked_user(): void
    {
        User::factory()->blocked()->create([
            'email' => 'blocked@example.com',
            'mot_de_passe' => Hash::make(self::PASSWORD),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'blocked@example.com',
            'password' => self::PASSWORD,
        ])->assertUnauthorized();
    }

    public function test_logout_revokes_token(): void
    {
        $user = $this->actingAsUser();

        $this->postJson('/api/auth/logout')->assertOk();

        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_verify_email_confirms_account(): void
    {
        $user = User::factory()->unverified()->create();
        $token = $user->issueEmailVerificationToken();

        $this->postJson('/api/auth/verify-email', [
            'id' => $user->id,
            'token' => $token,
        ])->assertOk();

        $this->assertTrue($user->fresh()->est_confirme);
    }
}
