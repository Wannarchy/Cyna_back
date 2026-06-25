<?php

namespace Tests\Feature;

use App\Models\HomepageContent;
use App\Models\HomepageSlide;
use Tests\Concerns\CreatesCatalog;
use Tests\TestCase;

class PublicContentTest extends TestCase
{
    use CreatesCatalog;

    public function test_categories_index_returns_only_active_categories_ordered(): void
    {
        $this->createCategory(['name' => 'Second', 'sort_order' => 2, 'is_active' => true]);
        $this->createCategory(['name' => 'First', 'sort_order' => 1, 'is_active' => true]);
        $this->createCategory(['name' => 'Hidden', 'sort_order' => 3, 'is_active' => false]);

        $response = $this->getJson('/api/categories');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $response->assertJsonPath('data.0.name', 'First');
        $response->assertJsonPath('data.1.name', 'Second');
    }

    public function test_product_show_returns_available_product(): void
    {
        $product = $this->createProduct(attributes: ['name' => 'Visible Product']);

        $this->getJson('/api/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', 'Visible Product');
    }

    public function test_product_show_hides_unavailable_product(): void
    {
        $product = $this->createProduct(attributes: [
            'name' => 'Hidden Product',
            'is_available' => false,
        ]);

        $this->getJson('/api/products/'.$product->id)
            ->assertNotFound()
            ->assertJsonPath('message', 'Produit introuvable.');
    }

    public function test_homepage_returns_active_slides_and_content(): void
    {
        HomepageSlide::create([
            'title' => 'Second',
            'sort_order' => 2,
            'is_active' => true,
        ]);
        HomepageSlide::create([
            'title' => 'First',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        HomepageSlide::create([
            'title' => 'Hidden',
            'sort_order' => 3,
            'is_active' => false,
        ]);
        HomepageContent::create([
            'content_text' => 'Bienvenue chez CYNA',
        ]);

        $response = $this->getJson('/api/homepage');

        $response->assertOk()
            ->assertJsonPath('data.slides.0.title', 'First')
            ->assertJsonPath('data.slides.1.title', 'Second')
            ->assertJsonPath('data.content.content_text', 'Bienvenue chez CYNA');

        $this->assertCount(2, $response->json('data.slides'));
    }
}
