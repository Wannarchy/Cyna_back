<?php

namespace App\Services;

use App\Support\CloudinaryPath;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class CloudinaryUploadService
{
    public function __construct()
    {
        $cloudName = config('cloudinary.cloud_name');
        $apiKey = config('cloudinary.api_key');
        $apiSecret = config('cloudinary.api_secret');

        if (! $cloudName || ! $apiKey || ! $apiSecret) {
            throw new RuntimeException('Cloudinary n\'est pas configuré (CLOUDINARY_* manquant).');
        }

        Configuration::instance([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
            ],
            'url' => [
                'secure' => true,
            ],
        ]);
    }

    public function upload(UploadedFile $file, string $subfolder = 'products'): array
    {
        $baseFolder = trim((string) config('cloudinary.folder', 'cyna'), '/');
        $folder = $subfolder !== '' ? $baseFolder.'/'.$subfolder : $baseFolder;

        $result = (new UploadApi)->upload($file->getRealPath(), [
            'folder' => $folder,
            'resource_type' => 'image',
            'use_filename' => true,
            'unique_filename' => true,
            'overwrite' => false,
        ]);

        $publicId = (string) ($result['public_id'] ?? '');

        return [
            'public_id' => $publicId,
            'url' => CloudinaryPath::deliveryUrl($publicId) ?? (string) ($result['secure_url'] ?? $result['url'] ?? ''),
            'width' => (int) ($result['width'] ?? 0),
            'height' => (int) ($result['height'] ?? 0),
        ];
    }
}
