<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    public function test_mask_ipv4_truncates_last_octet(): void
    {
        $this->assertSame('192.168.1.0', AuditLogger::maskIp('192.168.1.42'));
    }

    public function test_mask_ipv6_truncates_tail(): void
    {
        $masked = AuditLogger::maskIp('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
        $this->assertStringEndsWith(':0', $masked);
        $this->assertStringStartsWith('2001:', $masked);
    }

    public function test_mask_ip_invalid_returns_null(): void
    {
        $this->assertNull(AuditLogger::maskIp('not-an-ip'));
        $this->assertNull(AuditLogger::maskIp(null));
    }

    public function test_is_allowed_page_view_whitelist(): void
    {
        $this->assertTrue(AuditLogger::isAllowedPageView('checkout.php'));
        $this->assertFalse(AuditLogger::isAllowedPageView('catalogue.php'));
        $this->assertFalse(AuditLogger::isAllowedPageView('recherche.php'));
    }

    public function test_should_log_request_rejects_guest(): void
    {
        $request = Request::create('/api/orders', 'POST');

        $this->assertFalse(AuditLogger::shouldLogRequest($request));
    }

    public function test_should_log_request_rejects_user_get_on_catalog(): void
    {
        $user = $this->createUser();
        $request = Request::create('/api/products', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->assertFalse(AuditLogger::shouldLogRequest($request));
    }

    public function test_should_log_request_accepts_user_post(): void
    {
        $user = $this->createUser();
        $request = Request::create('/api/orders', 'POST');
        $request->setUserResolver(fn () => $user);

        $this->assertTrue(AuditLogger::shouldLogRequest($request));
    }

    public function test_should_log_request_accepts_admin_get(): void
    {
        $admin = $this->createAdmin();
        $request = Request::create('/api/admin/products', 'GET');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue(AuditLogger::shouldLogRequest($request));
    }

    public function test_log_stores_user_id_and_actor_type_for_admin(): void
    {
        $admin = $this->createAdmin();
        $request = Request::create('/api/admin/products', 'POST');
        $request->setUserResolver(fn () => $admin);

        AuditLogger::log('product.create', 'Product', 1, 'POST', $request);

        $this->assertDatabaseHas('logs', [
            'actor_type' => ActivityLog::ACTOR_ADMIN,
            'user_id' => $admin->id,
            'action' => 'product.create',
        ]);
    }

    public function test_log_stores_user_id_and_actor_type_for_user(): void
    {
        $user = $this->createUser();
        $request = Request::create('/api/orders', 'POST');
        $request->setUserResolver(fn () => $user);

        AuditLogger::log('order.create', 'Order', null, 'POST', $request);

        $this->assertDatabaseHas('logs', [
            'actor_type' => ActivityLog::ACTOR_USER,
            'user_id' => $user->id,
            'action' => 'order.create',
        ]);
    }

    public function test_page_view_does_not_store_ip(): void
    {
        $user = $this->createUser();
        $request = Request::create('/api/activity/page-view', 'POST');
        $request->server->set('REMOTE_ADDR', '203.0.113.10');
        $request->setUserResolver(fn () => $user);

        AuditLogger::logPageView($request, 'checkout.php');

        $log = ActivityLog::first();
        $this->assertSame('page.view', $log->action);
        $this->assertNull($log->ip);
    }

    public function test_anonymize_for_deleted_user_removes_page_views_and_anonymizes_retained(): void
    {
        $user = $this->createUser();

        ActivityLog::create([
            'actor_type' => ActivityLog::ACTOR_USER,
            'user_id' => $user->id,
            'action' => 'page.view',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'actor_type' => ActivityLog::ACTOR_USER,
            'user_id' => $user->id,
            'action' => 'order.create',
            'ip' => '192.168.0.1',
            'created_at' => now(),
        ]);

        ActivityLog::create([
            'actor_type' => ActivityLog::ACTOR_USER,
            'user_id' => $user->id,
            'action' => 'auth.login',
            'ip' => '10.0.0.5',
            'created_at' => now(),
        ]);

        AuditLogger::anonymizeForDeletedUser($user->id);

        $this->assertDatabaseMissing('logs', [
            'user_id' => $user->id,
            'action' => 'page.view',
        ]);

        $this->assertDatabaseHas('logs', [
            'action' => 'order.create',
            'user_id' => null,
            'ip' => null,
        ]);

        $this->assertDatabaseMissing('logs', [
            'action' => 'auth.login',
            'user_id' => $user->id,
        ]);
    }
}
