<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use CreatesUsers;

    public function test_page_view_requires_authentication(): void
    {
        $this->postJson('/api/activity/page-view', [
            'page' => 'checkout.php',
        ])->assertUnauthorized();
    }

    public function test_page_view_logs_allowed_page(): void
    {
        $user = $this->actingAsUser();

        $this->postJson('/api/activity/page-view', [
            'page' => '../checkout.php',
        ])->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('logs', [
            'user_id' => $user->id,
            'actor_type' => ActivityLog::ACTOR_USER,
            'action' => 'page.view',
            'target_type' => 'checkout.php',
        ]);
    }

    public function test_page_view_ignores_unlisted_page(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/activity/page-view', [
            'page' => 'unknown.php',
        ])->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseCount('logs', 0);
    }

    public function test_page_view_validates_page(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/activity/page-view', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['page']);
    }
}
