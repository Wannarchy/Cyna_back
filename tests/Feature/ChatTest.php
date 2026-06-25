<?php

namespace Tests\Feature;

use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use CreatesUsers;

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/chat', [
            'user_message' => 'Bonjour',
        ])->assertUnauthorized();
    }

    public function test_store_creates_chat_log_with_session_id(): void
    {
        $user = $this->actingAsUser();

        $response = $this->postJson('/api/chat', [
            'user_message' => 'Bonjour',
            'session_id' => 'sess-test-123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.session_id', 'sess-test-123')
            ->assertJsonPath('data.user_message', 'Bonjour')
            ->assertJsonStructure(['data' => ['bot_response', 'created_at']]);

        $this->assertDatabaseHas('chat_logs', [
            'user_id' => $user->id,
            'session_id' => 'sess-test-123',
            'user_message' => 'Bonjour',
        ]);
    }

    public function test_store_bot_response_for_subscription_keyword(): void
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/chat', [
            'user_message' => 'Comment résilier mon abonnement ?',
        ]);

        $response->assertCreated();
        $this->assertStringContainsString(
            'Mes abonnements',
            $response->json('data.bot_response')
        );
    }

    public function test_store_bot_response_for_pricing_keyword(): void
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/chat', [
            'user_message' => 'Quels sont vos tarifs ?',
        ]);

        $response->assertCreated();
        $this->assertStringContainsString(
            'catalogue',
            $response->json('data.bot_response')
        );
    }

    public function test_store_bot_response_fallback_for_unknown_message(): void
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/chat', [
            'user_message' => 'xyzzy random question',
        ]);

        $response->assertCreated();
        $this->assertStringContainsString(
            'formulaire de contact',
            $response->json('data.bot_response')
        );
    }
}
