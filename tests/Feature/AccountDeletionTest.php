<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ChatLog;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    private const PASSWORD = 'Password1!';

    public function test_user_can_delete_own_account(): void
    {
        $user = User::factory()->create([
            'mot_de_passe' => Hash::make(self::PASSWORD),
        ]);

        UserAddress::create([
            'user_id' => $user->id,
            'label' => 'Maison',
            'usage_type' => 'billing',
            'prenom' => 'Jean',
            'nom' => 'Dupont',
            'adresse1' => '1 rue Test',
            'ville' => 'Paris',
            'code_postal' => '75001',
            'pays' => 'FR',
            'is_default' => true,
        ]);

        ChatLog::create([
            'user_id' => $user->id,
            'session_id' => 'sess-test',
            'user_message' => 'Bonjour',
            'bot_response' => 'Salut',
            'created_at' => now(),
        ]);

        $this->actingAs($user);

        $this->deleteJson('/api/profile', [
            'current_password' => self::PASSWORD,
            'confirmation' => 'SUPPRIMER',
        ])->assertOk();

        $this->assertDatabaseMissing('utilisateurs', ['id' => $user->id]);
        $this->assertDatabaseMissing('user_addresses', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('chat_logs', ['user_id' => $user->id]);
    }

    public function test_admin_cannot_delete_account_via_self_service(): void
    {
        $admin = User::factory()->admin()->create([
            'mot_de_passe' => Hash::make(self::PASSWORD),
        ]);

        $this->actingAs($admin);

        $this->deleteJson('/api/profile', [
            'current_password' => self::PASSWORD,
            'confirmation' => 'SUPPRIMER',
        ])->assertStatus(422);

        $this->assertDatabaseHas('utilisateurs', ['id' => $admin->id]);
    }

    public function test_deletion_anonymizes_retained_order_logs(): void
    {
        $user = User::factory()->create([
            'mot_de_passe' => Hash::make(self::PASSWORD),
        ]);

        ActivityLog::create([
            'actor_type' => ActivityLog::ACTOR_USER,
            'user_id' => $user->id,
            'action' => 'order.create',
            'ip' => '203.0.113.1',
            'created_at' => now(),
        ]);

        $this->actingAs($user);

        $this->deleteJson('/api/profile', [
            'current_password' => self::PASSWORD,
            'confirmation' => 'SUPPRIMER',
        ])->assertOk();

        $this->assertDatabaseHas('logs', [
            'action' => 'order.create',
            'user_id' => null,
            'ip' => null,
        ]);
    }
}
