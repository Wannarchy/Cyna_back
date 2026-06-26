<?php

namespace App\Models;

use App\Support\CloudinaryPath;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'technical_specs',
        'image_path',
        'price_monthly',
        'price_yearly',
        'stripe_product_id',
        'stripe_price_id_monthly',
        'stripe_price_id_yearly',
        'is_available',
        'stock',
        'requires_shipping',
        'is_featured',
        'featured_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'technical_specs' => 'array',
            'is_available' => 'boolean',
            'stock' => 'integer',
            'requires_shipping' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    protected function imagePath(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => CloudinaryPath::normalizeForStorage($value),
        );
    }

    public function isPurchasable(): bool
    {
        return $this->is_available && (int) $this->stock > 0;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function productSubscriptions(): HasMany
    {
        return $this->hasMany(ProductSubscription::class, 'product_id');
    }
}
