<?php

namespace Tests\Unit;

use App\Support\CloudinaryPath;
use Tests\TestCase;

class CloudinaryPathTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cloudinary.cloud_name' => 'drhu5dven', 'cloudinary.folder' => 'cyna']);
    }

    public function test_extracts_public_id_from_delivery_url(): void
    {
        $url = 'https://res.cloudinary.com/drhu5dven/image/upload/v1781694447/cyna/products/sample_wznrtb.png';

        $this->assertSame(
            'cyna/products/sample_wznrtb',
            CloudinaryPath::extractPublicIdFromUrl($url)
        );
    }

    public function test_normalizes_delivery_url_for_storage(): void
    {
        $url = 'https://res.cloudinary.com/drhu5dven/image/upload/cyna/categories/cat.png';

        $this->assertSame('cyna/categories/cat', CloudinaryPath::normalizeForStorage($url));
    }

    public function test_keeps_local_assets_unchanged(): void
    {
        $this->assertSame('logo.jpg', CloudinaryPath::normalizeForStorage('logo.jpg'));
        $this->assertSame('assets/images/slide1.jpg', CloudinaryPath::normalizeForStorage('assets/images/slide1.jpg'));
        $this->assertSame('product.jpg', CloudinaryPath::normalizeForStorage('product.jpg'));
    }

    public function test_builds_delivery_url_from_public_id(): void
    {
        $this->assertSame(
            'https://res.cloudinary.com/drhu5dven/image/upload/cyna/products/sample',
            CloudinaryPath::deliveryUrl('cyna/products/sample')
        );
    }

    public function test_delivery_url_keeps_legacy_full_url(): void
    {
        $url = 'https://res.cloudinary.com/drhu5dven/image/upload/cyna/products/legacy.png';

        $this->assertSame($url, CloudinaryPath::deliveryUrl($url));
    }
}
