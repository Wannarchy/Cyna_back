<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCatalog;
use Tests\TestCase;

class ProductPaginationTest extends TestCase
{
    use CreatesCatalog;
    use RefreshDatabase;

    public function test_products_index_without_pagination_returns_all_available(): void
    {
        $this->createProduct(attributes: ['name' => 'A', 'is_available' => true]);
        $this->createProduct(attributes: ['name' => 'B', 'is_available' => true]);
        $this->createProduct(attributes: ['name' => 'Hidden', 'is_available' => false]);

        $response = $this->getJson('/api/products');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $this->assertNull($response->json('meta'));
    }

    public function test_products_index_with_pagination_returns_meta(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->createProduct(attributes: ['name' => "Product {$i}", 'featured_order' => $i]);
        }

        $response = $this->getJson('/api/products?page=1&per_page=2');

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.last_page', 3);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_products_filter_by_category(): void
    {
        $catA = $this->createCategory(['name' => 'Cat A']);
        $catB = $this->createCategory(['name' => 'Cat B', 'sort_order' => 2]);

        $this->createProduct($catA, ['name' => 'In A']);
        $this->createProduct($catB, ['name' => 'In B']);

        $response = $this->getJson('/api/products?category_id='.$catA->id);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('In A', $response->json('data.0.name'));
    }
}
