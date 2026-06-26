<?php

namespace App\Support;

class CloudinaryPath
{
    public static function cloudName(): string
    {
        return trim((string) config('cloudinary.cloud_name', ''));
    }

    public static function isDeliveryUrl(?string $value): bool
    {
        return is_string($value)
            && preg_match('#^https?://res\.cloudinary\.com/#i', $value) === 1;
    }

    public static function isLocalAsset(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return true;
        }

        $value = trim(str_replace('\\', '/', $value));

        if (in_array($value, ['logo.jpg', 'logo.png'], true)) {
            return true;
        }

        if (str_starts_with($value, 'assets/') || str_starts_with($value, '/assets/')) {
            return true;
        }

        return ! str_contains($value, '/') && ! self::isDeliveryUrl($value);
    }

    public static function isCloudinaryStorage(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return false;
        }

        $value = trim($value);

        if (self::isDeliveryUrl($value) || self::isLocalAsset($value)) {
            return false;
        }

        return ! preg_match('#^https?://#i', $value);
    }

    public static function extractPublicIdFromUrl(string $url): ?string
    {
        if (! self::isDeliveryUrl($url)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || ! preg_match('#/image/upload/(.*)$#', $path, $matches)) {
            return null;
        }

        $segments = explode('/', $matches[1]);
        $start = 0;

        foreach ($segments as $index => $segment) {
            if (preg_match('/^v\d+$/', $segment)) {
                $start = $index + 1;
                break;
            }
        }

        if ($start === 0) {
            foreach ($segments as $index => $segment) {
                if ($segment === trim((string) config('cloudinary.folder', 'cyna'), '/')) {
                    $start = $index;
                    break;
                }
            }
        }

        $publicIdWithExtension = implode('/', array_slice($segments, $start));
        if ($publicIdWithExtension === '') {
            return null;
        }

        return preg_replace('/\.[^.\/]+$/', '', $publicIdWithExtension) ?: $publicIdWithExtension;
    }

    public static function normalizeForStorage(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (self::isDeliveryUrl($value)) {
            return self::extractPublicIdFromUrl($value) ?? $value;
        }

        if (self::isLocalAsset($value)) {
            return $value;
        }

        return ltrim(str_replace('\\', '/', $value), '/');
    }

    public static function deliveryUrl(?string $stored, ?string $cloudName = null): ?string
    {
        if ($stored === null || trim($stored) === '') {
            return null;
        }

        $stored = trim($stored);

        if (self::isDeliveryUrl($stored)) {
            return $stored;
        }

        if (self::isLocalAsset($stored)) {
            return $stored;
        }

        if (preg_match('#^https?://#i', $stored)) {
            return $stored;
        }

        $cloudName = $cloudName ?? self::cloudName();
        if ($cloudName === '') {
            return $stored;
        }

        return 'https://res.cloudinary.com/'.$cloudName.'/image/upload/'.ltrim($stored, '/');
    }
}
