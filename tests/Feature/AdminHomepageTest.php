<?php

namespace Tests\Feature;

use App\Models\HomepageContent;
use App\Models\HomepageSlide;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AdminHomepageTest extends TestCase
{
    use CreatesUsers;

    public function test_admin_can_create_update_and_delete_homepage_slide(): void
    {
        $this->actingAsAdmin();

        $create = $this->putJson('/api/admin/homepage/slides', [
            'slides' => [
                [
                    'title' => 'Sécurité managée',
                    'subtitle' => 'SOC, EDR et XDR',
                    'image_path' => 'slide.jpg',
                    'link_url' => '/public/catalogue.php',
                    'sort_order' => 1,
                    'is_active' => true,
                ],
            ],
        ]);

        $create->assertOk()
            ->assertJsonPath('data.0.title', 'Sécurité managée');

        $slideId = $create->json('data.0.id');

        $this->putJson('/api/admin/homepage/slides', [
            'slides' => [
                [
                    'id' => $slideId,
                    'title' => 'Sécurité mise à jour',
                    'sort_order' => 2,
                    'is_active' => false,
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('data.0.title', 'Sécurité mise à jour')
            ->assertJsonPath('data.0.is_active', false);

        $this->deleteJson('/api/admin/homepage/slides/'.$slideId)
            ->assertOk()
            ->assertJsonPath('message', 'Slide supprimée.');

        $this->assertDatabaseMissing('homepage_slides', ['id' => $slideId]);
    }

    public function test_admin_can_create_and_update_homepage_content(): void
    {
        $this->actingAsAdmin();

        $this->putJson('/api/admin/homepage/content', [
            'content_text' => 'Premier contenu',
        ])->assertOk()
            ->assertJsonPath('data.content_text', 'Premier contenu');

        $this->putJson('/api/admin/homepage/content', [
            'content_text' => 'Contenu mis à jour',
        ])->assertOk()
            ->assertJsonPath('data.content_text', 'Contenu mis à jour');

        $this->assertSame(1, HomepageContent::count());
        $this->assertDatabaseHas('homepage_content', [
            'content_text' => 'Contenu mis à jour',
        ]);
    }

    public function test_destroy_slide_returns_not_found(): void
    {
        $this->actingAsAdmin();

        $this->deleteJson('/api/admin/homepage/slides/999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Slide introuvable.');
    }

    public function test_update_slides_validates_payload(): void
    {
        $this->actingAsAdmin();
        HomepageSlide::create([
            'title' => 'Existing',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->putJson('/api/admin/homepage/slides', [
            'slides' => [],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['slides']);
    }
}
