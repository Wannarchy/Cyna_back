<?php

namespace App\Models;

use App\Support\CloudinaryPath;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class HomepageSlide extends Model
{
    public $timestamps = false;

    protected $appends = [
        'image_url',
    ];

    protected $fillable = [
        'title',
        'subtitle',
        'image_path',
        'link_url',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected function imagePath(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => CloudinaryPath::normalizeForStorage($value),
        );
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => CloudinaryPath::deliveryUrl($this->image_path),
        );
    }
}
