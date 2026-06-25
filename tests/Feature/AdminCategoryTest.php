<?php

namespace Tests\Feature;

use Tests\Concerns\CreatesCatalog;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use CreatesCatalog;
    use CreatesUsers;

    public function test_admin_can_create_update_and_delete_category(): void
    {
        $this->actingAsAdmin();

        $create = $this->postJson('/api/admin/categories', [
            'name' => 'Services Admin',
            'image_path' => 'category.jpg',
            'sort_order' => 20,
            'is_active' => true,
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.name', 'Services Admin');

        $categoryId = $create->json('data.id');

        $this->putJson('/api/admin/categories/'.$categoryId, [
            'name' => 'Services Mis à jour',
            'sort_order' => 21,
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Services Mis à jour')
            ->assertJsonPath('data.is_active', false);

        $this->deleteJson('/api/admin/categories/'.$categoryId)
            ->assertOk()
            ->assertJsonPath('message', 'Catégorie supprimée.');
    }

    public function test_admin_cannot_delete_category_with_products(): void
    {
        $this->actingAsAdmin();
        $category = $this->createCategory(['name' => 'With Products', 'sort_order' => 30]);
        $this->createProduct($category);

        $this->deleteJson('/api/admin/categories/'.$category->id)
            ->assertStatus(422)
            ->assertJsonFragment(['message' => "Impossible de supprimer cette catégorie : 1 produit(s) y sont rattachés. Réassignez ou supprimez ces produits d'abord."]);
    }

    public function test_admin_category_validation_rejects_duplicate_sort_order(): void
    {
        $this->actingAsAdmin();
        $this->createCategory(['name' => 'Existing', 'sort_order' => 40]);

        $this->postJson('/api/admin/categories', [
            'name' => 'Duplicate Order',
            'sort_order' => 40,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['sort_order']);
    }
}
