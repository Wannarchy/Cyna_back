<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AdminUserBlockTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    public function test_admin_can_block_user(): void
    {
        $admin = $this->actingAsAdmin();
        $user = $this->createUser();

        $this->patchJson("/api/admin/users/{$user->id}/bloquer", [
            'bloquer' => true,
        ])->assertOk();

        $this->assertTrue($user->fresh()->bloquer);
    }

    public function test_admin_cannot_block_self(): void
    {
        $admin = $this->actingAsAdmin();

        $this->patchJson("/api/admin/users/{$admin->id}/bloquer", [
            'bloquer' => true,
        ])->assertStatus(422);
    }

    public function test_admin_cannot_block_other_admin(): void
    {
        $this->actingAsAdmin();
        $otherAdmin = $this->createAdmin();

        $this->patchJson("/api/admin/users/{$otherAdmin->id}/bloquer", [
            'bloquer' => true,
        ])->assertStatus(422);
    }
}
