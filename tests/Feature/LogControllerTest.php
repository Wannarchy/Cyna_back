<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class LogControllerTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    public function test_admin_can_paginate_logs(): void
    {
        $this->actingAsAdmin();

        for ($i = 0; $i < 3; $i++) {
            ActivityLog::create([
                'actor_type' => ActivityLog::ACTOR_USER,
                'user_id' => $this->createUser()->id,
                'action' => 'auth.login',
                'created_at' => now()->subMinutes($i),
            ]);
        }

        $response = $this->getJson('/api/admin/logs?per_page=2&page=1');

        $response->assertOk()
            ->assertJsonPath('data.current_page', 1)
            ->assertJsonPath('data.last_page', 2)
            ->assertJsonPath('data.per_page', 2);

        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_admin_can_filter_logs_by_actor_type(): void
    {
        $admin = $this->actingAsAdmin();
        $user = $this->createUser();

        ActivityLog::create([
            'actor_type' => ActivityLog::ACTOR_ADMIN,
            'user_id' => $admin->id,
            'action' => 'product.create',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'actor_type' => ActivityLog::ACTOR_USER,
            'user_id' => $user->id,
            'action' => 'order.create',
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/admin/logs?actor_type=user');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.data'));
        $this->assertSame('order.create', $response->json('data.data.0.action'));
    }

    public function test_non_admin_cannot_access_logs(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/admin/logs')->assertForbidden();
    }

    public function test_admin_can_filter_logs_by_action_date_and_search(): void
    {
        $admin = $this->actingAsAdmin();
        $user = $this->createUser(['email' => 'audit-filter@example.com', 'prenom' => 'Audit', 'nom' => 'Filter']);

        ActivityLog::create([
            'actor_type' => ActivityLog::ACTOR_USER,
            'user_id' => $user->id,
            'action' => 'order.create',
            'target_type' => 'Order',
            'target_id' => 42,
            'details' => 'POST',
            'created_at' => '2026-06-15 10:00:00',
        ]);

        ActivityLog::create([
            'actor_type' => ActivityLog::ACTOR_ADMIN,
            'user_id' => $admin->id,
            'action' => 'product.create',
            'created_at' => '2026-06-16 10:00:00',
        ]);

        $this->getJson('/api/admin/logs?action=order.create')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.action', 'order.create');

        $this->getJson('/api/admin/logs?date=2026-06-15')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');

        $this->getJson('/api/admin/logs?q=audit-filter@example.com')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');

        $this->getJson('/api/admin/logs?q=42')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');

        $this->getJson('/api/admin/logs?admin_id='.$admin->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.action', 'product.create');
    }
}
