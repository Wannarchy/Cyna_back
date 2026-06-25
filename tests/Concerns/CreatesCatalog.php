<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\Product;
use App\Models\PromoCode;

trait CreatesCatalog
{
    protected function createCategory(array $attributes = []): Category
    {
        return Category::create(array_merge([
            'name' => 'Test Category',
            'image_path' => 'logo.jpg',
            'sort_order' => 1,
            'is_active' => true,
        ], $attributes));
    }

    protected function createProduct(?Category $category = null, array $attributes = []): Product
    {
        $category ??= $this->createCategory();

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'image_path' => 'logo.jpg',
            'price_monthly' => 100.00,
            'price_yearly' => 1000.00,
            'is_available' => true,
            'stock' => 10,
            'is_featured' => false,
            'featured_order' => 1,
        ], $attributes));
    }

    protected function createPromoCode(array $attributes = []): PromoCode
    {
        return PromoCode::create(array_merge([
            'code' => 'PROMO10',
            'type' => 'percent',
            'value' => 10,
            'min_amount' => 0,
            'max_uses' => null,
            'uses_count' => 0,
            'expires_at' => null,
            'is_active' => true,
        ], $attributes));
    }
}
