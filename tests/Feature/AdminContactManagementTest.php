<?php

namespace Tests\Feature;

use App\Mail\ContactReplyMail;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AdminContactManagementTest extends TestCase
{
    use CreatesUsers;

    private function createContactMessage(array $overrides = []): ContactMessage
    {
        return ContactMessage::create(array_merge([
            'email' => 'client@example.com',
            'sujet' => 'Demande commerciale',
            'message' => 'Bonjour, pouvez-vous me rappeler ?',
            'status' => ContactMessage::STATUS_PENDING,
            'created_at' => now(),
        ], $overrides));
    }

    public function test_admin_can_filter_contact_messages(): void
    {
        $this->actingAsAdmin();
        $matching = $this->createContactMessage(['email' => 'match@example.com']);
        $this->createContactMessage(['email' => 'other@example.com']);

        $response = $this->getJson('/api/admin/contact-messages?email=match');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.data'));
        $response->assertJsonPath('data.data.0.id', $matching->id);
    }

    public function test_admin_can_update_contact_status(): void
    {
        $this->actingAsAdmin();
        $message = $this->createContactMessage();

        $this->patchJson('/api/admin/contact-messages/'.$message->id.'/status', [
            'status' => ContactMessage::STATUS_CLOSED,
        ])->assertOk()
            ->assertJsonPath('data.status', ContactMessage::STATUS_CLOSED)
            ->assertJsonPath('message', 'Statut mis à jour.');
    }

    public function test_admin_can_reply_to_contact_message(): void
    {
        Mail::fake();
        $admin = $this->actingAsAdmin();
        $message = $this->createContactMessage();

        $this->postJson('/api/admin/contact-messages/'.$message->id.'/reply', [
            'reply' => 'Merci pour votre message, nous revenons vers vous rapidement.',
        ])->assertOk()
            ->assertJsonPath('data.mail_sent', true)
            ->assertJsonPath('data.status', ContactMessage::STATUS_REPLIED);

        Mail::assertSent(ContactReplyMail::class);
        $this->assertDatabaseHas('contact_message_replies', [
            'contact_message_id' => $message->id,
            'admin_id' => $admin->id,
            'mail_sent' => true,
        ]);
    }
}
