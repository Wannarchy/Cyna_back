<?php

namespace Tests\Feature;

use App\Models\AdminLoginOtp;
use App\Models\User;
use App\Notifications\AdminLoginOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AdminLoginOtpTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    private const PASSWORD = 'Password1!';

    public function test_admin_login_requires_otp_and_sends_email(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'mot_de_passe' => Hash::make(self::PASSWORD),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => self::PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.requires_otp', true)
            ->assertJsonMissingPath('data.token')
            ->assertJsonStructure(['data' => ['challenge_token', 'expires_at']]);

        $this->assertDatabaseCount('login_otps', 1);
        $this->assertDatabaseHas('login_otps', [
            'user_id' => $admin->id,
        ]);

        Notification::assertSentTo($admin, AdminLoginOtpNotification::class);
    }

    public function test_non_admin_login_does_not_require_otp(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'user@example.com',
            'mot_de_passe' => Hash::make(self::PASSWORD),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => self::PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonMissingPath('data.requires_otp')
            ->assertJsonStructure(['data' => ['token', 'user']]);

        Notification::assertNothingSent();
        $this->assertDatabaseCount('login_otps', 0);
    }

    public function test_admin_can_verify_otp_and_receive_token(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'mot_de_passe' => Hash::make(self::PASSWORD),
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => self::PASSWORD,
        ])->assertOk();

        $challengeToken = $login->json('data.challenge_token');
        $otp = AdminLoginOtp::query()->where('user_id', $admin->id)->firstOrFail();

        $code = '12345678';
        $otp->update(['code_hash' => Hash::make($code)]);

        $response = $this->postJson('/api/auth/verify-admin-otp', [
            'challenge_token' => $challengeToken,
            'code' => $code,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.id', $admin->id)
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertDatabaseCount('login_otps', 0);
    }

    public function test_verify_otp_rejects_invalid_code(): void
    {
        Notification::fake();

        User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'mot_de_passe' => Hash::make(self::PASSWORD),
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => self::PASSWORD,
        ])->assertOk();

        $this->postJson('/api/auth/verify-admin-otp', [
            'challenge_token' => $login->json('data.challenge_token'),
            'code' => '00000000',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Code de vérification incorrect.');
    }

    public function test_verify_otp_rejects_expired_code(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'mot_de_passe' => Hash::make(self::PASSWORD),
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => self::PASSWORD,
        ])->assertOk();

        AdminLoginOtp::query()->where('user_id', $admin->id)->update([
            'expires_at' => now()->subMinute(),
        ]);

        $this->postJson('/api/auth/verify-admin-otp', [
            'challenge_token' => $login->json('data.challenge_token'),
            'code' => '12345678',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Ce code a expiré. Reconnectez-vous pour en recevoir un nouveau.');
    }
}
