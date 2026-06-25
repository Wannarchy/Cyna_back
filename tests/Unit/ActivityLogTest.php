<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use PHPUnit\Framework\TestCase;

class ActivityLogTest extends TestCase
{
    public function test_normalize_details_from_json_method(): void
    {
        $this->assertSame('POST', ActivityLog::normalizeDetails('{"method":"POST"}'));
    }

    public function test_normalize_details_from_array(): void
    {
        $this->assertSame('GET', ActivityLog::normalizeDetails(['method' => 'get']));
    }

    public function test_normalize_details_plain_string(): void
    {
        $this->assertSame('VIEW', ActivityLog::normalizeDetails('view'));
    }

    public function test_normalize_details_empty_returns_null(): void
    {
        $this->assertNull(ActivityLog::normalizeDetails(null));
        $this->assertNull(ActivityLog::normalizeDetails(''));
        $this->assertNull(ActivityLog::normalizeDetails('   '));
    }
}
