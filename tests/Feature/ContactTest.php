<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use CreatesUsers;

    private function validPayload(): array
    {
        return [
            'email' => 'contact@example.com',
            'sujet' => 'Question produit',
            'message' => 'Bonjour, j\'aimerais en savoir plus sur vos offres.',
        ];
    }

    public function test_store_creates_contact_message(): void
    {
        $response = $this->postJson('/api/contact', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('data.email', 'contact@example.com')
            ->assertJsonPath('data.sujet', 'Question produit')
            ->assertJsonPath('data.status', ContactMessage::STATUS_PENDING);

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'contact@example.com',
            'sujet' => 'Question produit',
            'status' => ContactMessage::STATUS_PENDING,
            'user_id' => null,
        ]);
    }

    public function test_store_links_authenticated_user(): void
    {
        $user = $this->actingAsUser();

        $this->postJson('/api/contact', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('contact_messages', [
            'user_id' => $user->id,
            'email' => 'contact@example.com',
        ]);
    }

    public function test_store_validation_errors(): void
    {
        $this->postJson('/api/contact', [
            'email' => 'not-an-email',
            'sujet' => 'ab',
            'message' => 'court',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'sujet', 'message']);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_store_is_rate_limited(): void
    {
        $payload = $this->validPayload();

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/contact', array_merge($payload, [
                'email' => "user{$i}@example.com",
            ]))->assertCreated();
        }

        $this->postJson('/api/contact', array_merge($payload, [
            'email' => 'blocked@example.com',
        ]))->assertStatus(429);
    }
}
