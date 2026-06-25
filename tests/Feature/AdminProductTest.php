<?php

namespace Tests\Feature;

use Tests\Concerns\CreatesCatalog;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use CreatesCatalog;
    use CreatesUsers;

    private function productPayload(array $overrides = []): array
    {
        $category = $overrides['category'] ?? $this->createCategory([
            'name' => 'Admin Products',
            'sort_order' => 50,
        ]);
        unset($overrides['category']);

        return array_merge([
            'category_id' => $category->id,
            'name' => 'Admin Product '.uniqid(),
            'description' => 'Produit administrable',
            'image_path' => 'product.jpg',
            'price_monthly' => 199.00,
            'price_yearly' => 1990.00,
            'is_available' => true,
            'stock' => 5,
            'is_featured' => false,
            'featured_order' => 10,
        ], $overrides);
    }

    public function test_admin_products_require_admin(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/admin/products')->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_product(): void
    {
        $this->actingAsAdmin();
        $payload = $this->productPayload();

        $create = $this->postJson('/api/admin/products', $payload);

        $create->assertCreated()
            ->assertJsonPath('data.name', $payload['name']);

        $productId = $create->json('data.id');

        $this->putJson('/api/admin/products/'.$productId, array_merge($payload, [
            'name' => 'Updated Product',
            'price_monthly' => 249.00,
        ]))->assertOk()
            ->assertJsonPath('data.name', 'Updated Product');

        $this->deleteJson('/api/admin/products/'.$productId)
            ->assertOk()
            ->assertJsonPath('message', 'Produit supprimé.');

        $this->assertDatabaseMissing('products', ['id' => $productId]);
    }

    public function test_admin_product_validation_rejects_duplicate_name(): void
    {
        $this->actingAsAdmin();
        $category = $this->createCategory(['name' => 'Duplicate Cat', 'sort_order' => 60]);
        $this->createProduct($category, ['name' => 'Duplicate Product']);

        $this->postJson('/api/admin/products', $this->productPayload([
            'category' => $category,
            'name' => ' duplicate product ',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
